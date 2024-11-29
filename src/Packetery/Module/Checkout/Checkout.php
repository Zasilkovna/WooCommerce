<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Core;
use Packetery\Core\Entity;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Exception\ProductNotFoundException;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order;
use Packetery\Module\Order\PickupPointValidator;
use Packetery\Module\Payment\PaymentHelper;
use Packetery\Module\ShippingMethod;
use WC_Cart;
use WC_Data_Exception;
use WC_Order;

class Checkout {

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	/**
	 * @var CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * @var Order\Repository
	 */
	private $orderRepository;

	/**
	 * @var CurrencySwitcherFacade
	 */
	private $currencySwitcherFacade;

	/**
	 * @var Order\PacketAutoSubmitter
	 */
	private $packetAutoSubmitter;

	/**
	 * @var Order\AttributeMapper
	 */
	private $mapper;

	/**
	 * @var RateCalculator
	 */
	private $rateCalculator;

	/**
	 * @var Carrier\EntityRepository
	 */
	private $carrierEntityRepository;

	/**
	 * @var CarDeliveryConfig
	 */
	private $carDeliveryConfig;

	/**
	 * @var PaymentHelper
	 */
	private $paymentHelper;

	/**
	 * @var CheckoutService
	 */
	private $checkoutService;

	/**
	 * @var CheckoutRenderer
	 */
	private $renderer;

	/**
	 * @var CheckoutStorage
	 */
	private $storage;

	/**
	 * @var CartService
	 */
	private $cartService;

	/**
	 * @var SessionService
	 */
	private $sessionService;

	/**
	 * @var CheckoutValidator
	 */
	private $validator;

	public function __construct(
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter,
		CarrierOptionsFactory $carrierOptionsFactory,
		OptionsProvider $optionsProvider,
		Order\Repository $orderRepository,
		CurrencySwitcherFacade $currencySwitcherFacade,
		Order\PacketAutoSubmitter $packetAutoSubmitter,
		Order\AttributeMapper $mapper,
		RateCalculator $rateCalculator,
		Carrier\EntityRepository $carrierEntityRepository,
		CarDeliveryConfig $carDeliveryConfig,
		PaymentHelper $paymentHelper,
		CheckoutService $checkoutService,
		CheckoutRenderer $renderer,
		CheckoutStorage $storage,
		CartService $cartService,
		SessionService $sessionService,
		CheckoutValidator $validator
	) {
		$this->wpAdapter               = $wpAdapter;
		$this->wcAdapter               = $wcAdapter;
		$this->carrierOptionsFactory   = $carrierOptionsFactory;
		$this->optionsProvider         = $optionsProvider;
		$this->orderRepository         = $orderRepository;
		$this->currencySwitcherFacade  = $currencySwitcherFacade;
		$this->packetAutoSubmitter     = $packetAutoSubmitter;
		$this->mapper                  = $mapper;
		$this->rateCalculator          = $rateCalculator;
		$this->carrierEntityRepository = $carrierEntityRepository;
		$this->carDeliveryConfig       = $carDeliveryConfig;
		$this->paymentHelper           = $paymentHelper;
		$this->checkoutService         = $checkoutService;
		$this->renderer                = $renderer;
		$this->storage                 = $storage;
		$this->cartService             = $cartService;
		$this->sessionService          = $sessionService;
		$this->validator               = $validator;
	}

	public function registerHooks(): void {
		// This action works for both classic and Divi templates.
		$this->wpAdapter->addAction(
			'woocommerce_review_order_before_submit',
			[
				$this->renderer,
				'actionRenderHiddenInputFields',
			]
		);

		$this->wpAdapter->addAction( 'woocommerce_checkout_process', [ $this->validator, 'actionValidateCheckoutData' ] );
		$this->wpAdapter->addAction( 'woocommerce_checkout_update_order_meta', [ $this, 'actionUpdateOrderMeta' ] );
		$this->wpAdapter->addAction(
			'woocommerce_store_api_checkout_order_processed',
			[
				$this,
				'actionUpdateOrderMetaBlocks',
			]
		);

		// Must not be registered at backend.
		$this->wpAdapter->addFilter( 'woocommerce_available_payment_gateways', [ $this, 'filterPaymentGateways' ] );

		$this->wpAdapter->addAction(
			'woocommerce_review_order_before_shipping',
			[
				$this->sessionService,
				'actionUpdateShippingRates',
			],
			10
		);
		$this->wpAdapter->addFilter(
			'woocommerce_cart_shipping_packages',
			[
				$this->sessionService,
				'filterUpdateShippingPackages',
			]
		);
		$this->wpAdapter->addAction( 'woocommerce_cart_calculate_fees', [ $this, 'actionCalculateFees' ] );
		$this->wpAdapter->addAction(
			'init',
			function () {
				/**
				 * Tells if widget button table row should be used.
				 *
				 * @since 1.3.0
				 */
				if ( $this->optionsProvider->getCheckoutWidgetButtonLocation() === 'after_transport_methods' ) {
					$this->wpAdapter->addAction(
						'woocommerce_review_order_after_shipping',
						[
							$this->renderer,
							'actionRenderWidgetButtonTableRow',
						]
					);
				} else {
					$this->wpAdapter->addAction(
						'woocommerce_after_shipping_rate',
						[
							$this->renderer,
							'actionRenderWidgetButtonAfterShippingRate',
						]
					);
				}
			}
		);

		$this->wpAdapter->addAction(
			'woocommerce_review_order_after_shipping',
			[
				$this->renderer,
				'actionRenderEstimatedDeliveryDateSection',
			]
		);
	}

	/**
	 * Saves pickup point and other Packeta information to order.
	 *
	 * @param int $orderId Order id.
	 *
	 * @throws WC_Data_Exception When invalid data are passed during shipping address update.
	 */
	public function actionUpdateOrderMeta( int $orderId ): void {
		$wcOrder = $this->orderRepository->getWcOrderById( $orderId );
		if ( null === $wcOrder ) {
			return;
		}

		$this->actionUpdateOrderMetaBlocks( $wcOrder );
	}

	/**
	 * Saves pickup point and other Packeta information to order.
	 *
	 * @param WC_Order $wcOrder Order id.
	 *
	 * @throws WC_Data_Exception When invalid data are passed during shipping address update.
	 */
	public function actionUpdateOrderMetaBlocks( WC_Order $wcOrder ): void {
		$chosenMethod = $this->checkoutService->getChosenMethod();
		if ( false === $this->checkoutService->isPacketeryShippingMethod( $chosenMethod ) ) {
			return;
		}

		$checkoutData           = $this->storage->getPostDataIncludingStoredData( $chosenMethod, $wcOrder->get_id() );
		$propsToSave            = [];
		$carrierId              = $this->checkoutService->getCarrierIdFromShippingMethod( $chosenMethod );
		$orderHasUnsavedChanges = false;

		$propsToSave[ Order\Attribute::CARRIER_ID ] = $carrierId;

		if ( $this->checkoutService->isPickupPointOrder() ) {
			// @phpstan-ignore-next-line
			if ( PickupPointValidator::IS_ACTIVE ) {
				$pickupPointValidationError = $this->wcAdapter->sessionGet( PickupPointValidator::VALIDATION_HTTP_ERROR_SESSION_KEY );
				if ( null !== $pickupPointValidationError ) {
					// translators: %s: Message from downloader.
					$wcOrder->add_order_note(
						sprintf(
							$this->wpAdapter->__( 'The selected Packeta pickup point could not be validated, reason: %s.', 'packeta' ),
							$pickupPointValidationError
						)
					);
					$this->wcAdapter->sessionSet( PickupPointValidator::VALIDATION_HTTP_ERROR_SESSION_KEY, null );
				}
			}

			if ( count( $checkoutData ) === 0 ) {
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
			$this->checkoutService->isHomeDeliveryOrder()
		) {
			$validatedAddress = $this->mapper->toValidatedAddress( $checkoutData );
			$orderEntity->setDeliveryAddress( $validatedAddress );
			$orderEntity->setAddressValidated( true );
			if ( $this->checkoutService->areBlocksUsedInCheckout() ) {
				$this->mapper->validatedAddressToWcOrderShippingAddress( $wcOrder, $checkoutData );
				$orderHasUnsavedChanges = true;
			}
		}

		if ( $orderHasUnsavedChanges ) {
			$wcOrder->save();
		}

		if ( count( $checkoutData ) > 0 && $this->checkoutService->isCarDeliveryOrder() ) {
			$address = $this->mapper->toCarDeliveryAddress( $checkoutData );
			$orderEntity->setDeliveryAddress( $address );
			$orderEntity->setAddressValidated( true );
			$orderEntity->setCarDeliveryId( $checkoutData[ Order\Attribute::CAR_DELIVERY_ID ] );
		}

		if ( 0.0 === $this->cartService->getCartWeightKg() && true === $this->optionsProvider->isDefaultWeightEnabled() ) {
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

		$this->storage->deleteTransient();
		$this->orderRepository->save( $orderEntity );
		$this->packetAutoSubmitter->handleEventAsync( Order\PacketAutoSubmitter::EVENT_ON_ORDER_CREATION_FE, $wcOrder->get_id() );
	}

	/**
	 * Calculates fees.
	 *
	 * @return void
	 * @throws ProductNotFoundException Product not found.
	 */
	public function actionCalculateFees(): void {
		$chosenShippingMethod = $this->checkoutService->calculateShippingAndGetId();
		if ( false === $this->checkoutService->isPacketeryShippingMethod( $chosenShippingMethod ) ) {
			return;
		}

		$carrierOptions = $this->carrierOptionsFactory->createByOptionId( $chosenShippingMethod );
		$chosenCarrier  = $this->carrierEntityRepository->getAnyById( $this->checkoutService->getCarrierIdFromShippingMethod( $chosenShippingMethod ) );
		$maxTaxClass    = $this->cartService->getTaxClassWithMaxRate();

		if (
			$carrierOptions->hasCouponFreeShippingForFeesAllowed() &&
			$this->rateCalculator->isFreeShippingCouponApplied( $this->wcAdapter->cart() )
		) {
			return;
		}

		if (
			null !== $chosenCarrier &&
			$chosenCarrier->supportsAgeVerification() &&
			null !== $carrierOptions->getAgeVerificationFee() &&
			$this->cartService->isAgeVerification18PlusRequired()
		) {
			$feeAmount = $this->currencySwitcherFacade->getConvertedPrice( $carrierOptions->getAgeVerificationFee() );
			if ( false !== $maxTaxClass && $feeAmount > 0 && $this->optionsProvider->arePricesTaxInclusive() ) {
				$feeAmount = $this->calcTaxExclusiveFeeAmount( $feeAmount, $maxTaxClass );
			}

			$this->wcAdapter->cartFeesApiAddFee(
				[
					'id'        => 'packetery-age-verification-fee',
					'name'      => $this->wpAdapter->__( 'Age verification fee', 'packeta' ),
					'amount'    => $feeAmount,
					'taxable'   => ! ( false === $maxTaxClass ),
					'tax_class' => $maxTaxClass,
				]
			);
		}

		$paymentMethod = $this->sessionService->getChosenPaymentMethod();
		if ( null === $paymentMethod || false === $this->paymentHelper->isCodPaymentMethod( $paymentMethod ) ) {
			return;
		}

		$applicableSurcharge = $this->rateCalculator->getCODSurcharge(
			$carrierOptions->toArray(),
			$this->wcAdapter->cartGetSubtotal()
		);
		$applicableSurcharge = $this->currencySwitcherFacade->getConvertedPrice( $applicableSurcharge );
		if ( 0 >= $applicableSurcharge ) {
			return;
		}

		if ( false !== $maxTaxClass && $this->optionsProvider->arePricesTaxInclusive() ) {
			$applicableSurcharge = $this->calcTaxExclusiveFeeAmount( $applicableSurcharge, $maxTaxClass );
		}

		$fee = [
			'id'        => 'packetery-cod-surcharge',
			'name'      => $this->wpAdapter->__( 'COD surcharge', 'packeta' ),
			'amount'    => $applicableSurcharge,
			'taxable'   => ! ( false === $maxTaxClass ),
			'tax_class' => $maxTaxClass,
		];

		$this->wcAdapter->cartFeesApiAddFee( $fee );
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
		return $taxInclusiveFeeAmount - array_sum(
			$this->wcAdapter->calcTax(
				$taxInclusiveFeeAmount,
				$this->wcAdapter->taxGetRates( $taxClass ),
				true
			)
		);
	}

	/**
	 * Prepare shipping rates based on cart properties.
	 *
	 * @param array|null $allowedCarrierNames List of allowed carrier names.
	 *
	 * @return array
	 * @throws ProductNotFoundException Product not found.
	 */
	public function getShippingRates( ?array $allowedCarrierNames ): array {
		$customerCountry           = $this->checkoutService->getCustomerCountry();
		$availableCarriers         = $this->carrierEntityRepository->getByCountryIncludingNonFeed( $customerCountry );
		$cartProducts              = $this->wcAdapter->cartGetCartContents();
		$cartPrice                 = $this->cartService->getCartContentsTotalIncludingTax();
		$cartWeight                = $this->cartService->getCartWeightKg();
		$totalCartProductValue     = $this->cartService->getTotalCartProductValue();
		$disallowedShippingRateIds = $this->cartService->getDisallowedShippingRateIds();
		$isAgeVerificationRequired = $this->cartService->isAgeVerification18PlusRequired();

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

			if ( $this->cartService->isShippingRateRestrictedByProductsCategory( $optionId, $cartProducts ) ) {
				continue;
			}

			$cost = $this->rateCalculator->getRateCost( $options, $cartPrice, $totalCartProductValue, $cartWeight );
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

				$customRates[ $rateId ] = $this->rateCalculator->createShippingRate( $carrierName, $rateId, $cost, $taxes );
			}
		}

		return $customRates;
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
			return sprintf( '%s: %s', $name, $this->wpAdapter->__( 'Free', 'packeta' ) );
		}

		return $name;
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
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		$wpOrderPay = $wp->query_vars['order-pay'] ?? null;
		if ( is_numeric( $wpOrderPay ) ) {
			$order = $this->orderRepository->getByIdWithValidCarrier( (int) $wpOrderPay );
		}

		if ( $order instanceof Entity\Order ) {
			$chosenMethod = Carrier\OptionPrefixer::getOptionId( $order->getCarrier()->getId() );
		} else {
			$chosenMethod = $this->sessionService->getChosenMethodFromSession();
		}

		if ( ! $this->checkoutService->isPacketeryShippingMethod( $chosenMethod ) ) {
			return $availableGateways;
		}

		$carrierId = $this->checkoutService->getCarrierIdFromShippingMethod( $chosenMethod );
		$carrier   = $this->carrierEntityRepository->getAnyById( $carrierId );
		if ( null === $carrier ) {
			return $availableGateways;
		}

		$carrierOptions = $this->carrierOptionsFactory->createByCarrierId( $carrierId );
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
	 * Applies surcharge if needed.
	 *
	 * @param WC_Cart $cart WC cart.
	 *
	 * @return void
	 * @throws ProductNotFoundException Product not found.
	 */
	public function actionApplyCodSurcharge( WC_Cart $cart ): void {
		if ( ! defined( 'DOING_AJAX' ) && $this->wpAdapter->isAdmin() ) {
			return;
		}
		$chosenPaymentMethod = $this->wcAdapter->sessionGet( 'packetery_checkout_payment_method' );
		if ( null !== $chosenPaymentMethod && ! $this->paymentHelper->isCodPaymentMethod( $chosenPaymentMethod ) ) {
			return;
		}
		$chosenShippingRate = $this->wcAdapter->sessionGet( 'packetery_checkout_shipping_method' );
		if ( null === $chosenShippingRate ) {
			return;
		}
		if ( ! $this->checkoutService->isPacketeryShippingMethod( $chosenShippingRate ) ) {
			return;
		}
		$chosenShippingMethod = $this->checkoutService->removeShippingMethodPrefix( $chosenShippingRate );
		$carrierOptions       = $this->carrierOptionsFactory->createByOptionId( $chosenShippingMethod );
		$surcharge            = $this->rateCalculator->getCODSurcharge(
			$carrierOptions->toArray(),
			$this->wcAdapter->cartGetSubtotal()
		);

		$maxTaxClass = $this->cartService->getTaxClassWithMaxRate();
		$taxable     = ! ( false === $maxTaxClass );

		$cart->add_fee( $this->wpAdapter->__( 'COD surcharge', 'packeta' ), $surcharge, $taxable );
	}
}
