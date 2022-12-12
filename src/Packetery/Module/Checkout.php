<?php
/**
 * Packeta plugin class for checkout.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Core;
use Packetery\Module\Options\Provider;
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
	private const NONCE_NAME    = '_wpnonce_packetery_checkout';

	const ATTR_POINT_ID     = 'packetery_point_id';
	const ATTR_POINT_NAME   = 'packetery_point_name';
	const ATTR_POINT_CITY   = 'packetery_point_city';
	const ATTR_POINT_ZIP    = 'packetery_point_zip';
	const ATTR_POINT_STREET = 'packetery_point_street';
	const ATTR_POINT_PLACE  = 'packetery_point_place'; // Business name of pickup point.
	const ATTR_CARRIER_ID   = 'packetery_carrier_id';
	const ATTR_POINT_URL    = 'packetery_point_url';

	const ATTR_ADDRESS_IS_VALIDATED = 'packetery_address_isValidated';
	const ATTR_ADDRESS_HOUSE_NUMBER = 'packetery_address_houseNumber';
	const ATTR_ADDRESS_STREET       = 'packetery_address_street';
	const ATTR_ADDRESS_CITY         = 'packetery_address_city';
	const ATTR_ADDRESS_POST_CODE    = 'packetery_address_postCode';
	const ATTR_ADDRESS_COUNTY       = 'packetery_address_county';
	const ATTR_ADDRESS_COUNTRY      = 'packetery_address_country';
	const ATTR_ADDRESS_LATITUDE     = 'packetery_address_latitude';
	const ATTR_ADDRESS_LONGITUDE    = 'packetery_address_longitude';

	const BUTTON_RENDERER_TABLE_ROW  = 'table-row';
	const BUTTON_RENDERER_AFTER_RATE = 'after-rate';

	/**
	 * Pickup point attributes configuration.
	 *
	 * @var array[]
	 */
	public static $pickupPointAttrs = array(
		'id'        => array(
			'name'     => self::ATTR_POINT_ID,
			'required' => true,
		),
		'name'      => array(
			'name'     => self::ATTR_POINT_NAME,
			'required' => true,
		),
		'city'      => array(
			'name'     => self::ATTR_POINT_CITY,
			'required' => true,
		),
		'zip'       => array(
			'name'     => self::ATTR_POINT_ZIP,
			'required' => true,
		),
		'street'    => array(
			'name'     => self::ATTR_POINT_STREET,
			'required' => true,
		),
		'place'     => array(
			'name'     => self::ATTR_POINT_PLACE,
			'required' => false,
		),
		'carrierId' => array(
			'name'     => self::ATTR_CARRIER_ID,
			'required' => false,
		),
		'url'       => array(
			'name'     => self::ATTR_POINT_URL,
			'required' => true,
		),
	);

	/**
	 * Home delivery attributes configuration.
	 *
	 * @var array[]
	 */
	public static $homeDeliveryAttrs = [
		'isValidated' => [
			'name'                => self::ATTR_ADDRESS_IS_VALIDATED, // Name of checkout hidden form field. Must be unique in entire form.
			'isWidgetResultField' => false, // Is attribute included in widget result address? By default, it is.
		],
		'houseNumber' => [ // post type address field called 'houseNumber'.
			'name' => self::ATTR_ADDRESS_HOUSE_NUMBER,
		],
		'street'      => [
			'name' => self::ATTR_ADDRESS_STREET,
		],
		'city'        => [
			'name' => self::ATTR_ADDRESS_CITY,
		],
		'postCode'    => [
			'name'              => self::ATTR_ADDRESS_POST_CODE,
			'widgetResultField' => 'postcode', // Widget returns address object containing specified field. By default, it is the array key 'postCode', but in this case it is 'postcode'.
		],
		'county'      => [
			'name' => self::ATTR_ADDRESS_COUNTY,
		],
		'country'     => [
			'name' => self::ATTR_ADDRESS_COUNTRY,
		],
		'latitude'    => [
			'name' => self::ATTR_ADDRESS_LATITUDE,
		],
		'longitude'   => [
			'name' => self::ATTR_ADDRESS_LONGITUDE,
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
	 * @var Carrier\Repository Carrier repository.
	 */
	private $carrierRepository;

	/**
	 * Http request.
	 *
	 * @var Request Http request.
	 */
	private $httpRequest;

	/**
	 * Order repository.
	 *
	 * @var Order\Repository
	 */
	private $orderRepository;

	/**
	 * Currency switcher facade.
	 *
	 * @var CurrencySwitcherFacade
	 */
	private $currencySwitcherFacade;

	/**
	 * Packet auto submitter.
	 *
	 * @var Order\PacketAutoSubmitter
	 */
	private $packetAutoSubmitter;

	/**
	 * Checkout constructor.
	 *
	 * @param Engine                    $latte_engine           PacketeryLatte engine.
	 * @param Provider                  $options_provider       Options provider.
	 * @param Carrier\Repository        $carrierRepository      Carrier repository.
	 * @param Request                   $httpRequest            Http request.
	 * @param Order\Repository          $orderRepository        Order repository.
	 * @param CurrencySwitcherFacade    $currencySwitcherFacade Currency switcher facade.
	 * @param Order\PacketAutoSubmitter $packetAutoSubmitter    Packet auto submitter.
	 */
	public function __construct(
		Engine $latte_engine,
		Provider $options_provider,
		Carrier\Repository $carrierRepository,
		Request $httpRequest,
		Order\Repository $orderRepository,
		CurrencySwitcherFacade $currencySwitcherFacade,
		Order\PacketAutoSubmitter $packetAutoSubmitter
	) {
		$this->latte_engine           = $latte_engine;
		$this->options_provider       = $options_provider;
		$this->carrierRepository      = $carrierRepository;
		$this->httpRequest            = $httpRequest;
		$this->orderRepository        = $orderRepository;
		$this->currencySwitcherFacade = $currencySwitcherFacade;
		$this->packetAutoSubmitter    = $packetAutoSubmitter;
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
	 * Render widget button table row.
	 *
	 * @return void
	 */
	public function renderWidgetButtonTableRow(): void {
		if ( ! is_checkout() ) {
			return;
		}

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/checkout/widget-button-row.latte',
			[
				'renderer'     => self::BUTTON_RENDERER_TABLE_ROW,
				'logo'         => Plugin::buildAssetUrl( 'public/packeta-symbol.png' ),
				'translations' => [
					'packeta' => __( 'Packeta', 'packeta' ),
				],
			]
		);
	}

	/**
	 * Renders widget button and information about chosen pickup point
	 *
	 * @param \WC_Shipping_Rate $shippingRate Shipping rate.
	 */
	public function renderWidgetButtonAfterShippingRate( \WC_Shipping_Rate $shippingRate ): void {
		if ( ! is_checkout() ) {
			return;
		}

		if ( ! $this->isPacketeryOrder( $shippingRate->get_id() ) ) {
			return;
		}

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/checkout/widget-button.latte',
			[
				'renderer'     => self::BUTTON_RENDERER_AFTER_RATE,
				'logo'         => Plugin::buildAssetUrl( 'public/packeta-symbol.png' ),
				'translations' => [
					'packeta' => __( 'Packeta', 'packeta' ),
				],
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
			return ( is_numeric( $carrierId ) ? $carrierId : Carrier\Repository::INTERNAL_PICKUP_POINTS_ID );
		}

		return null;
	}

	/**
	 * Creates settings for checkout script.
	 *
	 * @return array
	 */
	public function createSettings(): array {
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

		return [
			'language'                  => substr( get_locale(), 0, 2 ),
			'country'                   => $this->getCustomerCountry(),
			'weight'                    => $this->getCartWeightKg(),
			'carrierConfig'             => $carrierConfig,
			// TODO: Settings are not updated on AJAX checkout update. Needs rework due to possible checkout solutions allowing cart update.
			'isAgeVerificationRequired' => $this->isAgeVerification18PlusRequired(),
			'pickupPointAttrs'          => self::$pickupPointAttrs,
			'homeDeliveryAttrs'         => self::$homeDeliveryAttrs,
			'appIdentity'               => Plugin::getAppIdentity(),
			'packeteryApiKey'           => $this->options_provider->get_api_key(),
			'widgetAutoOpen'            => $this->options_provider->shouldWidgetOpenAutomatically(),
			'translations'              => [
				'choosePickupPoint'             => __( 'Choose pickup point', 'packeta' ),
				'chooseAddress'                 => __( 'Check shipping address', 'packeta' ),
				'addressValidationIsOutOfOrder' => __( 'Address validation is out of order', 'packeta' ),
				'invalidAddressCountrySelected' => __( 'The selected country does not correspond to the destination country.', 'packeta' ),
				'selectedShippingAddress'       => __( 'Selected shipping address', 'packeta' ),
				'addressIsValidated'            => __( 'Address is validated', 'packeta' ),
				'addressIsNotValidated'         => __( 'Delivery address has not been verified.', 'packeta' ),
				'addressIsNotValidatedAndRequiredByCarrier' => __( 'Delivery address has not been verified. Verification of delivery address is required by this carrier.', 'packeta' ),
			],
		];
	}

	/**
	 * Adds fields to checkout page to save the values later
	 */
	public function renderHiddenInputFields(): void {
		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/checkout/input_fields.latte',
			[ 'fields' => array_merge( array_column( self::$pickupPointAttrs, 'name' ), array_column( self::$homeDeliveryAttrs, 'name' ) ) ]
		);

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
	}

	/**
	 * Checks if all pickup point attributes are set, sets an error otherwise.
	 */
	public function validateCheckoutData(): void {
		$chosenShippingMethod = $this->getChosenMethod();

		if ( false === $this->isPacketeryOrder( $chosenShippingMethod ) ) {
			return;
		}

		$post = $this->httpRequest->getPost();
		if ( ! wp_verify_nonce( $post[ self::NONCE_NAME ], self::NONCE_ACTION ) ) {
			wp_nonce_ays( '' );
		}

		if ( $this->isShippingRateRestrictedByProductsCategory( $chosenShippingMethod, WC()->cart->get_cart_contents() ) ) {
			wc_add_notice( __( 'Chosen delivery method is no longer available. Please choose another delivery method.', 'packeta' ), 'error' );

			return;
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
				wc_add_notice( __( 'Pick up point is not chosen.', 'packeta' ), 'error' );
			}
		}

		if ( $this->isHomeDeliveryOrder() ) {
			$carrierId     = $this->getCarrierId( $chosenShippingMethod );
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
				wc_add_notice( __( 'Delivery address has not been verified. Verification of delivery address is required by this carrier.', 'packeta' ), 'error' );
			}
		}
	}

	/**
	 * Saves pickup point and other Packeta information to order.
	 *
	 * @param int $orderId Order id.
	 *
	 * @throws \WC_Data_Exception When invalid data are passed during shipping address update.
	 */
	public function updateOrderMeta( int $orderId ): void {
		$chosenMethod = $this->getChosenMethod();
		if ( false === $this->isPacketeryOrder( $chosenMethod ) ) {
			return;
		}

		$post = $this->httpRequest->getPost();

		$propsToSave = [];
		// Save carrier id for home delivery (we got no id from widget).
		$carrierId = $this->getCarrierId( $chosenMethod );
		if ( empty( $post[ self::ATTR_CARRIER_ID ] ) && $carrierId ) {
			$propsToSave[ self::ATTR_CARRIER_ID ] = $carrierId;
		}

		$wcOrder = wc_get_order( $orderId );
		if ( ! $wcOrder instanceof \WC_Order ) {
			return;
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
					( self::ATTR_CARRIER_ID === $attrName && ! $attrValue ) ||
					( self::ATTR_POINT_URL === $attrName && ! filter_var( $attrValue, FILTER_VALIDATE_URL ) )
				) {
					$saveMeta = false;
				}
				if ( $saveMeta ) {
					$propsToSave[ $attrName ] = $attrValue;
				}

				if ( $this->options_provider->replaceShippingAddressWithPickupPointAddress() ) {
					self::updateShippingAddressProperty( $wcOrder, $attrName, (string) $attrValue );
				}
			}
			$wcOrder->save();
		}

		$orderEntity = new Core\Entity\Order( (string) $orderId, $carrierId );
		if (
			isset( $post[ self::$homeDeliveryAttrs['isValidated']['name'] ] ) &&
			'1' === $post[ self::$homeDeliveryAttrs['isValidated']['name'] ] &&
			$this->isHomeDeliveryOrder()
		) {
			$validatedAddress = new Core\Entity\Address(
				$post[ self::$homeDeliveryAttrs['street']['name'] ],
				$post[ self::$homeDeliveryAttrs['city']['name'] ],
				$post[ self::$homeDeliveryAttrs['postCode']['name'] ]
			);
			$validatedAddress->setCounty( $post[ self::$homeDeliveryAttrs['county']['name'] ] );
			$validatedAddress->setHouseNumber( $post[ self::$homeDeliveryAttrs['houseNumber']['name'] ] );
			$validatedAddress->setLatitude( $post[ self::$homeDeliveryAttrs['latitude']['name'] ] );
			$validatedAddress->setLongitude( $post[ self::$homeDeliveryAttrs['longitude']['name'] ] );

			$orderEntity->setDeliveryAddress( $validatedAddress );
			$orderEntity->setAddressValidated( true );
		}

		if ( 0.0 === $this->getCartWeightKg() && true === $this->options_provider->isDefaultWeightEnabled() ) {
			$orderEntity->setWeight( $this->options_provider->getDefaultWeight() + $this->options_provider->getPackagingWeight() );
		}

		self::updateOrderEntityFromPropsToSave( $orderEntity, $propsToSave );
		$this->orderRepository->save( $orderEntity );
		$this->packetAutoSubmitter->handleEventAsync( Order\PacketAutoSubmitter::EVENT_ON_ORDER_CREATION_FE, $orderId );
	}

	/**
	 * Updates order entity from props to save-
	 *
	 * @param Core\Entity\Order $orderEntity Order entity.
	 * @param array             $propsToSave Props to save.
	 *
	 * @return void
	 */
	public static function updateOrderEntityFromPropsToSave( Core\Entity\Order $orderEntity, array $propsToSave ): void {
		$orderEntityPickupPoint = $orderEntity->getPickupPoint();
		if ( null === $orderEntityPickupPoint ) {
			$orderEntityPickupPoint = new Core\Entity\PickupPoint();
		}

		foreach ( $propsToSave as $attrName => $attrValue ) {
			switch ( $attrName ) {
				case self::ATTR_CARRIER_ID:
					$orderEntity->setCarrierId( $attrValue );
					break;
				case self::ATTR_POINT_ID:
					$orderEntityPickupPoint->setId( $attrValue );
					break;
				case self::ATTR_POINT_NAME:
					$orderEntityPickupPoint->setName( $attrValue );
					break;
				case self::ATTR_POINT_URL:
					$orderEntityPickupPoint->setUrl( $attrValue );
					break;
				case self::ATTR_POINT_STREET:
					$orderEntityPickupPoint->setStreet( $attrValue );
					break;
				case self::ATTR_POINT_ZIP:
					$orderEntityPickupPoint->setZip( $attrValue );
					break;
				case self::ATTR_POINT_CITY:
					$orderEntityPickupPoint->setCity( $attrValue );
					break;
			}
		}

		$orderEntity->setPickupPoint( $orderEntityPickupPoint );
	}

	/**
	 * Registers Packeta checkout hooks
	 */
	public function register_hooks(): void {
		// This action works for both classic and Divi templates.
		add_action( 'woocommerce_review_order_before_submit', [ $this, 'renderHiddenInputFields' ] );

		add_action( 'woocommerce_checkout_process', array( $this, 'validateCheckoutData' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'updateOrderMeta' ) );
		if ( ! is_admin() ) {
			add_filter( 'woocommerce_available_payment_gateways', [ $this, 'filterPaymentGateways' ] );
		}
		add_action( 'woocommerce_review_order_before_shipping', array( $this, 'updateShippingRates' ), 10, 2 );
		add_action( 'woocommerce_cart_calculate_fees', [ $this, 'calculateFees' ] );
		add_action(
			'init',
			function () {
				/**
				 * Tells if widget button table row should be used.
				 *
				 * @since 1.3.0
				 */
				if ( $this->options_provider->getCheckoutWidgetButtonLocation() === 'after_transport_methods' ) {
					add_action( 'woocommerce_review_order_after_shipping', [ $this, 'renderWidgetButtonTableRow' ] );
				} else {
					add_action( 'woocommerce_after_shipping_rate', [ $this, 'renderWidgetButtonAfterShippingRate' ] );
				}
			}
		);
	}

	/**
	 * Updates shipping rates cost based on cart properties.
	 * To test, change the shipping price during the transition from the first to the second step of the cart.
	 */
	public function updateShippingRates(): void {
		$packages = WC()->shipping()->get_packages();
		foreach ( $packages as $i => $package ) {
			WC()->session->set( 'shipping_for_package_' . $i, false );
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
	 * @return float
	 */
	public function getCartWeightKg(): float {
		$weight   = WC()->cart->cart_contents_weight;
		$weightKg = (float) wc_get_weight( $weight, 'kg' );
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

		$carrierOptions = Carrier\Options::createByOptionId( $chosenShippingMethod );
		$chosenCarrier  = $this->carrierRepository->getAnyById( $this->getExtendedBranchServiceId( $chosenShippingMethod ) );
		$maxTaxClass    = $this->getTaxClassWithMaxRate();

		if ( $carrierOptions->hasCouponFreeShippingForFeesAllowed() && $this->isFreeShippingCouponApplied() ) {
			return;
		}

		if (
			null !== $chosenCarrier &&
			$chosenCarrier->supportsAgeVerification() &&
			null !== $carrierOptions->getAgeVerificationFee() &&
			$this->isAgeVerification18PlusRequired()
		) {
			$feeAmount = $this->currencySwitcherFacade->getConvertedPrice( $carrierOptions->getAgeVerificationFee() );
			WC()->cart->fees_api()->add_fee(
				[
					'id'        => 'packetery-age-verification-fee',
					'name'      => __( 'Age verification fee', 'packeta' ),
					'amount'    => $feeAmount,
					'taxable'   => ! ( false === $maxTaxClass ),
					'tax_class' => $maxTaxClass,
				]
			);
		}

		$paymentMethod = WC()->session->get( 'chosen_payment_method' );
		if ( empty( $paymentMethod ) || false === $this->isCodPaymentMethod( $paymentMethod ) ) {
			return;
		}

		$applicableSurcharge = $this->getCODSurcharge( $carrierOptions->toArray(), $this->getCartPrice() );
		$applicableSurcharge = $this->currencySwitcherFacade->getConvertedPrice( $applicableSurcharge );
		if ( 0 >= $applicableSurcharge ) {
			return;
		}

		$fee = [
			'id'        => 'packetery-cod-surcharge',
			'name'      => __( 'COD surcharge', 'packeta' ),
			'amount'    => $applicableSurcharge,
			'taxable'   => ! ( false === $maxTaxClass ),
			'tax_class' => $maxTaxClass,
		];

		WC()->cart->fees_api()->add_fee( $fee );
	}

	/**
	 * Gets cart price. Value is cast to float because PHPDoc is not reliable.
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
		$customerCountry           = $this->getCustomerCountry();
		$availableCarriers         = $this->carrierRepository->getByCountryIncludingZpoints( $customerCountry );
		$cartProducts              = WC()->cart->get_cart_contents();
		$cartPrice                 = $this->getCartContentsTotalIncludingTax();
		$cartWeight                = $this->getCartWeightKg();
		$disallowedShippingRateIds = $this->getDisallowedShippingRateIds();
		$isAgeVerificationRequired = $this->isAgeVerification18PlusRequired();

		$customRates = [];
		foreach ( $availableCarriers as $carrier ) {
			if ( $isAgeVerificationRequired && false === $carrier->supportsAgeVerification() ) {
				continue;
			}

			$optionId = self::CARRIER_PREFIX . $carrier->getId();
			$rateId   = ShippingMethod::PACKETERY_METHOD_ID . ':' . $optionId;
			$options  = Carrier\Options::createByOptionId( $optionId );

			if ( false === $options->isActive() ) {
				continue;
			}

			if ( in_array( $optionId, $disallowedShippingRateIds, true ) ) {
				continue;
			}

			if ( $this->isShippingRateRestrictedByProductsCategory( $optionId, $cartProducts ) ) {
				continue;
			}

			$cost = $this->getRateCost( $options, $cartPrice, $cartWeight );
			if ( null !== $cost ) {
				$customRates[ $rateId ] = $this->createShippingRate( $options->getName(), $rateId, $cost );
			}
		}

		return $customRates;
	}

	/**
	 * Gets all possible shipping rates without calculated costs.
	 *
	 * @return iterable
	 */
	public function getAllShippingRates(): iterable {
		$availableCarriers = $this->carrierRepository->getAllCarriersIncludingZpoints();
		foreach ( $availableCarriers as $carrier ) {
			$carrierOptions = Carrier\Options::createByCarrierId( $carrier->getId() );
			if ( $carrierOptions->isActive() ) {
				yield $this->createShippingRate( $carrierOptions->getName(), $carrierOptions->getOptionId(), null );
			}
		}
	}

	/**
	 * Computes custom rate cost for carrier using cart contents.
	 *
	 * @param Carrier\Options $options    Carrier options.
	 * @param float           $cartPrice  Price.
	 * @param float|int       $cartWeight Weight.
	 *
	 * @return ?float
	 */
	private function getRateCost( Carrier\Options $options, float $cartPrice, $cartWeight ): ?float {
		$cost           = null;
		$carrierOptions = $options->toArray();

		foreach ( $carrierOptions['weight_limits'] as $weightLimit ) {
			if ( $cartWeight <= $weightLimit['weight'] ) {
				$cost = $weightLimit['price'];
				break;
			}
		}

		if ( null === $cost ) {
			return null;
		}

		if ( $carrierOptions['free_shipping_limit'] ) {
			$freeShippingLimit = $this->currencySwitcherFacade->getConvertedPrice( $carrierOptions['free_shipping_limit'] );
			if ( $cartPrice >= $freeShippingLimit ) {
				$cost = 0;
			}
		}

		if ( 0 !== $cost && $options->hasCouponFreeShippingActive() && $this->isFreeShippingCouponApplied() ) {
			$cost = 0;
		}

		// WooCommerce currency-switcher.com compatibility.
		return (float) $cost;
	}

	/**
	 * Tells if free shipping coupon is applied.
	 *
	 * @return bool
	 */
	private function isFreeShippingCouponApplied(): bool {
		$coupons = WC()->cart->get_coupons();
		foreach ( $coupons as $coupon ) {
			if ( $coupon->get_free_shipping() ) {
				return true;
			}
		}

		return false;
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
		$postedShippingMethodArray = $this->httpRequest->getPost( 'shipping_method' );

		if ( null !== $postedShippingMethodArray ) {
			return $this->getShortenedRateId( current( $postedShippingMethodArray ) );
		}

		return $this->calculateShipping();
	}

	/**
	 * Calculates shipping without using POST data.
	 *
	 * @return string
	 */
	private function calculateShipping(): string {
		$chosenShippingRates = WC()->cart->calculate_shipping();
		$chosenShippingRate  = array_shift( $chosenShippingRates );

		if ( $chosenShippingRate instanceof \WC_Shipping_Rate ) {
			return $this->getShortenedRateId( $chosenShippingRate->get_id() );
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
		$branchServiceId = $this->getExtendedBranchServiceId( $chosenMethod );
		if ( null === $branchServiceId ) {
			return null;
		}

		if ( strpos( $branchServiceId, 'zpoint' ) === 0 ) {
			return Carrier\Repository::INTERNAL_PICKUP_POINTS_ID;
		}

		return $branchServiceId;
	}

	/**
	 * Gets feed ID or artificially created ID for internal purposes.
	 *
	 * @param string $chosenMethod Chosen method.
	 *
	 * @return string|null
	 */
	public function getExtendedBranchServiceId( string $chosenMethod ): ?string {
		if ( ! $this->isPacketeryOrder( $chosenMethod ) ) {
			return null;
		}

		return str_replace( self::CARRIER_PREFIX, '', $chosenMethod );
	}

	/**
	 * Checks if chosen shipping method is one of packetery.
	 *
	 * @param string $chosenMethod Chosen shipping method.
	 *
	 * @return bool
	 */
	private function isPacketeryOrder( string $chosenMethod ): bool {
		$chosenMethod = $this->getShortenedRateId( $chosenMethod );
		return ( strpos( $chosenMethod, self::CARRIER_PREFIX ) === 0 );
	}

	/**
	 * Gets ShippingRate's ID of extended id.
	 *
	 * @param string $chosenMethod Chosen shipping method.
	 *
	 * @return string
	 */
	private function getShortenedRateId( string $chosenMethod ): string {
		return str_replace( ShippingMethod::PACKETERY_METHOD_ID . ':', '', $chosenMethod );
	}

	/**
	 * Update order shipping.
	 *
	 * @param \WC_Order $wcOrder       WC Order.
	 * @param string    $attributeName Attribute name.
	 * @param string    $value         Value.
	 *
	 * @return void
	 * @throws \WC_Data_Exception When shipping input is invalid.
	 */
	public static function updateShippingAddressProperty( \WC_Order $wcOrder, string $attributeName, string $value ): void {
		if ( self::ATTR_POINT_STREET === $attributeName ) {
			$wcOrder->set_shipping_address_1( $value );
			$wcOrder->set_shipping_address_2( '' );
		}
		if ( self::ATTR_POINT_PLACE === $attributeName ) {
			$wcOrder->set_shipping_company( $value );
		}
		if ( self::ATTR_POINT_CITY === $attributeName ) {
			$wcOrder->set_shipping_city( $value );
		}
		if ( self::ATTR_POINT_ZIP === $attributeName ) {
			$wcOrder->set_shipping_postcode( $value );
		}
	}

	/**
	 * Create shipping rate.
	 *
	 * @param string     $name     Name.
	 * @param string     $optionId Option ID.
	 * @param float|null $cost     Cost.
	 *
	 * @return array
	 */
	public function createShippingRate( string $name, string $optionId, ?float $cost ): array {
		return [
			'label'    => $name,
			'id'       => $optionId,
			'cost'     => $cost,
			'taxes'    => '',
			'calc_tax' => 'per_order',
		];
	}

	/**
	 * Gets disallowed shipping rate ids.
	 *
	 * @return array
	 */
	private function getDisallowedShippingRateIds(): array {
		$cartProducts = WC()->cart->get_cart();

		$arraysToMerge = [];
		foreach ( $cartProducts as $cartProduct ) {
			$productEntity = Product\Entity::fromPostId( $cartProduct['product_id'] );

			if ( false === $productEntity->isPhysical() ) {
				continue;
			}

			$arraysToMerge[] = $productEntity->getDisallowedShippingRateIds();
		}

		return array_unique( array_merge( [], ...$arraysToMerge ) );
	}

	/**
	 * Tells if age verification is required by products in cart.
	 *
	 * @return bool
	 */
	private function isAgeVerification18PlusRequired(): bool {
		$products = WC()->cart->get_cart();

		foreach ( $products as $product ) {
			$productEntity = Product\Entity::fromPostId( $product['product_id'] );
			if ( $productEntity->isPhysical() && $productEntity->isAgeVerification18PlusRequired() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns tax_class with the highest tax_rate of cart products, false if no product is taxable.
	 *
	 * @return false|string
	 */
	private function getTaxClassWithMaxRate() {
		$products   = WC()->cart->get_cart();
		$taxClasses = [];

		foreach ( $products as $cartProduct ) {
			$product = WC()->product_factory->get_product( $cartProduct['product_id'] );
			if ( $product->is_taxable() ) {
				$taxClasses[] = $product->get_tax_class();
			}
		}

		if ( empty( $taxClasses ) ) {
			return false;
		}

		$taxClasses = array_unique( $taxClasses );
		if ( 1 === count( $taxClasses ) ) {
			return $taxClasses[0];
		}

		$taxRates = [];
		$customer = WC()->cart->get_customer();
		foreach ( $taxClasses as $taxClass ) {
			$taxRates[ $taxClass ] = \WC_Tax::get_rates( $taxClass, $customer );
		}

		$maxRate        = 0;
		$resultTaxClass = false;
		foreach ( $taxRates as $taxClassName => $taxClassRates ) {
			foreach ( $taxClassRates as $rateId => $rate ) {
				if ( $rate['rate'] > $maxRate ) {
					$maxRate        = $rate['rate'];
					$resultTaxClass = $taxClassName;
				}
			}
		}

		return $resultTaxClass;
	}

	/**
	 * Tells cart contents total price including tax and discounts.
	 *
	 * @return float
	 */
	private function getCartContentsTotalIncludingTax():float {
		return (float) WC()->cart->get_cart_contents_total() + (float) WC()->cart->get_cart_contents_tax();
	}

	/**
	 * Check if given carrier is disabled in products categories in cart
	 *
	 * @param string $shippingRate Shipping rate.
	 * @param array  $cartProducts Array of cart products.
	 *
	 * @return bool
	 */
	private function isShippingRateRestrictedByProductsCategory( string $shippingRate, array $cartProducts ): bool {
		if ( ! $cartProducts ) {
			return false;
		}

		foreach ( $cartProducts as $cartProduct ) {
			$product            = WC()->product_factory->get_product( $cartProduct['product_id'] );
			$productCategoryIds = $product->get_category_ids();

			foreach ( $productCategoryIds as $productCategoryId ) {
				$productCategoryEntity           = ProductCategory\Entity::fromTermId( (int) $productCategoryId );
				$disallowedCategoryShippingRates = $productCategoryEntity->getDisallowedShippingRates();

				if ( in_array( $shippingRate, array_keys( $disallowedCategoryShippingRates ), true ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Filters out payment methods, that can not be used.
	 *
	 * @param array $availableGateways Available gateways.
	 *
	 * @return array
	 */
	public function filterPaymentGateways( array $availableGateways ): array {
		if ( ! is_checkout() ) {
			return $availableGateways;
		}

		$chosenMethod = $this->calculateShipping();
		if ( ! $this->isPacketeryOrder( $chosenMethod ) ) {
			return $availableGateways;
		}

		$carrier = $this->carrierRepository->getAnyById( $this->getExtendedBranchServiceId( $chosenMethod ) );
		if ( null === $carrier ) {
			return $availableGateways;
		}

		foreach ( $availableGateways as $key => $availableGateway ) {
			if (
				$this->isCodPaymentMethod( $availableGateway->id ) &&
				! $carrier->supportsCod()
			) {
				unset( $availableGateways[ $key ] );
			}
		}

		return $availableGateways;
	}

	/**
	 * Checks if payment method is a COD one.
	 *
	 * @param string $paymentMethod Payment method.
	 *
	 * @return bool
	 */
	private function isCodPaymentMethod( string $paymentMethod ): bool {
		$codPaymentMethod = $this->options_provider->getCodPaymentMethod();

		return ( null !== $codPaymentMethod && ! empty( $paymentMethod ) && $paymentMethod === $codPaymentMethod );
	}

}
