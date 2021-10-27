<?php
/**
 * Packeta plugin class for checkout.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Module\Carrier\Repository;
use Packetery\Module\Options\Provider;
use Packetery\Module\Order\Entity;
use PacketeryLatte\Engine;
use PacketeryNette\Http\Request;

/**
 * Class Checkout
 *
 * @package Packetery
 */
class Checkout {

	public const CARRIER_PREFIX = 'packetery_carrier_';
	private const NONCE_ACTION  = 'packetery_checkout';

	/**
	 * Pickup point attributes configuration.
	 *
	 * @var array[]
	 */
	public static $pickupPointAttrs = array(
		'id'                   => array(
			'name'     => Entity::META_POINT_ID,
			'required' => true,
		),
		'name'                 => array(
			'name'     => Entity::META_POINT_NAME,
			'required' => true,
		),
		'city'                 => array(
			'name'     => Entity::META_POINT_CITY,
			'required' => true,
		),
		'zip'                  => array(
			'name'     => Entity::META_POINT_ZIP,
			'required' => true,
		),
		'street'               => array(
			'name'     => Entity::META_POINT_STREET,
			'required' => true,
		),
		'pickupPointType'      => array(
			'name'     => Entity::META_POINT_TYPE,
			'required' => true,
		),
		'carrierId'            => array(
			'name'     => Entity::META_CARRIER_ID,
			'required' => false,
		),
		'carrierPickupPointId' => array(
			'name'     => Entity::META_POINT_CARRIER_ID,
			'required' => false,
		),
		'url'                  => array(
			'name'     => Entity::META_POINT_URL,
			'required' => true,
		),
	);

	/**
	 * Home delivery attributes configuration.
	 *
	 * @var array[]
	 */
	private static $homeDeliveryAttrs = [
		'active' => [ // Post type address field called 'active'
			'name'                => 'packetery_address_active', // Name of checkout hidden form field. Must be unique in entire form.
			'isWidgetResultField' => false, // Is attribute included in widget result address? By default it is.
			'castToInt'           => true, // Will backend cast value passed by browser to integer? Default value is false.
		],
		'houseNumber' => [ // post type address field called 'houseNumber'
			'name' => 'packetery_address_houseNumber',
		],
		'street'      => [
			'name' => 'packetery_address_street',
		],
		'city'        => [
			'name' => 'packetery_address_city',
		],
		'postCode'    => [
			'name'              => 'packetery_address_postCode',
			'widgetResultField' => 'postcode', // Widget returns address object containing specified field. By default it is the array key 'postCode', but in this case it is 'postcode'.
		],
		'county'      => [
			'name' => 'packetery_address_county',
		],
		'country'     => [
			'name' => 'packetery_address_country',
		],
		'latitude'    => [
			'name' => 'packetery_address_latitude',
		],
		'longitude'   => [
			'name' => 'packetery_address_longitude',
		],
	];

	/**
	 * PacketeryLatte engine
	 *
	 * @var Engine
	 */
	private $latte_engine;

	/**
	 * Options provider.
	 *
	 * @var Provider Options provider.
	 */
	private $options_provider;

	/**
	 * Carrier repository.
	 *
	 * @var Repository Carrier repository.
	 */
	private $carrierRepository;

	/**
	 * Http request.
	 *
	 * @var Request Http request.
	 */
	private $httpRequest;

	/**
	 * Address repository.
	 *
	 * @var Address\Repository
	 */
	private $addressRepository;

	/**
	 * Checkout constructor.
	 *
	 * @param Engine             $latte_engine      PacketeryLatte engine.
	 * @param Provider           $options_provider  Options provider.
	 * @param Repository         $carrierRepository Carrier repository.
	 * @param Request            $httpRequest       Http request.
	 * @param Address\Repository $addressRepository Address repository.
	 */
	public function __construct( Engine $latte_engine, Provider $options_provider, Repository $carrierRepository, Request $httpRequest, Address\Repository $addressRepository ) {
		$this->latte_engine      = $latte_engine;
		$this->options_provider  = $options_provider;
		$this->carrierRepository = $carrierRepository;
		$this->httpRequest       = $httpRequest;
		$this->addressRepository = $addressRepository;
	}

	/**
	 * Check if chosen shipping rate is bound with Packeta pickup points
	 *
	 * @return bool
	 */
	public function isPickupPointOrder(): bool {
		$chosenMethod = $this->getChosenMethod();
		$carrierId    = $this->getCarrierId( $chosenMethod );

		return $carrierId && $this->carrierRepository->isPickupPointCarrier( $carrierId );
	}

	/**
	 * Check if chosen shipping rate is bound with Packeta home delivery
	 *
	 * @return bool
	 */
	public function isHomeDeliveryOrder(): bool {
		$chosenMethod = $this->getChosenMethod();
		$carrierId    = $this->getCarrierId( $chosenMethod );

		return $carrierId && $this->carrierRepository->isHomeDeliveryCarrier( $carrierId );
	}

	/**
	 * Renders widget button and information about chosen pickup point
	 */
	public function renderWidgetButton(): void {
		$country = $this->getCustomerCountry();
		if ( ! $country ) {
			$this->latte_engine->render( PACKETERY_PLUGIN_DIR . '/template/checkout/country-error.latte' ); // TODO: Figure out reason. Under what circumstances customer country is empty.
			return;
		}

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/checkout/widget-button.latte',
			[
				'logo' => plugin_dir_url( PACKETERY_PLUGIN_DIR . '/packetery.php' ) . 'public/packeta-symbol.png',
			]
		);
	}

	/**
	 * Renders main checkout script
	 */
	public function render_after_checkout_form(): void {
		$appIdentity = 'woocommerce-' . get_bloginfo( 'version' ) . '-' . WC_VERSION . '-' . Plugin::VERSION;

		$carrierConfig = [];
		$carriers      = $this->carrierRepository->getAllIncludingZpoints();

		foreach ( $carriers as $carrier ) {
			$optionId                   = self::CARRIER_PREFIX . $carrier['id'];
			$carrierConfig[ $optionId ] = [
				'id'               => $carrier['id'],
				'is_pickup_points' => $carrier['is_pickup_points'],
			];

			if ( $carrier['is_pickup_points'] ) {
				$carrierConfig[ $optionId ]['carriers'] = ( is_numeric( $carrier['id'] ) ? $carrier['id'] : Repository::INTERNAL_PICKUP_POINTS_ID );
			}
		}

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/checkout/init.latte',
			[
				'settings' => [
					'language'          => substr( get_locale(), 0, 2 ),
					'country'           => $this->getCustomerCountry(),
					'weight'            => $this->getCartWeightKg(),
					'carrierConfig'     => $carrierConfig,
					'pickupPointAttrs'  => self::$pickupPointAttrs,
					'homeDeliveryAttrs' => self::$homeDeliveryAttrs,
					'appIdentity'       => $appIdentity,
					'packeteryApiKey'   => $this->options_provider->get_api_key(),
					'translations'      => [
						'choosePickupPoint'             => __( 'choosePickupPoint', 'packetery' ),
						'chooseAddress'                 => __( 'chooseAddress', 'packetery' ),
						'addressValidationIsOutOfOrder' => __( 'addressValidationIsOutOfOrder', 'packetery' ),
						'invalidAddressCountrySelected' => __( 'invalidAddressCountrySelected', 'packetery' ),
					],
				],
			]
		);
	}

	/**
	 * Adds fields to checkout page to save the values later
	 *
	 * @link https://docs.woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/
	 *
	 * @param array $fields Fields before adding.
	 *
	 * @return array
	 */
	public static function add_pickup_point_fields( array $fields ): array {
		foreach ( self::$pickupPointAttrs as $attr ) {
			$fields['billing'][ $attr['name'] ] = [
				'type'              => 'text',
				'required'          => false,
				// For older WooCommerce. See woocommerce_form_field function.
				'custom_attributes' => [ 'style' => 'display: none;' ],
			];
		}

		foreach ( self::$homeDeliveryAttrs as $attr ) {
			$fields['shipping'][ $attr['name'] ] = [
				'type'              => 'text',
				'required'          => false,
				'custom_attributes' => [ 'style' => 'display: none;' ],
			];
		}

		return $fields;
	}

	/**
	 * Checks if all pickup point attributes are set, sets an error otherwise.
	 */
	public function validatePickupPointData(): void {
		if ( $this->isPickupPointOrder() ) {
			$post = $this->httpRequest->getPost();
			if ( ! wp_verify_nonce( $post['_wpnonce'], self::NONCE_ACTION ) ) {
				wp_nonce_ays( '' );
			}

			$error          = false;
			$required_attrs = array_filter(
				array_combine(
					array_column( self::$pickupPointAttrs, 'name' ),
					array_column( self::$pickupPointAttrs, 'required' )
				)
			);
			foreach ( $required_attrs as $attr => $required ) {
				$attr_value = null;
				if ( isset( $post[ $attr ] ) ) {
					$attr_value = $post[ $attr ];
				}
				if ( ! $attr_value ) {
					$error = true;
				}
			}
			$carrierId = null;
			if ( isset( $post['carrier_id'] ) ) {
				$carrierId = $post['carrier_id'];
			}
			$pointCarrierId = null;
			if ( isset( $post['point_carrier_id'] ) ) {
				$pointCarrierId = $post['point_carrier_id'];
			}
			if ( $carrierId && ! $pointCarrierId ) {
				$error = true;
			}
			if ( ! $carrierId && $pointCarrierId ) {
				$error = true;
			}
			if ( $error ) {
				wc_add_notice( __( 'Pick up point is not chosen.', 'packetery' ), 'error' );
			}
		}
	}

	/**
	 * Saves pickup point and other Packeta information to order.
	 *
	 * @param int $orderId Order id.
	 */
	public function updateOrderMeta( int $orderId ): void {
		$chosenMethod = $this->getChosenMethod();
		if ( false === $this->isPacketeryOrder( $chosenMethod ) ) {
			return;
		}
		$post = $this->httpRequest->getPost();
		if ( empty( $post[ Entity::META_CARRIER_ID ] ) ) {
			$carrierId = $this->getCarrierId( $chosenMethod );
			if ( $carrierId ) {
				update_post_meta( $orderId, Entity::META_CARRIER_ID, $carrierId );
			}
		}

		if ( ! wp_verify_nonce( $post['_wpnonce'], self::NONCE_ACTION ) ) {
			wp_nonce_ays( '' );
		}

		if ( $this->isPickupPointOrder() ) {
			foreach ( self::$pickupPointAttrs as $attr ) {
				if (
					isset( $post[ $attr['name'] ] ) &&
					( Entity::META_CARRIER_ID !== $attr['name'] || $post[ $attr['name'] ] )
				) {
					update_post_meta( $orderId, $attr['name'], $post[ $attr['name'] ] );
				}
			}
		}

		if ( $this->isHomeDeliveryOrder() ) {
			$address = [];

			foreach ( self::$homeDeliveryAttrs as $field => $attributeData ) {
				$value = $post[ $attributeData['name'] ];

				if ( $attributeData['castToInt'] ?? false ) {
					$value = (int) $value;
				}

				$address[ $field ] = $value;
			}

			if ( $address['active'] === 1 ) {
				$this->addressRepository->save( $orderId, $address ); // TODO: think about address modifications by users
			}
		}
	}

	/**
	 * Renders nonce field
	 */
	public static function render_nonce_field() {
		wp_nonce_field( self::NONCE_ACTION );
	}

	/**
	 * Registers Packeta checkout hooks
	 */
	public function register_hooks(): void {
		add_action( 'woocommerce_review_order_before_payment', array( $this, 'renderWidgetButton' ) );
		add_action( 'woocommerce_after_checkout_form', array( $this, 'render_after_checkout_form' ) );
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'add_pickup_point_fields' ) );
		add_action( 'woocommerce_after_order_notes', array( __CLASS__, 'render_nonce_field' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'validatePickupPointData' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'updateOrderMeta' ) );
		add_action( 'woocommerce_review_order_before_shipping', array( $this, 'updateShippingRates' ), 10, 2 );
	}

	/**
	 * Updates shipping rates cost based on cart properties.
	 */
	public function updateShippingRates(): void {
		$customRates = $this->getShippingRates();

		$packages = WC()->shipping()->get_packages();
		foreach ( $packages as $i => $package ) {
			if ( ! empty( $package['rates'] ) ) {
				foreach ( $package['rates'] as $key => $rate ) {
					if ( isset( $customRates[ $rate->get_id() ] ) ) {
						$rate->set_cost( $customRates[ $rate->get_id() ]['cost'] );
						WC()->shipping->packages[ $i ]['rates'][ $key ] = $rate;
					}
				}
			}
		}
	}

	/**
	 * Gets customer country from WC cart.
	 *
	 * @return string
	 */
	public function getCustomerCountry(): string {
		$country = strtolower( WC()->customer->get_shipping_country() );
		if ( ! $country ) {
			$country = strtolower( WC()->customer->get_billing_country() );
		}

		return $country;
	}

	/**
	 * Gets cart contents weight in kg.
	 *
	 * @return float|int
	 */
	public function getCartWeightKg() {
		$weight = WC()->cart->cart_contents_weight;
		$weight = wc_get_weight( $weight, 'kg' );

		return $weight;
	}

	/**
	 * Prepare shipping rates based on cart properties.
	 *
	 * @return array
	 */
	public function getShippingRates(): array {
		$customerCountry   = $this->getCustomerCountry();
		$availableCarriers = $this->carrierRepository->getByCountryIncludingZpoints( $customerCountry );
		$carrierOptions    = [];
		foreach ( $availableCarriers as $carrier ) {
			$optionId                    = self::CARRIER_PREFIX . $carrier['id'];
			$carrierOptions[ $optionId ] = get_option( $optionId );
		}

		$cartPrice = (float) WC()->cart->get_subtotal();

		$cartWeight = $this->getCartWeightKg();

		$isCod        = false;
		$codMethod    = $this->options_provider->getCodPaymentMethod();
		$chosenMethod = WC()->session->get( 'chosen_payment_method' );
		if ( null !== $codMethod && ! empty( $chosenMethod ) && $chosenMethod === $codMethod ) {
			$isCod = true;
		}

		$customRates = [];
		foreach ( $carrierOptions as $optionId => $options ) {
			if ( is_array( $options ) && true === $options['active'] ) {
				$cost = $this->getRateCost( $options, $cartPrice, $cartWeight, $isCod );
				if ( null !== $cost ) {
					$customRates[ $optionId ] = [
						'label'    => $options['name'],
						'id'       => $optionId,
						'cost'     => $cost,
						'taxes'    => '',
						'calc_tax' => 'per_order',
					];
				}
			}
		}

		return $customRates;
	}

	/**
	 * Computes custom rate cost for carrier using cart contents.
	 *
	 * @param array     $carrierOptions Carrier options.
	 * @param float     $cartPrice Price.
	 * @param float|int $cartWeight Weight.
	 * @param bool      $isCod COD.
	 *
	 * @return int|float|null
	 */
	private function getRateCost( array $carrierOptions, float $cartPrice, $cartWeight, bool $isCod ) {
		$cost = null;
		if ( $carrierOptions['free_shipping_limit'] && $cartPrice >= $carrierOptions['free_shipping_limit'] ) {
			$cost = 0;
		} else {
			foreach ( $carrierOptions['weight_limits'] as $weightLimit ) {
				if ( $cartWeight <= $weightLimit['weight'] ) {
					$cost = $weightLimit['price'];
					break;
				}
			}
			if ( $isCod && is_numeric( $cost ) ) {
				foreach ( $carrierOptions['surcharge_limits'] as $weightLimit ) {
					if ( $cartPrice <= $weightLimit['order_price'] ) {
						$cost += $weightLimit['surcharge'];
						break;
					}
				}
			}
		}

		return $cost;
	}

	/**
	 * Get chosen shipping rate id.
	 *
	 * @return string
	 */
	private function getChosenMethod(): string {
		$chosenMethods = wc_get_chosen_shipping_method_ids();
		if ( ! empty( $chosenMethods[0] ) ) {
			return $chosenMethods[0];
		}

		return '';
	}

	/**
	 * Gets carrier id from chosen shipping method.
	 *
	 * @param string $chosenMethod Chosen shipping method.
	 *
	 * @return string|null
	 */
	public function getCarrierId( string $chosenMethod ): ?string {
		if ( ! $this->isPacketeryOrder( $chosenMethod ) ) {
			return null;
		}

		$carrierId = str_replace( self::CARRIER_PREFIX, '', $chosenMethod );
		if ( strpos( $carrierId, 'zpoint' ) === 0 ) {
			return Repository::INTERNAL_PICKUP_POINTS_ID;
		}

		return $carrierId;
	}


	/**
	 * Checks if chosen shipping method is one of packetery.
	 *
	 * @param string $chosenMethod Chosen shipping method.
	 *
	 * @return bool
	 */
	private function isPacketeryOrder( string $chosenMethod ): bool {
		return ( strpos( $chosenMethod, self::CARRIER_PREFIX ) === 0 );
	}
}
