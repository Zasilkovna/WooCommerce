<?php

namespace Tests\Module\Checkout;

use Packetery\Core\Entity\Carrier;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\Options;
use Packetery\Module\Checkout\CartService;
use Packetery\Module\Checkout\Checkout;
use Packetery\Module\Checkout\CheckoutRenderer;
use Packetery\Module\Checkout\CheckoutService;
use Packetery\Module\Checkout\CheckoutValidator;
use Packetery\Module\Checkout\CurrencySwitcherService;
use Packetery\Module\Checkout\OrderUpdater;
use Packetery\Module\Checkout\RateCalculator;
use Packetery\Module\Checkout\SessionService;
use Packetery\Module\Checkout\ShippingTaxModifier;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\Repository;
use Packetery\Module\Payment\PaymentHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WC_Cart;

class CheckoutTest extends TestCase {
	private WpAdapter|MockObject $wpAdapter;
	private CheckoutService|MockObject $checkoutService;
	private CarrierOptionsFactory|MockObject $carrierOptionsFactory;
	private EntityRepository|MockObject $carrierEntityRepository;
	private CartService|MockObject $cartService;
	private RateCalculator|MockObject $rateCalculator;
	private PaymentHelper|MockObject $paymentHelper;
	private WcAdapter|MockObject $wcAdapter;
	private OptionsProvider|MockObject $optionsProvider;
	private Repository|MockObject $orderRepository;
	private CurrencySwitcherService|MockObject $currencySwitcherService;
	private CheckoutRenderer|MockObject $checkoutRenderer;
	private ShippingTaxModifier|MockObject $checkoutEventHandler;
	private SessionService|MockObject $sessionService;
	private CheckoutValidator|MockObject $checkoutValidator;
	private OrderUpdater|MockObject $orderUpdater;
	private Checkout $checkout;
	private WC_Cart|MockObject $WCCart;

	protected function creatCheckout(): void {
		$this->wpAdapter               = $this->createMock( WpAdapter::class );
		$this->wcAdapter               = $this->createMock( WcAdapter::class );
		$this->carrierOptionsFactory   = $this->createMock( CarrierOptionsFactory::class );
		$this->optionsProvider         = $this->createMock( OptionsProvider::class );
		$this->orderRepository         = $this->createMock( Repository::class );
		$this->currencySwitcherService = $this->createMock( CurrencySwitcherService::class );
		$this->rateCalculator          = $this->createMock( RateCalculator::class );
		$this->carrierEntityRepository = $this->createMock( EntityRepository::class );
		$this->paymentHelper           = $this->createMock( PaymentHelper::class );
		$this->checkoutService         = $this->createMock( CheckoutService::class );
		$this->checkoutRenderer        = $this->createMock( CheckoutRenderer::class );
		$this->checkoutEventHandler    = $this->createMock( ShippingTaxModifier::class );
		$this->cartService             = $this->createMock( CartService::class );
		$this->sessionService          = $this->createMock( SessionService::class );
		$this->checkoutValidator       = $this->createMock( CheckoutValidator::class );
		$this->orderUpdater            = $this->createMock( OrderUpdater::class );
		$this->WCCart                  = $this->createMock( WC_Cart::class );

		$this->checkout = new Checkout(
			$this->wpAdapter,
			$this->wcAdapter,
			$this->carrierOptionsFactory,
			$this->optionsProvider,
			$this->orderRepository,
			$this->currencySwitcherService,
			$this->rateCalculator,
			$this->carrierEntityRepository,
			$this->paymentHelper,
			$this->checkoutService,
			$this->checkoutRenderer,
			$this->checkoutEventHandler,
			$this->cartService,
			$this->sessionService,
			$this->checkoutValidator,
			$this->orderUpdater,
		);
	}

	public function testDoesNothingIfNoChosenShippingMethod(): void {
		$this->creatCheckout();
		$this->checkoutService->method( 'calculateShippingAndGetOptionId' )->willReturn( null );

		$this->rateCalculator->expects( $this->never() )->method( 'getCODSurcharge' );

		$this->checkout->actionCalculateFees( $this->WCCart );
	}

	public function testDoesNothingIfShippingMethodNotPacketery(): void {
		$this->creatCheckout();
		$this->checkoutService->method( 'calculateShippingAndGetOptionId' )->willReturn( 'non_packetery_method' );
		$this->checkoutService->method( 'isPacketeryShippingMethod' )->willReturn( false );

		$this->rateCalculator->expects( $this->never() )->method( 'getCODSurcharge' );

		$this->checkout->actionCalculateFees( $this->WCCart );
	}

	public function testSkipsWhenCouponAllowsFreeShipping(): void {
		$this->creatCheckout();
		$this->checkoutService->method( 'calculateShippingAndGetOptionId' )->willReturn( 'packetery_method' );
		$this->checkoutService->method( 'isPacketeryShippingMethod' )->willReturn( true );

		$carrierOptions = $this->createMock( Options::class );
		$carrierOptions->method( 'hasCouponFreeShippingForFeesAllowed' )->willReturn( true );

		$this->carrierOptionsFactory->method( 'createByOptionId' )->willReturn( $carrierOptions );
		$this->rateCalculator->method( 'isFreeShippingCouponApplied' )->willReturn( true );

		$this->rateCalculator->expects( $this->never() )->method( 'getCODSurcharge' );
		$this->checkout->actionCalculateFees( $this->WCCart );
	}

	public function testAddsFeesSuccessfully(): void {
		$this->creatCheckout();
		$this->checkoutService->method( 'calculateShippingAndGetOptionId' )->willReturn( 'packetery_method' );
		$this->checkoutService->method( 'isPacketeryShippingMethod' )->willReturn( true );

		$carrierOptions = $this->createMock( Options::class );
		$carrierOptions->method( 'hasCouponFreeShippingForFeesAllowed' )->willReturn( false );
		$carrierOptions->method( 'toArray' )->willReturn( [ 'key' => 'value' ] );

		$this->carrierOptionsFactory->method( 'createByOptionId' )->willReturn( $carrierOptions );
		$this->rateCalculator->method( 'isFreeShippingCouponApplied' )->willReturn( false );

		$this->cartService->method( 'getTaxClassWithMaxRate' )->willReturn( 'standard' );
		$this->rateCalculator
			->method( 'getCODSurcharge' )
			->with( [ 'key' => 'value' ], $this->anything() )
			->willReturn( 5.00 );

		$carrier = $this->createMock( Carrier::class );
		$carrier->method( 'supportsAgeVerification' )->willReturn( true );
		$this->carrierEntityRepository->method( 'getAnyById' )->willReturn( $carrier );
		$carrierOptions->method( 'getAgeVerificationFee' )->willReturn( 10.0 );
		$this->cartService->method( 'isAgeVerificationRequired' )->willReturn( true );

		$this->sessionService->method( 'getChosenPaymentMethod' )->willReturn( 'cod' );
		$this->paymentHelper->method( 'isCodPaymentMethod' )->willReturn( true );
		$this->currencySwitcherService->method( 'getConvertedPrice' )->willReturn( 10.0 );
		$this->wpAdapter->method( '__' )->willReturn( 'COD surcharge' );

		$this->WCCart->expects( self::exactly( 2 ) )->method( 'add_fee' );

		$this->checkout->actionCalculateFees( $this->WCCart );
	}
}
