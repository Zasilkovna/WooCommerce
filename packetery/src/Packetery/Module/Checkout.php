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
		'id'        => array(
			'name'     => Entity::META_POINT_ID,
			'required' => true,
		),
		'name'      => array(
			'name'     => Entity::META_POINT_NAME,
			'required' => true,
		),
		'city'      => array(
			'name'     => Entity::META_POINT_CITY,
			'required' => true,
		),
		'zip'       => array(
			'name'     => Entity::META_POINT_ZIP,
			'required' => true,
		),
		'street'    => array(
			'name'     => Entity::META_POINT_STREET,
			'required' => true,
		),
		'carrierId' => array(
			'name'     => Entity::META_CARRIER_ID,
			'required' => false,
		),
		'url'       => array(
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
		'isValidated' => [
			'name'                => 'packetery_address_isValidated', // Name of checkout hidden form field. Must be unique in entire form.
			'isWidgetResultField' => false, // Is attribute included in widget result address? By default it is.
		],
		'houseNumber' => [ // post type address field called 'houseNumber'.
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
	 * Gets widget carriers param.
	 *
	 * @param bool   $isPickupPoints Is context pickup point related.
	 * @param string $carrierId      Carrier id.
	 *
	 * @return string|null
	 */
	public static function getWidgetCarriersParam( bool $isPickupPoints, string $carrierId ): ?string {
		if ( $isPickupPoints ) {
			return ( is_numeric( $carrierId ) ? $carrierId : Repository::INTERNAL_PICKUP_POINTS_ID );
		}

		return null;
	}

	/**
	 * Renders main checkout script
	 */
	public function render_after_checkout_form(): void {
		$carrierConfig = [];
		$carriers      = $this->carrierRepository->getAllIncludingZpoints();

		foreach ( $carriers as $carrier ) {
			$optionId                   = self::CARRIER_PREFIX . $carrier['id'];
			$carrierConfig[ $optionId ] = [
				'id'               => $carrier['id'],
				'is_pickup_points' => $carrier['is_pickup_points'],
			];

			if ( $carrier['is_pickup_points'] ) {
				$carrierConfig[ $optionId ]['carriers'] = self::getWidgetCarriersParam( (bool) $carrier['is_pickup_points'], (string) $carrier['id'] );
			}

			if ( ! $carrier['is_pickup_points'] ) {
				$carrierOption = get_option( $optionId );

				$addressValidation = 'none';
				if ( $carrierOption ) {
					$addressValidation = ( $carrierOption['address_validation'] ?? $addressValidation );
				}

				$carrierConfig[ $optionId ]['address_validation'] = $addressValidation;
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
					'appIdentity'       => Plugin::getAppIdentity(),
					'packeteryApiKey'   => $this->options_provider->get_api_key(),
					'translations'      => [
						'choosePickupPoint'             => __( 'choosePickupPoint', 'packetery' ),
						'chooseAddress'                 => __( 'checkShippingAddress', 'packetery' ),
						'addressValidationIsOutOfOrder' => __( 'addressValidationIsOutOfOrder', 'packetery' ),
						'invalidAddressCountrySelected' => __( 'invalidAddressCountrySelected', 'packetery' ),
						'selectedShippingAddress'       => __( 'selectedShippingAddress', 'packetery' ),
						'addressIsValidated'            => __( 'addressIsValidated', 'packetery' ),
						'addressIsNotValidated'         => __( 'addressIsNotValidated', 'packetery' ),
						'addressIsNotValidatedAndRequiredByCarrier' => __( 'addressIsNotValidatedAndRequiredByCarrier', 'packetery' ),
					],
				],
			]
		);
	}

	/**
	 * Adds fields to checkout page to save the values later
	 */
	public function addPickupPointFields(): void {
		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/checkout/input_fields.latte',
			[ 'fields' => array_merge( array_column( self::$pickupPointAttrs, 'name' ), array_column( self::$homeDeliveryAttrs, 'name' ) ) ]
		);

		wp_nonce_field( self::NONCE_ACTION );
	}

	/**
	 * Checks if all pickup point attributes are set, sets an error otherwise.
	 */
	public function validateCheckoutData(): void {
		$post = $this->httpRequest->getPost();
		if ( ! wp_verify_nonce( $post['_wpnonce'], self::NONCE_ACTION ) ) {
			wp_nonce_ays( '' );
		}

		if ( $this->isPickupPointOrder() ) {
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

		if ( $this->isHomeDeliveryOrder() ) {
			$chosenMethod  = $this->getChosenMethod();
			$carrierId     = $this->getCarrierId( $chosenMethod );
			$optionId      = self::CARRIER_PREFIX . $carrierId;
			$carrierOption = get_option( $optionId );

			$addressValidation = 'none';
			if ( $carrierOption ) {
				$addressValidation = ( $carrierOption['address_validation'] ?? $addressValidation );
			}

			if (
				'required' === $addressValidation &&
				(
					! isset( $post[ self::$homeDeliveryAttrs['isValidated']['name'] ] ) ||
					'1' !== $post[ self::$homeDeliveryAttrs['isValidated']['name'] ]
				)
			) {
				wc_add_notice( __( 'shippingAddressIsNotValidated', 'packetery' ), 'error' );

				return;
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

		// Save carrier id for home delivery (we got no id from widget).
		$carrierId = $this->getCarrierId( $chosenMethod );
		if ( empty( $post[ Entity::META_CARRIER_ID ] ) && $carrierId ) {
			update_post_meta( $orderId, Entity::META_CARRIER_ID, $carrierId );
		}

		if ( ! wp_verify_nonce( $post['_wpnonce'], self::NONCE_ACTION ) ) {
			wp_nonce_ays( '' );
		}

		if ( $this->isPickupPointOrder() ) {
			foreach ( self::$pickupPointAttrs as $attr ) {
				$attrName = $attr['name'];
				if ( ! isset( $post[ $attrName ] ) ) {
					continue;
				}
				$attrValue = $post[ $attrName ];

				$saveMeta = true;
				if (
					( Entity::META_CARRIER_ID === $attrName && ! $attrValue ) ||
					( Entity::META_POINT_URL === $attrName && ! filter_var( $attrValue, FILTER_VALIDATE_URL ) )
				) {
					$saveMeta = false;
				}
				if ( $saveMeta ) {
					update_post_meta( $orderId, $attrName, $attrValue );
				}
			}
		}

		if ( $this->isHomeDeliveryOrder() ) {
			$address = [];

			foreach ( self::$homeDeliveryAttrs as $field => $attributeData ) {
				$isWidgetResultField = ( $attributeData['isWidgetResultField'] ?? true );
				if ( false === $isWidgetResultField ) {
					continue;
				}
				if ( isset( $attributeData['name'] ) && isset( $post[ $attributeData['name'] ] ) ) {
					$value             = $post[ $attributeData['name'] ];
					$address[ $field ] = $value;
				}
			}

			if (
				isset( $post[ self::$homeDeliveryAttrs['isValidated']['name'] ] ) &&
				'1' === $post[ self::$homeDeliveryAttrs['isValidated']['name'] ]
			) {
				$this->addressRepository->save( $orderId, $address ); // TODO: Think about address modifications by users.
			}
		}
	}

	/**
	 * Registers Packeta checkout hooks
	 */
	public function register_hooks(): void {
		add_action( 'woocommerce_review_order_before_payment', array( $this, 'renderWidgetButton' ) );
		add_action( 'woocommerce_after_checkout_form', array( $this, 'render_after_checkout_form' ) );
		add_action( 'woocommerce_after_order_notes', array( $this, 'addPickupPointFields' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'validateCheckoutData' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'updateOrderMeta' ) );
		add_action( 'woocommerce_review_order_before_shipping', array( $this, 'updateShippingRates' ), 10, 2 );
		add_action( 'woocommerce_cart_calculate_fees', [ $this, 'calculateFees' ] );
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
		$weight   = WC()->cart->cart_contents_weight;
		$weightKg = wc_get_weight( $weight, 'kg' );
		if ( $weightKg ) {
			$weightKg += $this->options_provider->getPackagingWeight();
		}

		return $weightKg;
	}

	/**
	 * Calculates fees.
	 *
	 * @return void
	 */
	public function calculateFees(): void {
		$chosenShippingMethod = $this->getChosenMethod();
		if ( false === $this->isPacketeryOrder( $chosenShippingMethod ) ) {
			return;
		}

		$carrierOptions = get_option( $chosenShippingMethod );
		if ( ! $carrierOptions ) {
			return;
		}

		$isCod               = false;
		$codPaymentMethod    = $this->options_provider->getCodPaymentMethod();
		$chosenPaymentMethod = WC()->session->get( 'chosen_payment_method' );
		if ( null !== $codPaymentMethod && ! empty( $chosenPaymentMethod ) && $chosenPaymentMethod === $codPaymentMethod ) {
			$isCod = true;
		}

		if ( false === $isCod ) {
			return;
		}

		$applicableSurcharge = $this->getCODSurcharge( $carrierOptions, $this->getCartPrice() );
		if ( 0 >= $applicableSurcharge ) {
			return;
		}

		$fee = [
			'id'     => 'packetery-cod-surcharge',
			'name'   => __( 'codSurcharge', 'packetery' ),
			'amount' => $applicableSurcharge,
		];

		WC()->cart->fees_api()->add_fee( $fee );
	}

	/**
	 * Gets cart price. Value is casted to float because PHPDoc is not reliable.
	 *
	 * @return float
	 */
	private function getCartPrice(): float {
		return (float) WC()->cart->get_subtotal();
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
			$optionId                    = self::CARRIER_PREFIX . $carrier->getId();
			$carrierOptions[ $optionId ] = get_option( $optionId );
		}

		$cartPrice  = $this->getCartPrice();
		$cartWeight = $this->getCartWeightKg();

		$customRates = [];
		foreach ( $carrierOptions as $optionId => $options ) {
			if ( is_array( $options ) && true === $options['active'] ) {
				$cost = $this->getRateCost( $options, $cartPrice, $cartWeight );
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
	 *
	 * @return int|float|null
	 */
	private function getRateCost( array $carrierOptions, float $cartPrice, $cartWeight ) {
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
		}

		return $cost;
	}

	/**
	 * Gets applicable COD surcharge.
	 *
	 * @param array $carrierOptions Carrier options.
	 * @param float $cartPrice      Cart price.
	 *
	 * @return float
	 */
	private function getCODSurcharge( array $carrierOptions, float $cartPrice ): float {
		if ( isset( $carrierOptions['surcharge_limits'] ) ) {
			foreach ( $carrierOptions['surcharge_limits'] as $weightLimit ) {
				if ( $cartPrice <= $weightLimit['order_price'] ) {
					return (float) $weightLimit['surcharge'];
				}
			}
		}

		if ( isset( $carrierOptions['default_COD_surcharge'] ) && is_numeric( $carrierOptions['default_COD_surcharge'] ) ) {
			return (float) $carrierOptions['default_COD_surcharge'];
		}

		return 0.0;
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
