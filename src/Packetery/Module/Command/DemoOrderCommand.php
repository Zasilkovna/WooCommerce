<?php
declare(strict_types=1);

namespace Packetery\Module\Command;

use InvalidArgumentException;
use Packetery\Core\Entity\Carrier;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\Builder;
use Packetery\Module\Order\Repository;
use WC_Customer;
use WC_Order;
use WC_Order_Item_Shipping;
use WP_User;

/** @phpstan-type DemoOrderConfig array{
 *     customer_email: string,
 *     customer_address: array<string, string>,
 *     package_dimensions?: array {
 *          length: int,
 *          width: int,
 *          height: int,
 *          weights: list<int>
 *      }
 *     send_emails: bool,
 *     list_disabled_emails: list<string>,
 *     shipping_addresses_by_country?: array<string, list<array<string, string>>>
 * }
 */
class DemoOrderCommand {

	public const NAME = 'packeta-plugin-build-demo-order';

	/**
	 * @var string
	 */
	private $configPath;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var Builder
	 */
	private $builder;

	/**
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * @var EntityRepository
	 */
	private $carrierRepository;

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * @var CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/** @var DemoOrderConfig */
	private $validConfig;

	public function __construct(
		string $configPath,
		WpAdapter $wpAdapter,
		Builder $builder,
		Repository $orderRepository,
		EntityRepository $carrierRepository,
		OptionsProvider $optionsProvider,
		CarrierOptionsFactory $carrierOptionsFactory
	) {
		$this->configPath            = $configPath;
		$this->wpAdapter             = $wpAdapter;
		$this->builder               = $builder;
		$this->orderRepository       = $orderRepository;
		$this->carrierRepository     = $carrierRepository;
		$this->optionsProvider       = $optionsProvider;
		$this->carrierOptionsFactory = $carrierOptionsFactory;
	}

	/**
	 * Generates a demo order with Packet shipping.
	 *
	 * ## OPTIONS
	 *
	 * [--payment-method=<payment-method>]
	 * : Optional. Payment method to use for the order. If not set, both 'basc' and 'cod' are used.
	 *
	 * [--carrier-ids=<ids>]
	 * : Optional. Comma-separated list of carrier IDs to use. If not set, all active carriers without customs declarations are used.
	 *
	 * ## EXAMPLES
	 *
	 *     wp packeta-plugin-build-demo-order --payment-method=cod --carrier-ids=15,20
	 *
	 * @param string[] $args
	 * @param array<string, mixed> $assoc_args
	 */

	// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
	public function __invoke( array $args, array $assoc_args ): void {
		if ( ! is_file( $this->configPath ) ) {
			$this->wpAdapter->cliError( 'Config file does not exist.' );

			return;
		}

		$config = require $this->configPath;
		if ( ! is_array( $config ) ) {
			$this->wpAdapter->cliError( 'Config file must return an array.' );

			return;
		}

		try {
			$this->validConfig = $this->processConfig( $config );
			$this->wpAdapter->cliSuccess( 'Config settings were imported.' );
		} catch ( InvalidArgumentException $invalidArgumentException ) {
			$this->wpAdapter->cliError( $invalidArgumentException->getMessage() );
		}

		// phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
		$paymentMethods = isset( $assoc_args['payment-method'] ) ? [ sanitize_text_field( $assoc_args['payment-method'] ) ] : [ 'basc', 'cod' ];

		$carrierIds = null;
		if ( isset( $assoc_args['carrier-ids'] ) ) {
			$carrierIds = array_map( 'intval', explode( ',', sanitize_text_field( $assoc_args['carrier-ids'] ) ) );
		}
		// phpcs:enable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
		if ( $this->validConfig['send_emails'] === false ) {
			$this->wpAdapter->cliLine( 'Disabling email sending' );
			$this->disableEmails();
			$this->wpAdapter->cliSuccess( 'Email sending has been disabled' );
		}

		$this->wpAdapter->cliLine( 'Try create packeta demo customer' );
		$customer = $this->createCustomerIfNotExists();

		$this->wpAdapter->cliLine( 'Start creating Packeta demo orders' );

		$carriers       = $this->getFilteredCarriers( $carrierIds );
		$numbOfCarriers = count( $carriers );
		$count          = 0;

		foreach ( $carriers as $carrier ) {
			$countryCode = strtoupper( $carrier->getCountry() );
			$addressList = $this->validConfig['shipping_addresses_by_country'][ $countryCode ] ?? null;

			if ( ! is_array( $addressList ) || $addressList === [] ) {
				$this->wpAdapter->cliLog(
					sprintf(
						"Skipping carrier ID %s (%s): no addresses configured for country '%s'.",
						$carrier->getId(),
						$carrier->getName(),
						$countryCode
					)
				);

				continue;
			}

			foreach ( $paymentMethods as $paymentMethod ) {
				foreach ( $addressList as $addressIndex => $shippingAddress ) {
					$count++;
					$this->wpAdapter->cliLine(
						sprintf(
							'Creating order %d/%d for carrier %s and address #%d (%s)...',
							$count,
							$numbOfCarriers,
							$carrier->getName(),
							$addressIndex + 1,
							$countryCode
						)
					);

					$wcOrder = $this->makeWcOrder( $customer, $paymentMethod, $carrier, $shippingAddress );
					if ( $wcOrder === null ) {
						continue;
					}

					$packetaOrderData = $this->makePacketaOrderData( $wcOrder, $carrier );
					// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
					if ( $packetaOrderData->point_id !== null && $this->optionsProvider->replaceShippingAddressWithPickupPointAddress() ) {
						$wcOrder->set_shipping_address_1( $packetaOrderData->point_street );
						$wcOrder->set_shipping_address_2( '' );
						$wcOrder->set_shipping_company( $packetaOrderData->point_name );

						$wcOrder->set_shipping_city( $packetaOrderData->point_city );
						$wcOrder->set_shipping_postcode( $packetaOrderData->point_zip );
					}
					// phpcs:enable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
					$packetaOrder = $this->builder->build( $wcOrder, $packetaOrderData );
					$this->orderRepository->save( $packetaOrder );
				}
			}
		}

		$this->wpAdapter->cliSuccess( 'Packeta demo orders have been successfully created' );
	}

	private function makeWcOrder( WC_Customer $customer, string $paymentMethod, Carrier $carrier, array $shippingAddress ): ?\WC_Order {
		$countryCode = strtoupper( $carrier->getCountry() );
		$phoneNumber = $shippingAddress['phone'] ?? null;

		if ( $phoneNumber === null ) {
			$this->wpAdapter->cliLog(
				sprintf(
					"Skipping order for carrier ID %s (%s): missing phone number for country code '%s'.",
					$carrier->getId(),
					$carrier->getName(),
					$countryCode
				)
			);

			return null;
		}

		$order    = new \WC_Order();
		$products = $this->getProducts();

		foreach ( $products as $product ) {
			$quantity = wp_rand( 1, 2 );
			$order->add_product( $product, $quantity );
		}

		$order->set_customer_id( $customer->get_id() );
		$order->set_created_via( 'Packeta demo order generator' );
		$order->set_currency( get_woocommerce_currency() );
		$order->set_address( $customer->get_billing(), 'billing' );
		$order->set_address( $shippingAddress, 'shipping' );
		$order->set_billing_email( $customer->get_billing_email() );
		$order->set_billing_phone( $phoneNumber );

		$shippingItem = new WC_Order_Item_Shipping();
		$shippingItem->set_method_title( $carrier->getName() );
		$shippingItem->set_method_id( 'packeta_method_' . $carrier->getId() );
		$shippingItem->set_total( '119' );
		$order->add_item( $shippingItem );

		$order->set_payment_method( $paymentMethod );
		$order->set_status( 'on-hold' );

		$order->calculate_totals( true );
		$order->save();

		return $order;
	}

	private function getProducts(): array {
		global $wpdb;

		$minAmountProducts = 1;
		$maxAmountProducts = 3;
		$products          = [];

		$countProducts = (int) $wpdb->get_var(
			"SELECT COUNT( DISTINCT ID )
			FROM {$wpdb->posts}
			WHERE post_type='product'
			AND post_status='publish'"
		);

		$randomCountProducts = wp_rand( $minAmountProducts, $maxAmountProducts );

		if ( $randomCountProducts > $countProducts ) {
			$randomCountProducts = $countProducts;
		}

		$query = new \WC_Product_Query(
			[
				'limit'   => $randomCountProducts,
				'return'  => 'ids',
				'orderby' => 'rand',
			]
		);

		foreach ( $query->get_products() as $productId ) {
			$product = wc_get_product( $productId );
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			if ( $product->is_type( 'variable' ) ) {
				if ( ! $product instanceof \WC_Product_Variable ) {
					continue;
				}
				$availableVariations = $product->get_available_variations();
				$countVariations     = count( $availableVariations );
				if ( $countVariations <= 0 ) {
					continue;
				}
				$index      = wp_rand( 0, $countVariations - 1 );
				$products[] = new \WC_Product_Variation( $availableVariations[ $index ]['variation_id'] );
			} else {
				$products[] = new \WC_Product( $productId );
			}
		}

		return $products;
	}

	private function createCustomerIfNotExists(): WC_Customer {
		$user = get_user_by( 'email', $this->validConfig['customer_email'] );

		if ( $user instanceof WP_User ) {
			$this->wpAdapter->cliLine( 'The user is already created with an ID: ' . $user->ID );

			return new WC_Customer( $user->ID );
		}

		$customer = new WC_Customer();
		$customer->set_username( 'john.packeta' );
		$customer->set_password( 'Packeta123456' );
		$customer->set_email( $this->validConfig['customer_email'] );

		$customer->set_props(
			$this->validConfig['list_disabled_emails']
		);
		$customerId = $customer->save();

		if ( ! is_int( $customerId ) ) {
			$this->wpAdapter->cliError( 'Customer creation error' );
		}

		$this->wpAdapter->cliLine( 'Customer was successfully created with ID: ' . $customerId );

		return $customer;
	}

	private function makePacketaOrderData( WC_Order $wcOrder, Carrier $carrier ): PacketaOrderData {
		$packetaOrderData     = new PacketaOrderData();
		$packetaOrderData->id = (string) $wcOrder->get_id();
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		$packetaOrderData->carrier_id = $carrier->getId();
		$packetaOrderData->value      = (float) $wcOrder->get_total( 'raw' );

		if ( $carrier->hasPickupPoints() ) {
			if ( ! is_numeric( $carrier->getId() ) ) {
				$pickupPoint = $this->getFirstPickupPointForCarrier( $carrier ); // Zásilkovna
			} else {
				$pickupPoint = $this->getFirstCarrierPointForCarrier( $carrier ); // jiný dopravce
			}

			if ( isset( $this->validConfig['package_dimensions'] ) ) {
				$dimensions               = $this->validConfig['package_dimensions'];
				$packetaOrderData->length = $dimensions['length'];
				$packetaOrderData->width  = $dimensions['width'];
				$packetaOrderData->height = $dimensions['height'];

				$weights                  = $dimensions['weights'];
				$randomIndex              = array_rand( $weights );
				$packetaOrderData->weight = $weights[ $randomIndex ];
			}

			if ( $pickupPoint !== null ) {
				// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
				$packetaOrderData->point_id     = (string) $pickupPoint['id'];
				$packetaOrderData->point_name   = $pickupPoint['name'];
				$packetaOrderData->point_street = $pickupPoint['street'];
				$packetaOrderData->point_city   = $pickupPoint['city'];
				$packetaOrderData->point_zip    = $pickupPoint['zip'];
				// phpcs:enable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
			}
		}

		return $packetaOrderData;
	}

	private function disableEmails(): void {
		foreach ( $this->validConfig['list_disabled_emails'] as $action ) {
			remove_action( $action, [ 'WC_Emails', 'send_transactional_email' ] );
		}

		if ( ! has_action( 'woocommerce_allow_send_queued_transactional_email', '__return_false' ) ) {
			add_action(
				'woocommerce_allow_send_queued_transactional_email',
				static function (): void {
					// intentionally do nothing, just stop queued emails.
				}
			);
		}
	}

	/**
	 * @param int[]|null $filterIds
	 * @return Carrier[]
	 */
	private function getFilteredCarriers( ?array $filterIds = null ): array {
		$carriers = $this->carrierRepository->getAllCarriersIncludingNonFeed();

		if ( is_array( $filterIds ) ) {
			$filteredCarriers = array_filter(
				$carriers,
				function ( $carrier ) use ( $filterIds ) {
					return in_array( (int) $carrier->getId(), $filterIds, true );
				}
			);

			if ( count( $filteredCarriers ) === 0 ) {
				$this->wpAdapter->cliError( 'No carriers found for the specified IDs.' );
			}

			return $filteredCarriers;
		}

		$filteredCarriers = array_filter(
			$carriers,
			function ( $carrier ) {
				$carrierOptions = $this->carrierOptionsFactory->createByCarrierId( $carrier->getId() );

				return $carrierOptions->isActive()
						&& ! $carrier->isDeleted()
						&& ! $carrier->requiresCustomsDeclarations();
			}
		);

		if ( count( $filteredCarriers ) === 0 ) {
			$this->wpAdapter->cliError( 'No available carriers found.' );
		}

		return $filteredCarriers;
	}

	private function getFirstCarrierPointForCarrier( Carrier $carrier ): ?array {
		if ( ! $carrier->hasPickupPoints() ) {
			return null;
		}

		$apiKey = $this->optionsProvider->get_api_key();

		$url = sprintf(
			'https://pickup-point.api.packeta.com/v5/%s/carrier_point/json?ids[]=%s',
			$apiKey,
			$carrier->getId()
		);

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );

		$data = json_decode( $body, true );

		if ( ! isset( $data['carriers'][0]['points'] ) ) {
			return null;
		}

		$point = $data['carriers'][0]['points'][0];

		return [
			'id'      => $point['code'],
			'name'    => sprintf( '%s %s %s', $point['street'], $point['streetNumber'], $point['city'] ),
			'street'  => $point['street'] . ' ' . $point['streetNumber'],
			'city'    => $point['city'],
			'zip'     => $point['zip'],
			'country' => strtoupper( $point['country'] ),
		];
	}

	private function getFirstPickupPointForCarrier( Carrier $carrier ): ?array {
		if ( ! $carrier->hasPickupPoints() ) {
			return null;
		}

		$apiKey   = $this->optionsProvider->get_api_key();
		$language = 'cs';
		$country  = strtolower( $carrier->getCountry() );
		$type     = strpos( $carrier->getId(), Carrier::VENDOR_GROUP_ZBOX ) !== false ? 'box' : 'branch';

		$url = sprintf(
			'https://pickup-point.api.packeta.com/v5/%s/%s/json?lang=%s',
			$apiKey,
			$type,
			$language
		);

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );

		$data = json_decode( $body, true );
		foreach ( $data as $point ) {
			if (
				isset( $point['country'], $point['displayFrontend'] )
				&& $point['country'] === $country
				&& $point['displayFrontend'] === '1'
			) {
				return $point;
			}
		}

		return null;
	}

	/**
	 * @return DemoOrderConfig
	 * @throws InvalidArgumentException When invalid config is provided.
	 */
	private function processConfig( array $config ): array {
		if (
			! isset( $config['customer_email'], $config['customer_address'], $config['list_disabled_emails'] )
		) {
			throw new InvalidArgumentException(
				'Configuration must contain customer_email, customer_address and list_disabled_emails'
			);
		}
		if ( isset( $config['package_dimensions'] ) ) {
			foreach ( [ 'length', 'width', 'height', 'weights' ] as $key ) {
				if ( ! isset( $config['package_dimensions'][ $key ] ) ) {
					throw new InvalidArgumentException( "Missing package_dimensions[$key]" );
				}
			}
		}

		return $config;
	}
}
