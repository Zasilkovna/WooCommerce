<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Core\Entity;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Exception\ProductNotFoundException;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order;
use Packetery\Module\Payment\PaymentHelper;
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
		OrderUpdater $orderUpdater
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

		if (
			$chosenShippingMethodOptionId === null ||
			$this->checkoutService->isPacketeryShippingMethod( $chosenShippingMethodOptionId ) === false
		) {
			return;
		}

		$carrierOptions = $this->carrierOptionsFactory->createByOptionId( $chosenShippingMethodOptionId );
		$chosenCarrier  = $this->carrierEntityRepository->getAnyById(
			$this->checkoutService->getCarrierIdFromPacketeryShippingMethod( $chosenShippingMethodOptionId )
		);
		$maxTaxClass    = $this->cartService->getTaxClassWithMaxRate();
		$isTaxable      = $maxTaxClass !== null;

		if (
			$carrierOptions->hasCouponFreeShippingForFeesAllowed() &&
			$this->rateCalculator->isFreeShippingCouponApplied( $this->wcAdapter->cart() )
		) {
			return;
		}

		$this->addAgeVerificationFee( $cart, $chosenCarrier, $carrierOptions, $isTaxable, $maxTaxClass );
		$this->addCodSurchargeFee( $cart, $carrierOptions, $isTaxable, $maxTaxClass );
	}

	private function addAgeVerificationFee( WC_Cart $cart, ?Entity\Carrier $chosenCarrier, Carrier\Options $carrierOptions, bool $isTaxable, ?string $maxTaxClass ): void {
		if (
			$chosenCarrier === null ||
			! $chosenCarrier->supportsAgeVerification() ||
			$carrierOptions->getAgeVerificationFee() === null ||
			! $this->cartService->isAgeVerificationRequired()
		) {
			return;
		}
		$feeAmount = $this->currencySwitcherService->getConvertedPrice( $carrierOptions->getAgeVerificationFee() );

		if ( $isTaxable && $feeAmount > 0 && $this->optionsProvider->arePricesTaxInclusive() ) {
			$feeAmount = $this->calcTaxExclusiveFeeAmount( $feeAmount, $maxTaxClass );
		}
		$cart->add_fee( $this->wpAdapter->__( 'Age verification fee', 'packeta' ), $feeAmount, $isTaxable, $maxTaxClass );
	}

	private function addCodSurchargeFee( WC_Cart $cart, Carrier\Options $carrierOptions, bool $isTaxable, ?string $maxTaxClass ): void {
		if ( $this->checkoutService->areBlocksUsedInCheckout() ) {
			$paymentMethod = $this->wcAdapter->sessionGetString( 'packetery_checkout_payment_method' );
		} else {
			$paymentMethod = $this->sessionService->getChosenPaymentMethod();
		}

		if ( $paymentMethod === null || $this->paymentHelper->isCodPaymentMethod( $paymentMethod ) === false ) {
			return;
		}

		$applicableSurcharge = $this->rateCalculator->getCODSurcharge(
			$carrierOptions->toArray(),
			$this->wcAdapter->cartGetSubtotal()
		);
		$applicableSurcharge = $this->currencySwitcherService->getConvertedPrice( $applicableSurcharge );
		if ( $applicableSurcharge <= 0 ) {
			return;
		}

		if ( $isTaxable && $this->optionsProvider->arePricesTaxInclusive() ) {
			$applicableSurcharge = $this->calcTaxExclusiveFeeAmount( $applicableSurcharge, $maxTaxClass );
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
	 * @param WC_Payment_Gateway[] $availableGateways Available gateways.
	 *
	 * @return WC_Payment_Gateway[]
	 */
	public function filterPaymentGateways( array $availableGateways ): array {
		$order      = null;
		$wpOrderPay = $this->checkoutService->getOrderPayParameter();
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

		$carrierId = $this->checkoutService->getCarrierIdFromPacketeryShippingMethod( $chosenMethod );
		$carrier   = $this->carrierEntityRepository->getAnyById( $carrierId );
		if ( $carrier === null ) {
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
}
