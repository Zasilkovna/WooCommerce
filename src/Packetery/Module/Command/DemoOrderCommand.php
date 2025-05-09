<?php
declare(strict_types=1);

namespace Packetery\Module\Command;

use InvalidArgumentException;
use Packetery\Core\Entity\Carrier;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\Builder;
use Packetery\Module\Order\Repository;
use WC_Customer;
use WC_Order_Item_Shipping;
use WP_User;

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
	 * @var array{
	 *      customer_email: string,
	 *      customer_address: array<string, string>,
	 *      list_disabled_emails: list<string>
	 *  }
	 */
	private $validConfig;

	public function __construct(
		string $configPath,
		WpAdapter $wpAdapter,
		Builder $builder,
		Repository $orderRepository,
		EntityRepository $carrierRepository,
		OptionsProvider $optionsProvider
	) {
		$this->configPath        = $configPath;
		$this->wpAdapter         = $wpAdapter;
		$this->builder           = $builder;
		$this->orderRepository   = $orderRepository;
		$this->carrierRepository = $carrierRepository;
		$this->optionsProvider   = $optionsProvider;
	}

	/**
	 * Generates a demo order with Packet shipping.
	 *
	 * ## OPTIONS
	 *
	 * [--payment-method=<payment-method>]
	 * : Payment method to set. E.g., 'cod', 'bacs'. If not set, uses 'basc' by default.
	 *
	 * ## EXAMPLES
	 *
	 *     wp packeta-plugin-build-demo-order --count=5 --payment-method=cod
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

		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
		$paymentMethod = isset( $assoc_args['payment-method'] ) ? sanitize_text_field( $assoc_args['payment-method'] ) : 'basc';

		$this->wpAdapter->cliLine( 'Disabling email sending' );
		$this->disableEmails();
		$this->wpAdapter->cliSuccess( 'Email sending has been disabled' );

		$this->wpAdapter->cliLine( 'Try create packeta demo customer' );
		$customer = $this->createCustomerIfNotExists();

		$this->wpAdapter->cliLine( 'Start creating Packeta demo orders' );

		$carriers       = $this->getFilteredCarriers();
		$numbOfCarriers = count( $carriers );
		$count          = 0;

		foreach ( $carriers as $carrier ) {
			$count++;
			$this->wpAdapter->cliLine( sprintf( 'Creating order %d/%d...', $count, $numbOfCarriers ) );
			$wcOrder          = $this->makeWcOrder( $customer, $paymentMethod, $carrier );
			$packetaOrderData = $this->makePacketaOrderData( (string) $wcOrder->get_id(), $carrier );
			$packetaOrder     = $this->builder->build( $wcOrder, $packetaOrderData );
			$this->orderRepository->save( $packetaOrder );
		}
		$this->wpAdapter->cliSuccess( 'Packeta demo orders have been successfully created' );
	}

	private function makeWcOrder( WC_Customer $customer, string $paymentMethod, Carrier $carrier ): \WC_Order {
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
		$order->set_address( ShippingAdreessDataFixtures::getAddressByCountry( $carrier->getCountry() ), 'shipping' );
		$order->set_billing_email( $customer->get_billing_email() );
		$order->set_billing_phone( ShippingAdreessDataFixtures::getPhoneByCountry( $carrier->getCountry() ) );

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

	private function makePacketaOrderData( string $orderId, Carrier $carrier ): PacketaOrderData {
		$packetaOrderData     = new PacketaOrderData();
		$packetaOrderData->id = $orderId;
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		$packetaOrderData->carrier_id = $carrier->getId();

		if ( $carrier->hasPickupPoints() ) {
			if ( ! is_numeric( $carrier->getId() ) ) {
				$pickupPoint = $this->getFirstPickupPointForCarrier( $carrier ); // Zásilkovna
			} else {
				$pickupPoint = $this->getFirstCarrierPointForCarrier( $carrier ); // jiný dopravce
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

	private function getFilteredCarriers(): array {
		$carriers = $this->carrierRepository->getAllCarriersIncludingNonFeed();

		$availableCarriers = array_filter(
			$carriers,
			function ( $carrier ) {
				return $carrier->isAvailable() && ! $carrier->isDeleted();
			}
		);

		if ( count( $availableCarriers ) === 0 ) {
			$this->wpAdapter->cliError( 'No available carriers found.' );
		}

		$filteredCarriers = array_filter(
			$availableCarriers,
			function ( $carrier ) {
				return ! $carrier->requiresCustomsDeclarations();
			}
		);

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
	 * @return array{
	 *      customer_email: string,
	 *      customer_address: array<string, string>,
	 *      list_disabled_emails: list<string>
	 *  }
	 * @throws InvalidArgumentException When invalid config is provided.
	 */
	private function processConfig( array $config ): array {
		if ( ! isset( $config['customer_email'], $config['customer_address'], $config['list_disabled_emails'] ) ) {
			throw new InvalidArgumentException(
				'Configuration must contain customer_email, customer_address and list_disabled_emails'
			);
		}

		return $config;
	}
}
