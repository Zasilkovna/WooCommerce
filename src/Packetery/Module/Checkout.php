<?php
/**
 * Packeta plugin class for checkout.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use DateTime;
use Packetery\Core;
use Packetery\Core\Api\Rest\PickupPointValidateRequest;
use Packetery\Core\Entity;
use Packetery\Latte\Engine;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\OptionPrefixer;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\PickupPointValidator;
use Packetery\Module\Payment\PaymentHelper;
use Packetery\Module\Product\ProductEntityFactory;
use Packetery\Module\ProductCategory\ProductCategoryEntityFactory;
use Packetery\Nette\Http\Request;
use WC_Logger;
use WC_Tax;

/**
 * Class Checkout
 *
 * @package Packetery
 */
class Checkout {

	private const BUTTON_RENDERER_TABLE_ROW     = 'table-row';
	private const BUTTON_RENDERER_AFTER_RATE    = 'after-rate';
	public const TRANSIENT_CHECKOUT_DATA_PREFIX = 'packeta_checkout_data_';

	/**
	 * WpAdapter.
	 *
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * WcAdapter.
	 *
	 * @var WcAdapter
	 */
	private $wcAdapter;

	/**
	 * Product entity factory.
	 *
	 * @var ProductEntityFactory
	 */
	private $productEntiyFactory;

	/**
	 * Product category entity factory.
	 *
	 * @var ProductCategoryEntityFactory
	 */
	private $productCategoryEntiyFactory;

	/**
	 * Carrier options factory.
	 *
	 * @var CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/**
	 * PacketeryLatte engine
	 *
	 * @var Engine
	 */
	private $latte_engine;

	/**
	 * Options provider.
	 *
	 * @var OptionsProvider Options provider.
	 */
	private $optionsProvider;

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
	 * API router.
	 *
	 * @var Api\Internal\CheckoutRouter
	 */
	private $apiRouter;

	/**
	 *  Car delivery config.
	 *
	 * @var CarDeliveryConfig
	 */
	private $carDeliveryConfig;

	/**
	 * Payment helper.
	 *
	 * @var PaymentHelper
	 */
	private $paymentHelper;

	/**
	 * Checkout constructor.
	 *
	 * @param WpAdapter                    $wpAdapter               WpAdapter.
	 * @param WcAdapter                    $wcAdapter               WcAdapter.
	 * @param ProductEntityFactory         $productEntityFactory    Product entity factory.
	 * @param ProductCategoryEntityFactory $productCategoryEntityFactory    Product category entity factory.
	 * @param CarrierOptionsFactory        $carrierOptionsFactory   Carrier options factory.
	 * @param Engine                       $latte_engine            PacketeryLatte engine.
	 * @param OptionsProvider              $optionsProvider         Options provider.
	 * @param Carrier\Repository           $carrierRepository       Carrier repository.
	 * @param Request                      $httpRequest             Http request.
	 * @param Order\Repository             $orderRepository         Order repository.
	 * @param CurrencySwitcherFacade       $currencySwitcherFacade  Currency switcher facade.
	 * @param Order\PacketAutoSubmitter    $packetAutoSubmitter     Packet auto submitter.
	 * @param PickupPointValidator         $pickupPointValidator    Pickup point validation API.
	 * @param Order\AttributeMapper        $mapper                  OrderFacade.
	 * @param RateCalculator               $rateCalculator          RateCalculator.
	 * @param PacketaPickupPointsConfig    $pickupPointsConfig      Internal pickup points config.
	 * @param WidgetOptionsBuilder         $widgetOptionsBuilder    Widget options builder.
	 * @param Carrier\EntityRepository     $carrierEntityRepository Carrier repository.
	 * @param Api\Internal\CheckoutRouter  $apiRouter               API router.
	 * @param CarDeliveryConfig            $carDeliveryConfig       Car delivery config.
	 * @param PaymentHelper                $paymentHelper           Payment helper.
	 */
	public function __construct(
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter,
		ProductEntityFactory $productEntityFactory,
		ProductCategoryEntityFactory $productCategoryEntityFactory,
		CarrierOptionsFactory $carrierOptionsFactory,
		Engine $latte_engine,
		OptionsProvider $optionsProvider,
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
		Carrier\EntityRepository $carrierEntityRepository,
		Api\Internal\CheckoutRouter $apiRouter,
		CarDeliveryConfig $carDeliveryConfig,
		PaymentHelper $paymentHelper
	) {
		$this->wpAdapter                   = $wpAdapter;
		$this->wcAdapter                   = $wcAdapter;
		$this->productEntiyFactory         = $productEntityFactory;
		$this->productCategoryEntiyFactory = $productCategoryEntityFactory;
		$this->carrierOptionsFactory       = $carrierOptionsFactory;
		$this->latte_engine                = $latte_engine;
		$this->optionsProvider             = $optionsProvider;
		$this->carrierRepository           = $carrierRepository;
		$this->httpRequest                 = $httpRequest;
		$this->orderRepository             = $orderRepository;
		$this->currencySwitcherFacade      = $currencySwitcherFacade;
		$this->packetAutoSubmitter         = $packetAutoSubmitter;
		$this->pickupPointValidator        = $pickupPointValidator;
		$this->mapper                      = $mapper;
		$this->rateCalculator              = $rateCalculator;
		$this->pickupPointsConfig          = $pickupPointsConfig;
		$this->widgetOptionsBuilder        = $widgetOptionsBuilder;
		$this->carrierEntityRepository     = $carrierEntityRepository;
		$this->apiRouter                   = $apiRouter;
		$this->carDeliveryConfig           = $carDeliveryConfig;
		$this->paymentHelper               = $paymentHelper;
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

		return $carrierId && $this->carrierEntityRepository->isHomeDeliveryCarrier( $carrierId );
	}

	/**
	 * Check if chosen shipping rate is bound with Packeta car delivery
	 *
	 * @return bool
	 */
	public function isCarDeliveryOrder(): bool {
		$chosenMethod = $this->getChosenMethod();
		$carrierId    = $this->getCarrierId( $chosenMethod );

		return $carrierId && $this->carDeliveryConfig->isCarDeliveryCarrier( $carrierId );
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
				'logo'         => Plugin::buildAssetUrl( 'public/images/packeta-symbol.png' ),
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

		if ( ! $this->isPacketeryShippingMethod( $shippingRate->get_id() ) ) {
			return;
		}

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/checkout/widget-button.latte',
			[
				'renderer'     => self::BUTTON_RENDERER_AFTER_RATE,
				'logo'         => Plugin::buildAssetUrl( 'public/images/packeta-symbol.png' ),
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
		if ( ! ( WC()->cart instanceof \WC_Cart ) ) {
			return [];
		}

		$carriersConfigForWidget = [];
		$carriers                = $this->carrierEntityRepository->getAllCarriersIncludingNonFeed();

		foreach ( $carriers as $carrier ) {
			$optionId = Carrier\OptionPrefixer::getOptionId( $carrier->getId() );

			$carriersConfigForWidget[ $optionId ] = $this->widgetOptionsBuilder->getCarrierForCheckout(
				$carrier,
				$optionId
			);
		}

		/**
		 * Filter widget weight in checkout.
		 *
		 * @since 1.6.3
		 */
		$widgetWeight = (float) apply_filters( 'packeta_widget_weight', $this->getCartWeightKg() );

		return [
			/**
			 * Filter widget language in checkout.
			 *
			 * @since 1.4.2
			 */
			'language'                   => (string) apply_filters( 'packeta_widget_language', substr( get_locale(), 0, 2 ) ),
			'logo'                       => Plugin::buildAssetUrl( 'public/images/packeta-symbol.png' ),
			'country'                    => $this->getCustomerCountry(),
			'weight'                     => $widgetWeight,
			'carrierConfig'              => $carriersConfigForWidget,
			'isCarDeliverySampleEnabled' => $this->carDeliveryConfig->isSampleEnabled(),
			// TODO: Settings are not updated on AJAX checkout update. Needs rework due to possible checkout solutions allowing cart update.
			'isAgeVerificationRequired'  => $this->isAgeVerification18PlusRequired(),
			'pickupPointAttrs'           => Order\Attribute::$pickupPointAttrs,
			'homeDeliveryAttrs'          => Order\Attribute::$homeDeliveryAttrs,
			'carDeliveryAttrs'           => Order\Attribute::$carDeliveryAttrs,
			'carDeliveryCarriers'        => Entity\Carrier::CAR_DELIVERY_CARRIERS,
			'expeditionDay'              => $this->getExpeditionDay(),
			'appIdentity'                => Plugin::getAppIdentity(),
			'packeteryApiKey'            => $this->optionsProvider->get_api_key(),
			'widgetAutoOpen'             => $this->optionsProvider->shouldWidgetOpenAutomatically(),
			'saveSelectedPickupPointUrl' => $this->apiRouter->getSaveSelectedPickupPointUrl(),
			'saveValidatedAddressUrl'    => $this->apiRouter->getSaveValidatedAddressUrl(),
			'saveCarDeliveryDetailsUrl'  => $this->apiRouter->getSaveCarDeliveryDetailsUrl(),
			'removeSavedDataUrl'         => $this->apiRouter->getRemoveSavedDataUrl(),
			'adminAjaxUrl'               => admin_url( 'admin-ajax.php' ),
			'nonce'                      => wp_create_nonce( 'wp_rest' ),
			'savedData'                  => get_transient( $this->getTransientNamePacketaCheckoutData() ),
			'translations'               => [
				'packeta'                       => __( 'Packeta', 'packeta' ),
				'choosePickupPoint'             => __( 'Choose pickup point', 'packeta' ),
				'pickupPointNotChosen'          => __( 'Pickup point is not chosen.', 'packeta' ),
				'placeholderText'               => __( 'Loading Packeta widget button...', 'packeta' ),
				'chooseAddress'                 => __( 'Choose delivery address', 'packeta' ),
				'addressValidationIsOutOfOrder' => __( 'Address validation is out of order', 'packeta' ),
				'invalidAddressCountrySelected' => __( 'The selected country does not correspond to the destination country.', 'packeta' ),
				'deliveryAddressNotification'   => __( 'The order will be delivered to the address:', 'packeta' ),
				'addressIsNotValidatedAndRequiredByCarrier' => __( 'Delivery address has not been chosen. Choosing a delivery address using the widget is required by this carrier.', 'packeta' ),
			],
		];
	}

	/**
	 * Used to provide additional settings for blocks checkout.
	 *
	 * @return void
	 */
	public function createSettingsAjax(): void {
		$settings = [];
		if ( WC()->cart instanceof \WC_Cart ) {
			$settings['isAgeVerificationRequired'] = $this->isAgeVerification18PlusRequired();
		}

		wp_send_json( $settings );
	}

	/**
	 * Adds fields to the checkout page to save the values later
	 */
	public function renderHiddenInputFields(): void {
		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/checkout/input_fields.latte',
			[
				'fields' => array_unique(
					array_merge(
						array_column( Order\Attribute::$pickupPointAttrs, 'name' ),
						array_column( Order\Attribute::$homeDeliveryAttrs, 'name' ),
						array_column( Order\Attribute::$carDeliveryAttrs, 'name' )
					)
				),
			]
		);
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

		$checkoutData = $this->getPostDataIncludingStoredData( $chosenShippingMethod );

		if ( $this->isShippingRateRestrictedByProductsCategory( $chosenShippingMethod, $this->wcAdapter->cartGetCartContents() ) ) {
			wc_add_notice( __( 'Chosen delivery method is no longer available. Please choose another delivery method.', 'packeta' ), 'error' );

			return;
		}

		// Cannot be null because of previous condition.
		$carrierId      = $this->getCarrierId( $chosenShippingMethod );
		$carrierOptions = $this->carrierOptionsFactory->createByCarrierId( $carrierId );
		$paymentMethod  = $this->getChosenPaymentMethod();

		if ( null !== $paymentMethod && $carrierOptions->hasCheckoutPaymentMethodDisallowed( $paymentMethod ) ) {
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
				if ( isset( $checkoutData[ $attr ] ) ) {
					$attr_value = $checkoutData[ $attr ];
				}
				if ( ! $attr_value ) {
					$error = true;
				}
			}
			if ( $error ) {
				wc_add_notice( __( 'Pickup point is not chosen.', 'packeta' ), 'error' );
			}

			if (
				! $error &&
				! $this->carrierEntityRepository->isValidForCountry(
					$carrierId,
					$this->getCustomerCountry()
				)
			) {
				wc_add_notice( __( 'The selected Packeta carrier is not available for the selected delivery country.', 'packeta' ), 'error' );
				$error = true;
			}

			if ( ! $error && PickupPointValidator::IS_ACTIVE ) {
				$pickupPointId         = $checkoutData[ Order\Attribute::POINT_ID ];
				$carriersForValidation = $chosenShippingMethod;
				if ( '' === $carrierId ) {
					$carrierId             = Entity\Carrier::INTERNAL_PICKUP_POINTS_ID;
					$carriersForValidation = Entity\Carrier::INTERNAL_PICKUP_POINTS_ID;
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
			$optionId      = Carrier\OptionPrefixer::getOptionId( $carrierId );
			$carrierOption = get_option( $optionId );

			$addressValidation = 'none';
			if ( $carrierOption ) {
				$addressValidation = ( $carrierOption['address_validation'] ?? $addressValidation );
			}

			if (
				'required' === $addressValidation &&
				(
					! isset( $checkoutData[ Order\Attribute::ADDRESS_IS_VALIDATED ] ) ||
					'1' !== $checkoutData[ Order\Attribute::ADDRESS_IS_VALIDATED ]
				)
			) {
				wc_add_notice( __( 'Delivery address has not been verified. Verification of delivery address is required by this carrier.', 'packeta' ), 'error' );
			}
		}

		if ( empty( $checkoutData[ Order\Attribute::CAR_DELIVERY_ID ] ) && $this->isCarDeliveryOrder() ) {
			wc_add_notice( __( 'Delivery address has not been verified. Verification of delivery address is required by this carrier.', 'packeta' ), 'error' );
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
		$wcOrder = $this->orderRepository->getWcOrderById( $orderId );
		if ( null === $wcOrder ) {
			return;
		}

		$this->updateOrderMetaBlocks( $wcOrder );
	}

	/**
	 * Saves pickup point and other Packeta information to order.
	 *
	 * @param \WC_Order $wcOrder Order id.
	 *
	 * @throws \WC_Data_Exception When invalid data are passed during shipping address update.
	 */
	public function updateOrderMetaBlocks( \WC_Order $wcOrder ): void {
		$chosenMethod = $this->getChosenMethod();
		if ( false === $this->isPacketeryShippingMethod( $chosenMethod ) ) {
			return;
		}

		$checkoutData           = $this->getPostDataIncludingStoredData( $chosenMethod, $wcOrder->get_id() );
		$propsToSave            = [];
		$carrierId              = $this->getCarrierId( $chosenMethod );
		$orderHasUnsavedChanges = false;

		$propsToSave[ Order\Attribute::CARRIER_ID ] = $carrierId;

		if ( $this->isPickupPointOrder() ) {
			if ( PickupPointValidator::IS_ACTIVE ) {
				$pickupPointValidationError = WC()->session->get( PickupPointValidator::VALIDATION_HTTP_ERROR_SESSION_KEY );
				if ( null !== $pickupPointValidationError ) {
					// translators: %s: Message from downloader.
					$wcOrder->add_order_note( sprintf( __( 'The selected Packeta pickup point could not be validated, reason: %s.', 'packeta' ), $pickupPointValidationError ) );
					WC()->session->set( PickupPointValidator::VALIDATION_HTTP_ERROR_SESSION_KEY, null );
				}
			}

			if ( empty( $checkoutData ) ) {
				return;
			}
			foreach ( Order\Attribute::$pickupPointAttrs as $attr ) {
				$attrName = $attr['name'];
				if ( ! isset( $checkoutData[ $attrName ] ) ) {
					continue;
				}
				$attrValue = $checkoutData[ $attrName ];

				$saveMeta = true;
				if (
					Order\Attribute::CARRIER_ID === $attrName ||
					( Order\Attribute::POINT_URL === $attrName && ! filter_var( $attrValue, FILTER_VALIDATE_URL ) )
				) {
					$saveMeta = false;
				}
				if ( $saveMeta ) {
					$propsToSave[ $attrName ] = $attrValue;
				}

				if ( $this->optionsProvider->replaceShippingAddressWithPickupPointAddress() ) {
					$this->mapper->toWcOrderShippingAddress( $wcOrder, $attrName, (string) $attrValue );
				}
			}
			$orderHasUnsavedChanges = true;
		}

		$orderEntity = new Core\Entity\Order( (string) $wcOrder->get_id(), $this->carrierEntityRepository->getAnyById( $carrierId ) );
		if (
			isset( $checkoutData[ Order\Attribute::ADDRESS_IS_VALIDATED ] ) &&
			'1' === $checkoutData[ Order\Attribute::ADDRESS_IS_VALIDATED ] &&
			$this->isHomeDeliveryOrder()
		) {
			$validatedAddress = $this->mapper->toValidatedAddress( $checkoutData );
			$orderEntity->setDeliveryAddress( $validatedAddress );
			$orderEntity->setAddressValidated( true );
			if ( $this->areBlocksUsedInCheckout() ) {
				$this->mapper->validatedAddressToWcOrderShippingAddress( $wcOrder, $checkoutData );
				$orderHasUnsavedChanges = true;
			}
		}

		if ( $orderHasUnsavedChanges ) {
			$wcOrder->save();
		}

		if ( ! empty( $checkoutData ) && $this->isCarDeliveryOrder() ) {
			$address = $this->mapper->toCarDeliveryAddress( $checkoutData );
			$orderEntity->setDeliveryAddress( $address );
			$orderEntity->setAddressValidated( true );
			$orderEntity->setCarDeliveryId( $checkoutData[ Order\Attribute::CAR_DELIVERY_ID ] );
		}

		if ( 0.0 === $this->getCartWeightKg() && true === $this->optionsProvider->isDefaultWeightEnabled() ) {
			$orderEntity->setWeight( $this->optionsProvider->getDefaultWeight() + $this->optionsProvider->getPackagingWeight() );
		}

		$carrierEntity = $this->carrierEntityRepository->getAnyById( $carrierId );
		if (
			null !== $carrierEntity &&
			true === $carrierEntity->requiresSize() &&
			true === $this->optionsProvider->isDefaultDimensionsEnabled()
		) {
			$size = new Entity\Size(
				$this->optionsProvider->getDefaultLength(),
				$this->optionsProvider->getDefaultWidth(),
				$this->optionsProvider->getDefaultHeight()
			);

			$orderEntity->setSize( $size );
		}

		$pickupPoint = $this->mapper->toOrderEntityPickupPoint( $orderEntity, $propsToSave );
		$orderEntity->setPickupPoint( $pickupPoint );

		delete_transient( $this->getTransientNamePacketaCheckoutData() );
		$this->orderRepository->save( $orderEntity );
		$this->packetAutoSubmitter->handleEventAsync( Order\PacketAutoSubmitter::EVENT_ON_ORDER_CREATION_FE, $wcOrder->get_id() );
	}

	/**
	 * Checks if Blocks are used in checkout.
	 *
	 * @return bool
	 */
	public function areBlocksUsedInCheckout(): bool {
		$checkoutDetection = $this->optionsProvider->getCheckoutDetection();

		if ( OptionsProvider::BLOCK_CHECKOUT_DETECTION === $checkoutDetection ) {
			return true;
		}

		if ( OptionsProvider::CLASSIC_CHECKOUT_DETECTION === $checkoutDetection ) {
			return false;
		}

		if (
			$this->wpAdapter->hasBlock(
				'woocommerce/checkout',
				$this->wpAdapter->getPostField(
					'post_content',
					$this->wcAdapter->getPageId( 'checkout' )
				)
			) ) {
			return true;
		}

		return false;
	}

	/**
	 * Registers Packeta checkout hooks
	 */
	public function register_hooks(): void {
		// This action works for both classic and Divi templates.
		add_action( 'woocommerce_review_order_before_submit', [ $this, 'renderHiddenInputFields' ] );

		add_action( 'woocommerce_checkout_process', array( $this, 'validateCheckoutData' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'updateOrderMeta' ) );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'updateOrderMetaBlocks' ) );
		if ( ! is_admin() ) {
			add_filter( 'woocommerce_available_payment_gateways', [ $this, 'filterPaymentGateways' ] );
		}
		add_action( 'woocommerce_review_order_before_shipping', array( $this, 'updateShippingRates' ), 10, 2 );
		add_filter( 'woocommerce_cart_shipping_packages', [ $this, 'updateShippingPackages' ] );
		add_action( 'woocommerce_cart_calculate_fees', [ $this, 'calculateFees' ] );
		add_action(
			'init',
			function () {
				/**
				 * Tells if widget button table row should be used.
				 *
				 * @since 1.3.0
				 */
				if ( $this->optionsProvider->getCheckoutWidgetButtonLocation() === 'after_transport_methods' ) {
					add_action( 'woocommerce_review_order_after_shipping', [ $this, 'renderWidgetButtonTableRow' ] );
				} else {
					add_action( 'woocommerce_after_shipping_rate', [ $this, 'renderWidgetButtonAfterShippingRate' ] );
				}
			}
		);

		add_action( 'woocommerce_review_order_after_shipping', [ $this, 'renderEstimatedDeliveryDateSection' ] );
	}

	/**
	 * Shows an estimated delivery date for Car Delivery.
	 *
	 * @return void
	 */
	public function renderEstimatedDeliveryDateSection(): void {
		if ( ! is_checkout() ) {
			return;
		}

		if ( ! $this->isCarDeliveryOrder() ) {
			return;
		}

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/checkout/car-delivery-estimated-delivery-date.latte'
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
	 * Updates shipping packages to make WooCommerce caching system work correctly.
	 * Package values are used in WooCommerce method \WC_Shipping::calculate_shipping_for_package().
	 * In order to generate package cache hash correctly by WooCommerce
	 * the package must contain all relevant information related to pricing.
	 *
	 * @param array $packages Packages.
	 *
	 * @return array
	 */
	public function updateShippingPackages( array $packages ): array {
		foreach ( $packages as &$package ) {
			$package['packetery_payment_method'] = $this->getChosenPaymentMethod();
		}

		return $packages;
	}

	/**
	 * Gets customer country from WC cart.
	 *
	 * @return string
	 */
	public function getCustomerCountry(): string {
		$country = strtolower( $this->wcAdapter->customerGetShippingCountry() );
		if ( ! $country ) {
			$country = strtolower( $this->wcAdapter->customerGetBillingCountry() );
		}

		return $country;
	}

	/**
	 * Gets cart contents weight in kg.
	 *
	 * @return float
	 */
	public function getCartWeightKg(): float {
		if ( ! $this->wpAdapter->didAction( 'wp_loaded' ) ) {
			return 0.0;
		}

		$weight   = $this->wcAdapter->cartGetCartContentsWeight();
		$weightKg = $this->wcAdapter->getWeight( $weight, 'kg' );
		if ( $weightKg ) {
			$weightKg += $this->optionsProvider->getPackagingWeight();
		}

		return $weightKg;
	}

	/**
	 * Gets total cart product value.
	 *
	 * @return float
	 */
	public function getTotalCartProductValue(): float {
		$totalProductPrice = 0.0;

		foreach ( $this->wcAdapter->cartGetCartContent() as $cartItem ) {
			$totalProductPrice += (float) $cartItem['data']->get_price( 'raw' ) * $cartItem['quantity'];
		}

		return $totalProductPrice;
	}

	/**
	 * Calculates tax exclusive fee amount.
	 *
	 * @param float  $taxInclusiveFeeAmount Tax inclusive fee amount.
	 * @param string $taxClass              Related tax class.
	 *
	 * @return float
	 */
	private function calcTaxExclusiveFeeAmount( float $taxInclusiveFeeAmount, string $taxClass ): float {
		return $taxInclusiveFeeAmount - array_sum( WC_Tax::calc_tax( $taxInclusiveFeeAmount, WC_Tax::get_rates( $taxClass ), true ) );
	}

	/**
	 * Calculates fees.
	 *
	 * @return void
	 */
	public function calculateFees(): void {
		$chosenShippingMethod = $this->calculateShipping();
		if ( false === $this->isPacketeryShippingMethod( $chosenShippingMethod ) ) {
			return;
		}

		$carrierOptions = $this->carrierOptionsFactory->createByOptionId( $chosenShippingMethod );
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
			if ( false !== $maxTaxClass && $feeAmount > 0 && $this->optionsProvider->arePricesTaxInclusive() ) {
				$feeAmount = $this->calcTaxExclusiveFeeAmount( $feeAmount, $maxTaxClass );
			}

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

		$paymentMethod = $this->getChosenPaymentMethod();
		if ( empty( $paymentMethod ) || false === $this->paymentHelper->isCodPaymentMethod( $paymentMethod ) ) {
			return;
		}

		$applicableSurcharge = $this->getCODSurcharge( $carrierOptions->toArray(), $this->getCartPrice() );
		$applicableSurcharge = $this->currencySwitcherFacade->getConvertedPrice( $applicableSurcharge );
		if ( 0 >= $applicableSurcharge ) {
			return;
		}

		if ( false !== $maxTaxClass && $this->optionsProvider->arePricesTaxInclusive() ) {
			$applicableSurcharge = $this->calcTaxExclusiveFeeAmount( $applicableSurcharge, $maxTaxClass );
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
	 * @param array|null $allowedCarrierNames List of allowed carrier names.
	 *
	 * @return array
	 */
	public function getShippingRates( ?array $allowedCarrierNames ): array {
		$customerCountry           = $this->getCustomerCountry();
		$availableCarriers         = $this->carrierEntityRepository->getByCountryIncludingNonFeed( $customerCountry );
		$cartProducts              = $this->wcAdapter->cartGetCartContents();
		$cartPrice                 = $this->getCartContentsTotalIncludingTax();
		$cartWeight                = $this->getCartWeightKg();
		$totalCartProductValue     = $this->getTotalCartProductValue();
		$disallowedShippingRateIds = $this->getDisallowedShippingRateIds();
		$isAgeVerificationRequired = $this->isAgeVerification18PlusRequired();

		$customRates = [];
		foreach ( $availableCarriers as $carrier ) {
			if ( $isAgeVerificationRequired && false === $carrier->supportsAgeVerification() ) {
				continue;
			}

			if ( null !== $allowedCarrierNames && ! array_key_exists( $carrier->getId(), $allowedCarrierNames ) ) {
				continue;
			}

			$optionId    = Carrier\OptionPrefixer::getOptionId( $carrier->getId() );
			$options     = $this->carrierOptionsFactory->createByOptionId( $optionId );
			$carrierName = $options->getName();
			if ( null !== $allowedCarrierNames ) {
				$carrierName = $allowedCarrierNames[ $carrier->getId() ];
			}

			if ( null === $allowedCarrierNames && false === $options->isActive() ) {
				continue;
			}

			if ( $carrier->isCarDelivery() && $this->carDeliveryConfig->isDisabled() ) {
				continue;
			}

			if ( in_array( $optionId, $disallowedShippingRateIds, true ) ) {
				continue;
			}

			if ( $this->isShippingRateRestrictedByProductsCategory( $optionId, $cartProducts ) ) {
				continue;
			}

			$cost = $this->getRateCost( $options, $cartPrice, $totalCartProductValue, $cartWeight );
			if ( null !== $cost ) {
				$carrierName = $this->getFormattedShippingMethodName( $carrierName, $cost );
				$rateId      = ShippingMethod::PACKETERY_METHOD_ID . ':' . $optionId;
				$taxes       = null;

				if ( $cost > 0 && $this->optionsProvider->arePricesTaxInclusive() ) {
					$rates            = $this->wcAdapter->taxGetShippingTaxRates();
					$taxes            = $this->wcAdapter->taxCalcInclusiveTax( $cost, $rates );
					$taxExclusiveCost = $cost - array_sum( $taxes );
					/**
					 * Filters shipping taxes.
					 *
					 * @since 1.6.5
					 *
					 * @param array $taxes            Taxes.
					 * @param float $taxExclusiveCost Tax exclusive cost.
					 * @param array $rates            Rates.
					 */
					$taxes = $this->wpAdapter->applyFilters( 'woocommerce_calc_shipping_tax', $taxes, $taxExclusiveCost, $rates );
					if ( ! is_array( $taxes ) ) {
						$taxes = [];
					}

					$cost -= array_sum( $taxes );
				}

				$customRates[ $rateId ] = $this->createShippingRate( $carrierName, $rateId, $cost, $taxes );
			}
		}

		return $customRates;
	}

	/**
	 * Computes custom rate cost for carrier using cart contents.
	 *
	 * @param Carrier\Options $options    Carrier options.
	 * @param float           $cartPrice  Price.
	 * @param float           $totalCartProductValue Total cart product value.
	 * @param float|int       $cartWeight Weight.
	 *
	 * @return ?float
	 */
	private function getRateCost( Carrier\Options $options, float $cartPrice, float $totalCartProductValue, $cartWeight ): ?float {
		return $this->rateCalculator->getShippingRateCost( $options, $cartPrice, $totalCartProductValue, $cartWeight, $this->isFreeShippingCouponApplied() );
	}

	/**
	 * Returns the shipping method name by price.
	 *
	 * @param string $name Shipping Rate Name.
	 * @param float  $cost Shipping Rate Cost.
	 * @return string
	 */
	private function getFormattedShippingMethodName( string $name, float $cost ): string {
		if ( 0.0 === $cost && $this->optionsProvider->isFreeShippingShown() ) {
			return sprintf( '%s: %s', $name, __( 'Free', 'packeta' ) );
		}

		return $name;
	}

	/**
	 * Tells if free shipping coupon is applied.
	 *
	 * @return bool
	 */
	private function isFreeShippingCouponApplied(): bool {
		return $this->rateCalculator->isFreeShippingCouponApplied( $this->wcAdapter->cart() );
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
	 * Calculates and returns Expedition Day
	 *
	 * @return string
	 */
	private function getExpeditionDay(): ?string {
		$chosenShippingMethod = $this->getChosenMethodFromSession();
		$carrierId            = OptionPrefixer::removePrefix( $chosenShippingMethod );
		if ( false === $this->carDeliveryConfig->isCarDeliveryCarrier( $carrierId ) ) {
			return null;
		}

		$carrierOptions = $this->carrierOptionsFactory->createByOptionId( $chosenShippingMethod )->toArray();
		$today          = new DateTime();
		$processingDays = $carrierOptions['days_until_shipping'];
		$cutoffTime     = $carrierOptions['shipping_time_cut_off'];

		// Check if a cut-off time is provided and if the current time is after the cut-off time.
		if ( null !== $cutoffTime ) {
			$currentTime = $today->format( 'H:i' );
			if ( $currentTime > $cutoffTime ) {
				// If after cut-off time, move to the next day.
				$today->modify( '+1 day' );
			}
		}

		// Loop through each day to add processing days, skipping weekends.
		for ( $i = 0; $i < $processingDays; $i++ ) {
			// Add a day to the current date.
			$today->modify( '+1 day' );

			// Check if the current day is a weekend (Saturday or Sunday).
			if ( $today->format( 'N' ) >= 6 ) {
				// If it's a weekend, move to the next Monday.
				$today->modify( 'next Monday' );
			}
		}

		// Get the final expedition day.
		return $today->format( 'Y-m-d' );
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
	 * Gets chosen payment method.
	 *
	 * @return string|null
	 */
	private function getChosenPaymentMethod(): ?string {
		$paymentMethod = WC()->session->get( 'chosen_payment_method' );
		if ( $paymentMethod ) {
			return $paymentMethod;
		}

		return null;
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
	 * Gets shipping method from session without calculation.
	 *
	 * @return string
	 */
	private function getChosenMethodFromSession(): string {
		$chosenShippingRate = null;
		if ( WC()->session ) {
			$chosenShippingRates = WC()->session->get( 'chosen_shipping_methods' );
			if ( is_array( $chosenShippingRates ) && ! empty( $chosenShippingRates ) ) {
				$chosenShippingRate = $chosenShippingRates[0];
			}
		}

		return ( $chosenShippingRate ? $chosenShippingRate : '' );
	}

	/**
	 * Gets carrier id from chosen shipping method.
	 * TODO: It's actually an alias of getCarrierIdFromShippingMethod, remove it later.
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

		$optionId = $this->removeShippingMethodPrefix( $chosenMethod );

		return Carrier\OptionPrefixer::removePrefix( $optionId );
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
	 * @param string            $name             Name.
	 * @param string            $optionId         Option ID.
	 * @param float             $taxExclusiveCost Cost.
	 * @param array<float>|null $taxes            Taxes. If NULL than it is going to be calculated.
	 *
	 * @return array
	 */
	private function createShippingRate( string $name, string $optionId, float $taxExclusiveCost, ?array $taxes ): array {
		return [
			'label'    => $name,
			'id'       => $optionId,
			'cost'     => $taxExclusiveCost,
			'taxes'    => $taxes ?? '',
			'calc_tax' => 'per_order',
		];
	}

	/**
	 * Gets disallowed shipping rate ids.
	 *
	 * @return array
	 */
	private function getDisallowedShippingRateIds(): array {
		$cartProducts = $this->wcAdapter->cartGetCartContent();

		$arraysToMerge = [];
		foreach ( $cartProducts as $cartProduct ) {
			$productEntity = $this->productEntiyFactory->fromPostId( $cartProduct['product_id'] );

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
		if ( ! $this->wpAdapter->didAction( 'wp_loaded' ) ) {
			return false;
		}

		$products = $this->wcAdapter->cartGetCartContent();

		foreach ( $products as $product ) {
			$productEntity = $this->productEntiyFactory->fromPostId( $product['product_id'] );
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
		$products   = $this->wcAdapter->cartGetCartContent();
		$taxClasses = [];

		foreach ( $products as $cartProduct ) {
			$product = $this->wcAdapter->productFactoryGetProduct( $cartProduct['product_id'] );
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
	private function getCartContentsTotalIncludingTax(): float {
		return $this->wcAdapter->cartGetCartContentsTotal() + $this->wcAdapter->cartGetCartContentsTax();
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
			$product            = $this->wcAdapter->productFactoryGetProduct( $cartProduct['product_id'] );
			$productCategoryIds = $product->get_category_ids();

			foreach ( $productCategoryIds as $productCategoryId ) {
				$productCategoryEntity           = $this->productCategoryEntiyFactory->fromTermId( (int) $productCategoryId );
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
	 * @param array $availableGateways Available gateways.
	 *
	 * @return array
	 */
	public function filterPaymentGateways( array $availableGateways ): array {
		global $wp;

		if ( ! is_checkout() ) {
			return $availableGateways;
		}

		$order = null;
		if ( isset( $wp->query_vars['order-pay'] ) && is_numeric( $wp->query_vars['order-pay'] ) ) {
			$order = $this->orderRepository->getById( (int) $wp->query_vars['order-pay'], true );
		}

		if ( $order instanceof Entity\Order ) {
			$chosenMethod = Carrier\OptionPrefixer::getOptionId( $order->getCarrier()->getId() );
		} else {
			$chosenMethod = $this->getChosenMethodFromSession();
		}

		if ( ! $this->isPacketeryShippingMethod( $chosenMethod ) ) {
			return $availableGateways;
		}

		$carrier = $this->carrierEntityRepository->getAnyById( $this->getCarrierIdFromShippingMethod( $chosenMethod ) );
		if ( null === $carrier ) {
			return $availableGateways;
		}

		$carrierOptions = $this->carrierOptionsFactory->createByCarrierId( $this->getCarrierId( $chosenMethod ) );
		foreach ( $availableGateways as $key => $availableGateway ) {
			if (
				$this->paymentHelper->isCodPaymentMethod( $availableGateway->id ) &&
				! $carrier->supportsCod()
			) {
				unset( $availableGateways[ $key ] );
			}

			if ( $carrierOptions->hasCheckoutPaymentMethodDisallowed( $availableGateway->id ) ) {
				unset( $availableGateways[ $key ] );
			}
		}

		return $availableGateways;
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
		if ( $this->pickupPointsConfig->isInternalPickupPointCarrier( $carrierId ) ) {
			return true;
		}

		return $this->carrierRepository->hasPickupPoints( (int) $carrierId );
	}

	/**
	 * Gets checkout POST data including stored pickup point if not present in the data.
	 *
	 * @param string   $chosenShippingMethod Chosen shipping method id.
	 * @param int|null $orderId              Id of order to be updated.
	 *
	 * @return array
	 */
	private function getPostDataIncludingStoredData( string $chosenShippingMethod, int $orderId = null ): array {
		$checkoutData      = $this->httpRequest->getPost();
		$savedCheckoutData = get_transient( $this->getTransientNamePacketaCheckoutData() );

		if ( empty( $checkoutData ) && empty( $savedCheckoutData[ $chosenShippingMethod ] ) ) {
			/**
			 * WC logger.
			 *
			 * @var WC_Logger $wcLogger
			 */
			$wcLogger = wc_get_logger();

			$dataToLog = [
				'chosenShippingMethod' => $chosenShippingMethod,
				'checkoutData'         => $checkoutData,
				'savedCheckoutData'    => $savedCheckoutData,
			];
			if ( null !== $orderId ) {
				$dataToLog['orderId'] = $orderId;
			}
			$wcLogger->warning( sprintf( 'Data of the order to be validated or saved are not set: %s', wp_json_encode( $dataToLog ) ), [ 'source' => 'packeta' ] );

			return [];
		}

		if (
			! is_array( $savedCheckoutData ) ||
			! isset( $savedCheckoutData[ $chosenShippingMethod ] ) ||
			! is_array( $savedCheckoutData[ $chosenShippingMethod ] )
		) {
			return $checkoutData;
		}

		$savedCarrierData = $savedCheckoutData[ $chosenShippingMethod ];
		if (
			empty( $checkoutData[ Order\Attribute::POINT_ID ] ) &&
			! empty( $savedCarrierData[ Order\Attribute::POINT_ID ] )
		) {
			foreach ( Order\Attribute::$pickupPointAttrs as $attribute ) {
				$checkoutData[ $attribute['name'] ] = $savedCarrierData[ $attribute['name'] ];
			}
		}

		if (
			empty( $checkoutData[ Order\Attribute::ADDRESS_IS_VALIDATED ] ) &&
			! empty( $savedCarrierData[ Order\Attribute::ADDRESS_IS_VALIDATED ] )
		) {
			foreach ( Order\Attribute::$homeDeliveryAttrs as $attribute ) {
				$checkoutData[ $attribute['name'] ] = $savedCarrierData[ $attribute['name'] ];
			}
		}

		if (
			empty( $checkoutData[ Order\Attribute::CAR_DELIVERY_ID ] ) &&
			! empty( $savedCarrierData[ Order\Attribute::CAR_DELIVERY_ID ] )
		) {
			foreach ( Order\Attribute::$carDeliveryAttrs as $attribute ) {
				$checkoutData[ $attribute['name'] ] = $savedCarrierData[ $attribute['name'] ];
			}
		}

		if (
			empty( $checkoutData[ Order\Attribute::CARRIER_ID ] ) &&
			! empty( $savedCarrierData[ Order\Attribute::CARRIER_ID ] )
		) {
			$checkoutData[ Order\Attribute::CARRIER_ID ] = $savedCarrierData[ Order\Attribute::CARRIER_ID ];
		}

		return $checkoutData;
	}

	/**
	 * Gets name of transient for selected pickup point.
	 */
	public function getTransientNamePacketaCheckoutData(): string {
		if ( is_user_logged_in() ) {
			$token = wp_get_session_token();
		} else {
			WC()->initialize_session();
			$token = WC()->session->get_customer_id();
		}
		return self::TRANSIENT_CHECKOUT_DATA_PREFIX . $token;
	}

	/**
	 * Applies surcharge if needed.
	 *
	 * @param \WC_Cart $cart WC cart.
	 *
	 * @return void
	 */
	public function applyCodSurgarche( \WC_Cart $cart ): void {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		$chosenPaymentMethod = WC()->session->get( 'packetery_checkout_payment_method' );
		if ( null !== $chosenPaymentMethod && ! $this->paymentHelper->isCodPaymentMethod( $chosenPaymentMethod ) ) {
			return;
		}
		$chosenShippingRate = WC()->session->get( 'packetery_checkout_shipping_method' );
		if ( null === $chosenShippingRate ) {
			return;
		}
		$chosenShippingMethod = $this->removeShippingMethodPrefix( $chosenShippingRate );
		if ( ! $this->isPacketeryShippingMethod( $chosenShippingMethod ) ) {
			return;
		}
		$carrierOptions = $this->carrierOptionsFactory->createByOptionId( $chosenShippingMethod );
		$surcharge      = $this->getCODSurcharge( $carrierOptions->toArray(), $this->getCartPrice() );

		$maxTaxClass = $this->getTaxClassWithMaxRate();
		$taxable     = ! ( false === $maxTaxClass );

		$cart->add_fee( __( 'COD surcharge', 'packeta' ), $surcharge, $taxable );
	}

}
