<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Core\Entity;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\DiagnosticsLogger\DiagnosticsLogger;
use Packetery\Module\Exception\ProductNotFoundException;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order;
use Packetery\Module\Payment\PaymentHelper;
use Packetery\Module\WcLogger;
use WC_Cart;
use WC_Payment_Gateway;

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
	 * @var CurrencySwitcherService
	 */
	private $currencySwitcherService;

	/**
	 * @var RateCalculator
	 */
	private $rateCalculator;

	/**
	 * @var Carrier\EntityRepository
	 */
	private $carrierEntityRepository;

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

	/**
	 * @var OrderUpdater
	 */
	private $orderUpdater;

	/**
	 * @var DiagnosticsLogger
	 */
	private $diagnosticsLogger;

	public function __construct(
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter,
		CarrierOptionsFactory $carrierOptionsFactory,
		OptionsProvider $optionsProvider,
		Order\Repository $orderRepository,
		CurrencySwitcherService $currencySwitcherService,
		RateCalculator $rateCalculator,
		Carrier\EntityRepository $carrierEntityRepository,
		PaymentHelper $paymentHelper,
		CheckoutService $checkoutService,
		CheckoutRenderer $renderer,
		CartService $cartService,
		SessionService $sessionService,
		CheckoutValidator $validator,
		OrderUpdater $orderUpdater,
		DiagnosticsLogger $diagnosticsLogger
	) {
		$this->wpAdapter               = $wpAdapter;
		$this->wcAdapter               = $wcAdapter;
		$this->carrierOptionsFactory   = $carrierOptionsFactory;
		$this->optionsProvider         = $optionsProvider;
		$this->orderRepository         = $orderRepository;
		$this->currencySwitcherService = $currencySwitcherService;
		$this->rateCalculator          = $rateCalculator;
		$this->carrierEntityRepository = $carrierEntityRepository;
		$this->paymentHelper           = $paymentHelper;
		$this->checkoutService         = $checkoutService;
		$this->renderer                = $renderer;
		$this->cartService             = $cartService;
		$this->sessionService          = $sessionService;
		$this->validator               = $validator;
		$this->orderUpdater            = $orderUpdater;
		$this->diagnosticsLogger       = $diagnosticsLogger;
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
		$this->wpAdapter->addAction( 'woocommerce_checkout_update_order_meta', [ $this->orderUpdater, 'actionUpdateOrderById' ] );
		$this->wpAdapter->addAction(
			'woocommerce_store_api_checkout_order_processed',
			[
				$this->orderUpdater,
				'actionUpdateOrder',
			]
		);

		// Must not be registered at backend.
		$this->wpAdapter->addFilter( 'woocommerce_available_payment_gateways', [ $this, 'filterPaymentGateways' ] );

		$this->wpAdapter->addAction(
			'woocommerce_review_order_before_shipping',
			[
				$this->sessionService,
				'actionUpdateShippingRates',
			]
		);
		$this->wpAdapter->addFilter(
			'woocommerce_cart_shipping_packages',
			[
				$this->sessionService,
				'filterUpdateShippingPackages',
			]
		);
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
	 * Calculates fees.
	 *
	 * @return void
	 * @throws ProductNotFoundException Product not found.
	 */
	public function actionCalculateFees( WC_Cart $cart ): void {
		$chosenShippingMethodOptionId = $this->checkoutService->calculateShippingAndGetOptionId();
		$isPacketeryShippingMethod    = $this->checkoutService->isPacketeryShippingMethod( (string) $chosenShippingMethodOptionId );

		if (
			$chosenShippingMethodOptionId === null ||
			$isPacketeryShippingMethod === false
		) {
			$this->diagnosticsLogger->log(
				'No packetery shipping method chosen',
				[
					'chosenShippingMethodOptionId' => $chosenShippingMethodOptionId,
					'isPacketeryShippingMethod'    => $isPacketeryShippingMethod,
				]
			);

			return;
		}

		$carrierOptions                      = $this->carrierOptionsFactory->createByOptionId( $chosenShippingMethodOptionId );
		$chosenCarrier                       = $this->carrierEntityRepository->getAnyById(
			$this->checkoutService->getCarrierIdFromPacketeryShippingMethod( $chosenShippingMethodOptionId )
		);
		$maxTaxClass                         = $this->cartService->getTaxClassWithMaxRate();
		$isTaxable                           = $maxTaxClass !== null;
		$hasCouponFreeShippingForFeesAllowed = $carrierOptions->hasCouponFreeShippingForFeesAllowed();
		$wcAdapterCart                       = $this->wcAdapter->cart();
		$isFreeShippingCouponApplied         = $this->rateCalculator->isFreeShippingCouponApplied( $wcAdapterCart );

		$this->diagnosticsLogger->log(
			'Coupon free shipping for fees parameters',
			[
				'hasCouponFreeShippingForFeesAllowed' => $hasCouponFreeShippingForFeesAllowed,
				'isFreeShippingCouponApplied'         => $isFreeShippingCouponApplied,
				'wcAdapterCart'                       => $wcAdapterCart,
			]
		);
		if ( $hasCouponFreeShippingForFeesAllowed === true && $isFreeShippingCouponApplied === true ) {
			return;
		}

		$this->diagnosticsLogger->log(
			'Coupon free shipping for fees is not allowed',
			[
				'chosenShippingMethodOptionId'        => $chosenShippingMethodOptionId,
				'hasCouponFreeShippingForFeesAllowed' => $hasCouponFreeShippingForFeesAllowed,
				'isFreeShippingCouponApplied'         => $isFreeShippingCouponApplied,
				'chosenCarrier'                       => $chosenCarrier,
				'carrierOptions'                      => $carrierOptions,
				'maxTaxClass'                         => $maxTaxClass,
				'isTaxable'                           => $isTaxable,
				'cart'                                => $cart,
			]
		);

		$this->addAgeVerificationFee( $cart, $chosenCarrier, $carrierOptions, $isTaxable, $maxTaxClass );
		$this->addCodSurchargeFee( $cart, $carrierOptions, $isTaxable, $maxTaxClass );
	}

	private function addAgeVerificationFee( WC_Cart $cart, ?Entity\Carrier $chosenCarrier, Carrier\Options $carrierOptions, bool $isTaxable, ?string $maxTaxClass ): void {
		$isAgeVerificationRequired = $this->cartService->isAgeVerificationRequired();
		$ageVerificationFee        = $carrierOptions->getAgeVerificationFee();
		$this->diagnosticsLogger->log(
			'Age verification parameters',
			[
				'chosenCarrier'             => $chosenCarrier,
				'ageVerificationFee'        => $ageVerificationFee,
				'isAgeVerificationRequired' => $isAgeVerificationRequired,
				'isTaxable'                 => $isTaxable,
				'maxTaxClass'               => $maxTaxClass,
			]
		);
		if (
			$chosenCarrier === null ||
			! $chosenCarrier->supportsAgeVerification() ||
			$ageVerificationFee === null ||
			$isAgeVerificationRequired === false
		) {
			$this->diagnosticsLogger->log( 'Age verification fee is not added', [] );

			return;
		}
		$feeAmount = $this->currencySwitcherService->getConvertedPrice( $ageVerificationFee );
		$this->diagnosticsLogger->log( 'Age verification converted price is added', [ 'feeAmount' => $feeAmount ] );

		if ( $isTaxable && $feeAmount > 0 && $this->optionsProvider->arePricesTaxInclusive() ) {
			$feeAmount = $this->calcTaxExclusiveFeeAmount( $feeAmount, $maxTaxClass );
			$this->diagnosticsLogger->log( 'Age verification tax exclusive fee amount is added', [ 'feeAmount' => $feeAmount ] );
		}
		$cart->add_fee( $this->wpAdapter->__( 'Age verification fee', 'packeta' ), $feeAmount, $isTaxable, $maxTaxClass );
	}

	private function addCodSurchargeFee( WC_Cart $cart, Carrier\Options $carrierOptions, bool $isTaxable, ?string $maxTaxClass ): void {
		if ( $this->checkoutService->areBlocksUsedInCheckout() ) {
			$paymentMethod = $this->wcAdapter->sessionGetString( 'packetery_checkout_payment_method' );
		} else {
			$paymentMethod = $this->sessionService->getChosenPaymentMethod();
		}

		$isCodPaymentMethod = $this->paymentHelper->isCodPaymentMethod( (string) $paymentMethod );
		$this->diagnosticsLogger->log(
			'COD surcharge parameters',
			[
				'paymentMethod'      => $paymentMethod,
				'isCodPaymentMethod' => $isCodPaymentMethod,
			]
		);
		if ( $paymentMethod === null || $isCodPaymentMethod === false ) {
			return;
		}

		$applicableSurcharge = $this->rateCalculator->getCODSurcharge(
			$carrierOptions->toArray(),
			$this->wcAdapter->cartGetSubtotal()
		);
		$this->diagnosticsLogger->log( 'Get COD surcharge', [ 'applicableSurcharge' => $applicableSurcharge ] );
		$applicableSurcharge = $this->currencySwitcherService->getConvertedPrice( $applicableSurcharge );
		$this->diagnosticsLogger->log( 'Get COD surcharge converted', [ 'applicableSurcharge' => $applicableSurcharge ] );
		if ( $applicableSurcharge <= 0 ) {
			return;
		}

		if ( $isTaxable && $this->optionsProvider->arePricesTaxInclusive() ) {
			$applicableSurcharge = $this->calcTaxExclusiveFeeAmount( $applicableSurcharge, $maxTaxClass );
			$this->diagnosticsLogger->log( 'Get COD surcharge tax exclusive', [ 'applicableSurcharge' => $applicableSurcharge ] );
		}

		$cart->add_fee( $this->wpAdapter->__( 'COD surcharge', 'packeta' ), $applicableSurcharge, $isTaxable, $maxTaxClass );
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
	 * Filters out payment methods, that can not be used.
	 *
	 * @param WC_Payment_Gateway[]|mixed $availableGateways Available gateways.
	 *
	 * @return WC_Payment_Gateway[]|mixed
	 */
	public function filterPaymentGateways( $availableGateways ) {
		$this->diagnosticsLogger->log(
			'Payment gateways for filtering',
			[
				'availableGateways' => $availableGateways,
			]
		);
		if ( ! is_array( $availableGateways ) ) {
			WcLogger::logArgumentTypeError( __METHOD__, 'availableGateways', 'array', $availableGateways );

			return $availableGateways;
		}

		$order      = null;
		$wpOrderPay = $this->checkoutService->getOrderPayParameter();
		if ( is_numeric( $wpOrderPay ) ) {
			$order = $this->orderRepository->getByIdWithValidCarrier( (int) $wpOrderPay );
			$this->diagnosticsLogger->log(
				'Order found by order pay parameter',
				[
					'wpOrderPay' => $wpOrderPay,
					'order'      => $order,
				]
			);
		}

		if ( $order instanceof Entity\Order ) {
			$chosenMethod = Carrier\OptionPrefixer::getOptionId( $order->getCarrier()->getId() );
		} else {
			$chosenMethod = $this->sessionService->getChosenMethodFromSession();
		}

		$isPacketeryShippingMethod = $this->checkoutService->isPacketeryShippingMethod( $chosenMethod );
		$this->diagnosticsLogger->log(
			'Chosen method',
			[
				'chosenMethod'              => $chosenMethod,
				'isPacketeryShippingMethod' => $isPacketeryShippingMethod,
			]
		);
		if ( $isPacketeryShippingMethod === false ) {
			return $availableGateways;
		}

		$carrierId = $this->checkoutService->getCarrierIdFromPacketeryShippingMethod( $chosenMethod );
		$carrier   = $this->carrierEntityRepository->getAnyById( $carrierId );
		$this->diagnosticsLogger->log(
			'Carrier for filtering gateways',
			[
				'carrierId' => $carrierId,
				'carrier'   => $carrier,
			]
		);
		if ( $carrier === null ) {
			return $availableGateways;
		}

		$carrierOptions = $this->carrierOptionsFactory->createByCarrierId( $carrierId );
		foreach ( $availableGateways as $key => $availableGateway ) {
			if ( ! $availableGateway instanceof WC_Payment_Gateway ) {
				WcLogger::logArgumentTypeError( __METHOD__, 'availableGateway', 'WC_Payment_Gateway', $availableGateway );

				continue;
			}

			$isCodPaymentMethod                 = $this->paymentHelper->isCodPaymentMethod( $availableGateway->id );
			$supportsCod                        = $carrier->supportsCod();
			$hasCheckoutPaymentMethodDisallowed = $carrierOptions->hasCheckoutPaymentMethodDisallowed( $availableGateway->id );
			$this->diagnosticsLogger->log(
				'Payment method filtering parameters',
				[
					'availableGateway'                   => $availableGateway,
					'isCodPaymentMethod'                 => $isCodPaymentMethod,
					'supportsCod'                        => $supportsCod,
					'hasCheckoutPaymentMethodDisallowed' => $hasCheckoutPaymentMethodDisallowed,
				]
			);

			if ( $isCodPaymentMethod === true && $supportsCod === false ) {
				unset( $availableGateways[ $key ] );
			}

			if ( $hasCheckoutPaymentMethodDisallowed === true ) {
				unset( $availableGateways[ $key ] );
			}
		}

		$this->diagnosticsLogger->log( 'Filtered payment methods', [ 'availableGateways' => $availableGateways ] );

		return $availableGateways;
	}
}
