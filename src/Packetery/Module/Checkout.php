<?php
/**
 * Packeta plugin class for checkout.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Core;
use Packetery\Core\Api\Rest\PickupPointValidateRequest;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Options\Provider;
use Packetery\Module\Order;
use Packetery\Module\Order\PickupPointValidator;
use PacketeryLatte\Engine;
use PacketeryNette\Http\Request;

/**
 * Class Checkout
 *
 * @package Packetery
 */
class Checkout {

	private const NONCE_ACTION = 'packetery_checkout';
	private const NONCE_NAME   = '_wpnonce_packetery_checkout';

	private const BUTTON_RENDERER_TABLE_ROW  = 'table-row';
	private const BUTTON_RENDERER_AFTER_RATE = 'after-rate';

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
	 * Pickup point validation API.
	 *
	 * @var PickupPointValidator
	 */
	private $pickupPointValidator;

	/**
	 * OrderFacade.
	 *
	 * @var Order\AttributeMapper
	 */
	private $mapper;

	/**
	 * RateCalculator.
	 *
	 * @var RateCalculator
	 */
	private $rateCalculator;

	/**
	 * Internal pickup points config.
	 *
	 * @var PacketaPickupPointsConfig
	 */
	private $pickupPointsConfig;

	/**
	 * Widget options builder.
	 *
	 * @var WidgetOptionsBuilder
	 */
	private $widgetOptionsBuilder;

	/**
	 * Carrier entity repository.
	 *
	 * @var Carrier\EntityRepository
	 */
	private $carrierEntityRepository;

	/**
	 * Checkout constructor.
	 *
	 * @param Engine                    $latte_engine            PacketeryLatte engine.
	 * @param Provider                  $options_provider        Options provider.
	 * @param Carrier\Repository        $carrierRepository       Carrier repository.
	 * @param Request                   $httpRequest             Http request.
	 * @param Order\Repository          $orderRepository         Order repository.
	 * @param CurrencySwitcherFacade    $currencySwitcherFacade  Currency switcher facade.
	 * @param Order\PacketAutoSubmitter $packetAutoSubmitter     Packet auto submitter.
	 * @param PickupPointValidator      $pickupPointValidator    Pickup point validation API.
	 * @param Order\AttributeMapper     $mapper                  OrderFacade.
	 * @param RateCalculator            $rateCalculator          RateCalculator.
	 * @param PacketaPickupPointsConfig $pickupPointsConfig      Internal pickup points config.
	 * @param WidgetOptionsBuilder      $widgetOptionsBuilder    Widget options builder.
	 * @param Carrier\EntityRepository  $carrierEntityRepository Carrier repository.
	 */
	public function __construct(
		Engine $latte_engine,
		Provider $options_provider,
		Carrier\Repository $carrierRepository,
		Request $httpRequest,
		Order\Repository $orderRepository,
		CurrencySwitcherFacade $currencySwitcherFacade,
		Order\PacketAutoSubmitter $packetAutoSubmitter,
		PickupPointValidator $pickupPointValidator,
		Order\AttributeMapper $mapper,
		RateCalculator $rateCalculator,
		PacketaPickupPointsConfig $pickupPointsConfig,
		WidgetOptionsBuilder $widgetOptionsBuilder,
		Carrier\EntityRepository $carrierEntityRepository
	) {
		$this->latte_engine            = $latte_engine;
		$this->options_provider        = $options_provider;
		$this->carrierRepository       = $carrierRepository;
		$this->httpRequest             = $httpRequest;
		$this->orderRepository         = $orderRepository;
		$this->currencySwitcherFacade  = $currencySwitcherFacade;
		$this->packetAutoSubmitter     = $packetAutoSubmitter;
		$this->pickupPointValidator    = $pickupPointValidator;
		$this->mapper                  = $mapper;
		$this->rateCalculator          = $rateCalculator;
		$this->pickupPointsConfig      = $pickupPointsConfig;
		$this->widgetOptionsBuilder    = $widgetOptionsBuilder;
		$this->carrierEntityRepository = $carrierEntityRepository;
	}

	/**
	 * Check if chosen shipping rate is bound with Packeta pickup points
	 *
	 * @return bool
	 */
	public function isPickupPointOrder(): bool {
		$chosenMethod = $this->getChosenMethod();
		$carrierId    = $this->getCarrierId( $chosenMethod );

		return $carrierId && $this->isPickupPointCarrier( $carrierId );
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
	 * @param \WC_Shipping_Rate|mixed $shippingRate Shipping rate.
	 */
	public function renderWidgetButtonAfterShippingRate( $shippingRate ): void {
		if ( ! $shippingRate instanceof \WC_Shipping_Rate ) {
			WcLogger::logArgumentTypeError( __METHOD__, 'shippingRate', \WC_Shipping_Rate::class, $shippingRate );
			return;
		}

		if ( ! is_checkout() ) {
			return;
		}

		if ( ! $this->isPacketeryShippingMethod( $shippingRate->get_id() ) ) {
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
	 * Creates settings for checkout script.
	 *
	 * @return array
	 */
	public function createSettings(): array {
		$carriersConfigForWidget = [];
		$carriers                = $this->carrierEntityRepository->getAllCarriersIncludingNonFeed();

		foreach ( $carriers as $carrier ) {
			$optionId     = Carrier\OptionPrefixer::getOptionId( $carrier->getId() );
			$defaultPrice = $this->getRateCost(
				Carrier\Options::createByCarrierId( $carrier->getId() ),
				$this->getCartContentsTotalIncludingTax(),
				$this->getCartWeightKg()
			);

			$carriersConfigForWidget[ $optionId ] = $this->widgetOptionsBuilder->getCarrierForCheckout(
				$carrier,
				$defaultPrice,
				$optionId
			);
		}

		return [
			/**
			 * Filter widget language in checkout.
			 *
			 * @since 1.4.2
			 */
			'language'                  => (string) apply_filters( 'packeta_widget_language', substr( get_locale(), 0, 2 ) ),
			'country'                   => $this->getCustomerCountry(),
			'weight'                    => $this->getCartWeightKg(),
			'carrierConfig'             => $carriersConfigForWidget,
			// TODO: Settings are not updated on AJAX checkout update. Needs rework due to possible checkout solutions allowing cart update.
			'isAgeVerificationRequired' => $this->isAgeVerification18PlusRequired(),
			'pickupPointAttrs'          => Order\Attribute::$pickupPointAttrs,
			'homeDeliveryAttrs'         => Order\Attribute::$homeDeliveryAttrs,
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
			[
				'fields' => array_merge(
					array_column( Order\Attribute::$pickupPointAttrs, 'name' ),
					array_column( Order\Attribute::$homeDeliveryAttrs, 'name' )
				),
			]
		);

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
	}

	/**
	 * Checks if all pickup point attributes are set, sets an error otherwise.
	 */
	public function validateCheckoutData(): void {
		$chosenShippingMethod = $this->getChosenMethod();
		WC()->session->set( PickupPointValidator::VALIDATION_HTTP_ERROR_SESSION_KEY, null );

		if ( false === $this->isPacketeryShippingMethod( $chosenShippingMethod ) ) {
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
			$error = false;
			/**
			 * Returns array always.
			 *
			 * @var array $required_attrs
			 */
			$required_attrs = array_filter(
				array_combine(
					array_column( Order\Attribute::$pickupPointAttrs, 'name' ),
					array_column( Order\Attribute::$pickupPointAttrs, 'required' )
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
			if ( $error ) {
				wc_add_notice( __( 'Pickup point is not chosen.', 'packeta' ), 'error' );
			}

			if ( ! $error && ! $this->carrierEntityRepository->isValidForCountry(
				( $post[ Order\Attribute::CARRIER_ID ] ? $post[ Order\Attribute::CARRIER_ID ] : null ),
				$this->getCustomerCountry()
			) ) {
				wc_add_notice( __( 'The selected Packeta carrier is not available for the selected delivery country.', 'packeta' ), 'error' );
				$error = true;
			}

			if ( ! $error && PickupPointValidator::IS_ACTIVE ) {
				$pickupPointId         = $post[ Order\Attribute::POINT_ID ];
				$carrierId             = ( $post[ Order\Attribute::CARRIER_ID ] ?? null );
				$carriersForValidation = $chosenShippingMethod;
				if ( '' === $carrierId ) {
					$carrierId             = Carrier\Repository::INTERNAL_PICKUP_POINTS_ID;
					$carriersForValidation = Carrier\Repository::INTERNAL_PICKUP_POINTS_ID;
				}
				$pickupPointValidationResponse = $this->pickupPointValidator->validate(
					$this->getPickupPointValidateRequest(
						$pickupPointId,
						$carrierId,
						( is_numeric( $carrierId ) ? $pickupPointId : null ),
						$carriersForValidation
					)
				);
				if ( ! $pickupPointValidationResponse->isValid() ) {
					wc_add_notice( __( 'The selected Packeta pickup point could not be validated. Please select another.', 'packeta' ), 'error' );
					foreach ( $pickupPointValidationResponse->getErrors() as $validationError ) {
						$reason = $this->pickupPointValidator->getTranslatedError()[ $validationError['code'] ];
						// translators: %s: Reason for validation failure.
						wc_add_notice( sprintf( __( 'Reason: %s', 'packeta' ), $reason ), 'error' );
					}
				}
			}
		}

		if ( $this->isHomeDeliveryOrder() ) {
			$carrierId     = $this->getCarrierId( $chosenShippingMethod );
			$optionId      = Carrier\OptionPrefixer::getOptionId( $carrierId );
			$carrierOption = get_option( $optionId );

			$addressValidation = 'none';
			if ( $carrierOption ) {
				$addressValidation = ( $carrierOption['address_validation'] ?? $addressValidation );
			}

			if (
				'required' === $addressValidation &&
				(
					! isset( $post[ Order\Attribute::ADDRESS_IS_VALIDATED ] ) ||
					'1' !== $post[ Order\Attribute::ADDRESS_IS_VALIDATED ]
				)
			) {
				wc_add_notice( __( 'Delivery address has not been verified. Verification of delivery address is required by this carrier.', 'packeta' ), 'error' );
			}
		}
	}

	/**
	 * Saves pickup point and other Packeta information to order.
	 *
	 * @param int|mixed $orderId Order id.
	 *
	 * @throws \WC_Data_Exception When invalid data are passed during shipping address update.
	 */
	public function updateOrderMeta( $orderId ): void {
		if ( ! is_int( $orderId ) ) {
			WcLogger::logArgumentTypeError( __METHOD__, 'orderId', 'int', $orderId );
			return;
		}

		$chosenMethod = $this->getChosenMethod();
		if ( false === $this->isPacketeryShippingMethod( $chosenMethod ) ) {
			return;
		}

		$post = $this->httpRequest->getPost();

		$propsToSave = [];
		// Save carrier id for home delivery (we got no id from widget).
		$carrierId = $this->getCarrierId( $chosenMethod );
		if ( empty( $post[ Order\Attribute::CARRIER_ID ] ) && $carrierId ) {
			$propsToSave[ Order\Attribute::CARRIER_ID ] = $carrierId;
		}

		$wcOrder = $this->orderRepository->getWcOrderById( $orderId );
		if ( null === $wcOrder ) {
			return;
		}

		if ( $this->isPickupPointOrder() ) {
			if ( PickupPointValidator::IS_ACTIVE ) {
				$pickupPointValidationError = WC()->session->get( PickupPointValidator::VALIDATION_HTTP_ERROR_SESSION_KEY );
				if ( null !== $pickupPointValidationError ) {
					// translators: %s: Message from downloader.
					$wcOrder->add_order_note( sprintf( __( 'The selected Packeta pickup point could not be validated, reason: %s.', 'packeta' ), $pickupPointValidationError ) );
					WC()->session->set( PickupPointValidator::VALIDATION_HTTP_ERROR_SESSION_KEY, null );
				}
			}

			foreach ( Order\Attribute::$pickupPointAttrs as $attr ) {
				$attrName = $attr['name'];
				if ( ! isset( $post[ $attrName ] ) ) {
					continue;
				}
				$attrValue = $post[ $attrName ];

				$saveMeta = true;
				if (
					( Order\Attribute::CARRIER_ID === $attrName && ! $attrValue ) ||
					( Order\Attribute::POINT_URL === $attrName && ! filter_var( $attrValue, FILTER_VALIDATE_URL ) )
				) {
					$saveMeta = false;
				}
				if ( $saveMeta ) {
					$propsToSave[ $attrName ] = $attrValue;
				}

				if ( $this->options_provider->replaceShippingAddressWithPickupPointAddress() ) {
					$this->mapper->toWcOrderShippingAddress( $wcOrder, $attrName, (string) $attrValue );
				}
			}
			$wcOrder->save();
		}

		$orderEntity = new Core\Entity\Order( (string) $orderId, $carrierId );
		if (
			isset( $post[ Order\Attribute::ADDRESS_IS_VALIDATED ] ) &&
			'1' === $post[ Order\Attribute::ADDRESS_IS_VALIDATED ] &&
			$this->isHomeDeliveryOrder()
		) {
			$validatedAddress = $this->mapper->toValidatedAddress( $post );
			$orderEntity->setDeliveryAddress( $validatedAddress );
			$orderEntity->setAddressValidated( true );
		}

		if ( 0.0 === $this->getCartWeightKg() && true === $this->options_provider->isDefaultWeightEnabled() ) {
			$orderEntity->setWeight( $this->options_provider->getDefaultWeight() + $this->options_provider->getPackagingWeight() );
		}

		$pickupPoint = $this->mapper->toOrderEntityPickupPoint( $orderEntity, $propsToSave );
		$orderEntity->setPickupPoint( $pickupPoint );

		$this->orderRepository->save( $orderEntity );
		$this->packetAutoSubmitter->handleEventAsync( Order\PacketAutoSubmitter::EVENT_ON_ORDER_CREATION_FE, $orderId );
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
		if ( false === $this->isPacketeryShippingMethod( $chosenShippingMethod ) ) {
			return;
		}

		$carrierOptions = Carrier\Options::createByOptionId( $chosenShippingMethod );
		$chosenCarrier  = $this->carrierEntityRepository->getAnyById( $this->getCarrierIdFromShippingMethod( $chosenShippingMethod ) );
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
		$availableCarriers         = $this->carrierEntityRepository->getByCountryIncludingNonFeed( $customerCountry );
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

			$optionId = Carrier\OptionPrefixer::getOptionId( $carrier->getId() );
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
				$rateId                 = ShippingMethod::PACKETERY_METHOD_ID . ':' . $optionId;
				$customRates[ $rateId ] = $this->createShippingRate( $options->getName(), $rateId, $cost );
			}
		}

		return $customRates;
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
		return $this->rateCalculator->getShippingRateCost( $options, $cartPrice, $cartWeight, $this->isFreeShippingCouponApplied() );
	}

	/**
	 * Tells if free shipping coupon is applied.
	 *
	 * @return bool
	 */
	private function isFreeShippingCouponApplied(): bool {
		return $this->rateCalculator->isFreeShippingCouponApplied( WC()->cart );
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
			return $this->removeShippingMethodPrefix( current( $postedShippingMethodArray ) );
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
			return $this->removeShippingMethodPrefix( $chosenShippingRate->get_id() );
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
	private function getCarrierId( string $chosenMethod ): ?string {
		$carrierId = $this->getCarrierIdFromShippingMethod( $chosenMethod );
		if ( null === $carrierId ) {
			return null;
		}

		if ( $this->pickupPointsConfig->isCompoundCarrierId( $carrierId ) ) {
			return Carrier\Repository::INTERNAL_PICKUP_POINTS_ID;
		}

		return $carrierId;
	}

	/**
	 * Gets feed ID or artificially created ID for internal purposes.
	 *
	 * @param string $chosenMethod Chosen method.
	 *
	 * @return string|null
	 */
	private function getCarrierIdFromShippingMethod( string $chosenMethod ): ?string {
		if ( ! $this->isPacketeryShippingMethod( $chosenMethod ) ) {
			return null;
		}

		return Carrier\OptionPrefixer::removePrefix( $chosenMethod );
	}

	/**
	 * Checks if chosen shipping method is one of packetery.
	 *
	 * @param string $chosenMethod Chosen shipping method.
	 *
	 * @return bool
	 */
	private function isPacketeryShippingMethod( string $chosenMethod ): bool {
		$optionId = $this->removeShippingMethodPrefix( $chosenMethod );

		return Carrier\OptionPrefixer::isOptionId( $optionId );
	}

	/**
	 * Gets ShippingRate's ID of extended id.
	 *
	 * @param string $chosenMethod Chosen shipping method.
	 *
	 * @return string
	 */
	private function removeShippingMethodPrefix( string $chosenMethod ): string {
		return str_replace( ShippingMethod::PACKETERY_METHOD_ID . ':', '', $chosenMethod );
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
	private function createShippingRate( string $name, string $optionId, ?float $cost ): array {
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
			foreach ( $taxClassRates as $rate ) {
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
			if ( ! isset( $cartProduct['product_id'] ) ) {
				continue;
			}
			$product            = WC()->product_factory->get_product( $cartProduct['product_id'] );
			$productCategoryIds = $product->get_category_ids();

			foreach ( $productCategoryIds as $productCategoryId ) {
				$productCategoryEntity           = ProductCategory\Entity::fromTermId( (int) $productCategoryId );
				$disallowedCategoryShippingRates = $productCategoryEntity->getDisallowedShippingRateIds();
				if ( in_array( $shippingRate, $disallowedCategoryShippingRates, true ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Filters out payment methods, that can not be used.
	 *
	 * @param array|mixed $availableGateways Available gateways.
	 *
	 * @return array|mixed
	 */
	public function filterPaymentGateways( $availableGateways ) {
		if ( ! is_array( $availableGateways ) ) {
			WcLogger::logArgumentTypeError( __METHOD__, 'availableGateways', 'array', $availableGateways );
			return $availableGateways;
		}

		if ( ! is_checkout() ) {
			return $availableGateways;
		}

		$chosenMethod = $this->calculateShipping();
		if ( ! $this->isPacketeryShippingMethod( $chosenMethod ) ) {
			return $availableGateways;
		}

		$carrier = $this->carrierEntityRepository->getAnyById( $this->getCarrierIdFromShippingMethod( $chosenMethod ) );
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

	/**
	 * Creates PickupPointValidateRequest object.
	 *
	 * @param string  $pickupPointId Pickup point id.
	 * @param ?string $carrierId Carrier id.
	 * @param ?string $pointCarrierId Carrier pickup point id.
	 * @param string  $chosenShippingMethod WC shipping method id.
	 *
	 * @return PickupPointValidateRequest
	 */
	private function getPickupPointValidateRequest(
		string $pickupPointId,
		?string $carrierId,
		?string $pointCarrierId,
		string $chosenShippingMethod
	): PickupPointValidateRequest {
		return new PickupPointValidateRequest(
			$pickupPointId,
			$carrierId,
			$pointCarrierId,
			$this->getCustomerCountry(),
			$this->getCarrierId( $chosenShippingMethod ),
			false,
			false,
			$this->getCartWeightKg(),
			$this->isAgeVerification18PlusRequired(),
			null
		);
	}

	/**
	 * Checks if chosen carrier has pickup points and sets carrier id in provided array.
	 *
	 * @param string $carrierId Carrier id.
	 *
	 * @return bool
	 */
	public function isPickupPointCarrier( string $carrierId ): bool {
		if ( Carrier\Repository::INTERNAL_PICKUP_POINTS_ID === $carrierId ) {
			return true;
		}
		if ( $this->pickupPointsConfig->isVendorCarrierId( $carrierId ) ) {
			return true;
		}

		return $this->carrierRepository->hasPickupPoints( (int) $carrierId );
	}

}
