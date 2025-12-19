<?php

namespace Tests\Module\Checkout;

use Packetery\Core\Entity;
use Packetery\Core\Entity\Order;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Checkout\CartService;
use Packetery\Module\Checkout\Checkout;
use Packetery\Module\Checkout\CheckoutRenderer;
use Packetery\Module\Checkout\CheckoutService;
use Packetery\Module\Checkout\CheckoutValidator;
use Packetery\Module\Checkout\CurrencySwitcherService;
use Packetery\Module\Checkout\OrderUpdater;
use Packetery\Module\Checkout\RateCalculator;
use Packetery\Module\Checkout\SessionService;
use Packetery\Module\DiagnosticsLogger\DiagnosticsLogger;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Log\ArgumentTypeErrorLogger;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\Repository;
use Packetery\Module\Payment\PaymentHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WC_Cart;
use WC_Payment_Gateway;

class CheckoutTest extends TestCase {
	private WpAdapter&MockObject $wpAdapterMock;
	private SessionService&MockObject $sessionServiceMock;
	private CartService&MockObject $cartServiceMock;
	private PaymentHelper&MockObject $paymentHelperMock;
	private EntityRepository&MockObject $carrierEntityRepositoryMock;
	private RateCalculator&MockObject $rateCalculatorMock;
	private CurrencySwitcherService&MockObject $currencySwitcherServiceMock;
	private OptionsProvider&MockObject $optionsProviderMock;
	private CarrierOptionsFactory&MockObject $carrierOptionsFactoryMock;
	private Repository&MockObject $orderRepositoryMock;
	private WcAdapter&MockObject $wcAdapterMock;
	private CheckoutService&MockObject $checkoutServiceMock;

	private function createCheckout(): Checkout {
		$this->wpAdapterMock               = $this->createMock( WpAdapter::class );
		$this->wcAdapterMock               = $this->createMock( WcAdapter::class );
		$this->carrierOptionsFactoryMock   = $this->createMock( CarrierOptionsFactory::class );
		$this->optionsProviderMock         = $this->createMock( OptionsProvider::class );
		$this->orderRepositoryMock         = $this->createMock( Repository::class );
		$this->currencySwitcherServiceMock = $this->createMock( CurrencySwitcherService::class );
		$this->rateCalculatorMock          = $this->createMock( RateCalculator::class );
		$this->carrierEntityRepositoryMock = $this->createMock( EntityRepository::class );
		$this->paymentHelperMock           = $this->createMock( PaymentHelper::class );
		$this->checkoutServiceMock         = $this->createMock( CheckoutService::class );
		$checkoutRendererMock              = $this->createMock( CheckoutRenderer::class );
		$this->cartServiceMock             = $this->createMock( CartService::class );
		$this->sessionServiceMock          = $this->createMock( SessionService::class );
		$checkoutValidatorMock             = $this->createMock( CheckoutValidator::class );
		$orderUpdaterMock                  = $this->createMock( OrderUpdater::class );

		$this->wpAdapterMock
			->method( '__' )
			->willReturnCallback(
				static function ( $text ) {
					return $text;
				}
			);

		return new Checkout(
			$this->wpAdapterMock,
			$this->wcAdapterMock,
			$this->carrierOptionsFactoryMock,
			$this->optionsProviderMock,
			$this->orderRepositoryMock,
			$this->currencySwitcherServiceMock,
			$this->rateCalculatorMock,
			$this->carrierEntityRepositoryMock,
			$this->paymentHelperMock,
			$this->checkoutServiceMock,
			$checkoutRendererMock,
			$this->cartServiceMock,
			$this->sessionServiceMock,
			$checkoutValidatorMock,
			$orderUpdaterMock,
			$this->createMock( DiagnosticsLogger::class ),
			$this->createMock( ArgumentTypeErrorLogger::class )
		);
	}

	public function testActionCalculateFeesHappyPathWithAgeVerificationAndCodFees(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$carrierMock = $this->createMock( Entity\Carrier::class );
		$carrierMock->method( 'supportsAgeVerification' )->willReturn( true );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'getAgeVerificationFee' )->willReturn( 50.0 );
		$carrierOptionsMock->method( 'hasCouponFreeShippingForFeesAllowed' )->willReturn( false );
		$carrierOptionsMock->method( 'toArray' )->willReturn( [] );

		$this->checkoutServiceMock->method( 'calculateShippingAndGetOptionId' )->willReturn( 'packetery_carrier_123' );
		$this->checkoutServiceMock->method( 'isPacketeryShippingMethod' )->willReturn( true );
		$this->checkoutServiceMock->method( 'getCarrierIdFromPacketeryShippingMethod' )->willReturn( '123' );
		$this->checkoutServiceMock->method( 'areBlocksUsedInCheckout' )->willReturn( false );

		$this->carrierOptionsFactoryMock->method( 'createByOptionId' )->willReturn( $carrierOptionsMock );

		$this->carrierEntityRepositoryMock->method( 'getAnyById' )->willReturn( $carrierMock );

		$this->cartServiceMock->method( 'isAgeVerificationRequired' )->willReturn( true );
		$this->cartServiceMock->method( 'getTaxClassWithMaxRate' )->willReturn( 'standard' );

		$this->paymentHelperMock->method( 'isCodPaymentMethod' )->willReturn( true );

		$this->sessionServiceMock->method( 'getChosenPaymentMethod' )->willReturn( 'cod' );

		$this->rateCalculatorMock->method( 'isFreeShippingCouponApplied' )->willReturn( false );
		$this->rateCalculatorMock->method( 'getCODSurcharge' )->willReturn( 30.0 );

		$this->currencySwitcherServiceMock->method( 'getConvertedPrice' )->willReturn( 30.0 );

		$this->optionsProviderMock->method( 'arePricesTaxInclusive' )->willReturn( true );

		$this->wcAdapterMock->method( 'cart' )->willReturn( $cartMock );
		$this->wcAdapterMock->method( 'cartGetSubtotal' )->willReturn( 1000.0 );
		$this->wcAdapterMock->method( 'calcTax' )->willReturn( [ 5.206612 ] );

		$callsAddFee = [];
		$cartMock->expects( $this->exactly( 2 ) )
				->method( 'add_fee' )
				->willReturnCallback(
					function ( ...$args ) use ( &$callsAddFee ) {
						$callsAddFee[] = $args;
					}
				);

		$checkout->actionCalculateFees( $cartMock );

		$this->assertCount( 2, $callsAddFee );

		$this->assertEquals( 24.793388, $callsAddFee[0][1] );
		$this->assertTrue( $callsAddFee[0][2] );
		$this->assertEquals( 'standard', $callsAddFee[0][3] );

		$this->assertEquals( 24.793388, $callsAddFee[1][1] );
		$this->assertTrue( $callsAddFee[1][2] );
		$this->assertEquals( 'standard', $callsAddFee[1][3] );
	}

	public function testActionCalculateFeesWithShippingFreeCoupon(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$carrierMock = $this->createMock( Entity\Carrier::class );
		$carrierMock->method( 'supportsAgeVerification' )->willReturn( true );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'getAgeVerificationFee' )->willReturn( 50.0 );
		$carrierOptionsMock->method( 'hasCouponFreeShippingForFeesAllowed' )->willReturn( true );
		$carrierOptionsMock->method( 'toArray' )->willReturn( [] );

		$this->checkoutServiceMock->method( 'calculateShippingAndGetOptionId' )->willReturn( 'packetery_carrier_123' );
		$this->checkoutServiceMock->method( 'isPacketeryShippingMethod' )->willReturn( true );
		$this->checkoutServiceMock->method( 'getCarrierIdFromPacketeryShippingMethod' )->willReturn( '123' );
		$this->checkoutServiceMock->method( 'areBlocksUsedInCheckout' )->willReturn( false );

		$this->carrierOptionsFactoryMock->method( 'createByOptionId' )->willReturn( $carrierOptionsMock );

		$this->carrierEntityRepositoryMock->method( 'getAnyById' )->willReturn( $carrierMock );

		$this->cartServiceMock->method( 'getTaxClassWithMaxRate' )->willReturn( 'standard' );

		$this->rateCalculatorMock->method( 'isFreeShippingCouponApplied' )->willReturn( true );

		$this->wcAdapterMock->method( 'cart' )->willReturn( $cartMock );

		$cartMock->expects( self::never() )->method( 'add_fee' );

		$checkout->actionCalculateFees( $cartMock );
	}

	public function testActionCalculateFeesWhenChosenShippingMethodOptionIdIsNull(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$this->checkoutServiceMock->method( 'calculateShippingAndGetOptionId' )->willReturn( null );

		$cartMock->expects( self::never() )->method( 'add_fee' );

		$checkout->actionCalculateFees( $cartMock );
	}

	public function testActionCalculateFeesWhenIsPacketeryShippingMethodReturnsFalse(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$this->checkoutServiceMock->method( 'calculateShippingAndGetOptionId' )->willReturn( 'some_other_carrier_123' );
		$this->checkoutServiceMock->method( 'isPacketeryShippingMethod' )->willReturn( false );

		$cartMock->expects( self::never() )->method( 'add_fee' );

		$checkout->actionCalculateFees( $cartMock );
	}

	public function testAddAgeVerificationFeeHappyPath(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$carrierMock = $this->createMock( Entity\Carrier::class );
		$carrierMock->method( 'supportsAgeVerification' )->willReturn( true );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'getAgeVerificationFee' )->willReturn( 50.0 );

		$this->cartServiceMock->method( 'isAgeVerificationRequired' )->willReturn( true );
		$this->currencySwitcherServiceMock->method( 'getConvertedPrice' )->willReturn( 50.0 );
		$this->optionsProviderMock->method( 'arePricesTaxInclusive' )->willReturn( false );

		$cartMock->expects( $this->once() )
				->method( 'add_fee' )
				->with( 'Age verification fee', 50.0, true, 'standard' );

		$reflection = new \ReflectionClass( $checkout );
		$method     = $reflection->getMethod( 'addAgeVerificationFee' );
		$method->setAccessible( true );
		$method->invoke( $checkout, $cartMock, $carrierMock, $carrierOptionsMock, true, 'standard' );
	}

	public function testAddAgeVerificationFeeWhenCarrierIsNull(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );

		$cartMock->expects( self::never() )->method( 'add_fee' );

		$reflection = new \ReflectionClass( $checkout );
		$method     = $reflection->getMethod( 'addAgeVerificationFee' );
		$method->setAccessible( true );
		$method->invoke( $checkout, $cartMock, null, $carrierOptionsMock, true, 'standard' );
	}

	public function testAddAgeVerificationFeeWhenCarrierDoesNotSupportAgeVerification(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$carrierMock = $this->createMock( Entity\Carrier::class );
		$carrierMock->method( 'supportsAgeVerification' )->willReturn( false );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );

		$cartMock->expects( self::never() )->method( 'add_fee' );

		$reflection = new \ReflectionClass( $checkout );
		$method     = $reflection->getMethod( 'addAgeVerificationFee' );
		$method->setAccessible( true );
		$method->invoke( $checkout, $cartMock, $carrierMock, $carrierOptionsMock, true, 'standard' );
	}

	public function testAddAgeVerificationFeeWhenFeeIsNull(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$carrierMock = $this->createMock( Entity\Carrier::class );
		$carrierMock->method( 'supportsAgeVerification' )->willReturn( true );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'getAgeVerificationFee' )->willReturn( null );

		$cartMock->expects( self::never() )->method( 'add_fee' );

		$reflection = new \ReflectionClass( $checkout );
		$method     = $reflection->getMethod( 'addAgeVerificationFee' );
		$method->setAccessible( true );
		$method->invoke( $checkout, $cartMock, $carrierMock, $carrierOptionsMock, true, 'standard' );
	}

	public function testAddAgeVerificationFeeWhenAgeVerificationNotRequired(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$carrierMock = $this->createMock( Entity\Carrier::class );
		$carrierMock->method( 'supportsAgeVerification' )->willReturn( true );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'getAgeVerificationFee' )->willReturn( 50.0 );

		$this->cartServiceMock->method( 'isAgeVerificationRequired' )->willReturn( false );

		$cartMock->expects( self::never() )->method( 'add_fee' );

		$reflection = new \ReflectionClass( $checkout );
		$method     = $reflection->getMethod( 'addAgeVerificationFee' );
		$method->setAccessible( true );
		$method->invoke( $checkout, $cartMock, $carrierMock, $carrierOptionsMock, true, 'standard' );
	}

	public function testAddAgeVerificationFeeWithTaxInclusivePricing(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$carrierMock = $this->createMock( Entity\Carrier::class );
		$carrierMock->method( 'supportsAgeVerification' )->willReturn( true );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'getAgeVerificationFee' )->willReturn( 50.0 );

		$this->cartServiceMock->method( 'isAgeVerificationRequired' )->willReturn( true );
		$this->currencySwitcherServiceMock->method( 'getConvertedPrice' )->willReturn( 50.0 );
		$this->optionsProviderMock->method( 'arePricesTaxInclusive' )->willReturn( true );
		$this->wcAdapterMock->method( 'calcTax' )->willReturn( [ 8.33 ] );

		$cartMock->expects( $this->once() )
				->method( 'add_fee' )
				->with( 'Age verification fee', 41.67, true, 'standard' );

		$reflection = new \ReflectionClass( $checkout );
		$method     = $reflection->getMethod( 'addAgeVerificationFee' );
		$method->setAccessible( true );
		$method->invoke( $checkout, $cartMock, $carrierMock, $carrierOptionsMock, true, 'standard' );
	}

	public function testAddAgeVerificationFeeWithNonTaxableScenario(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$carrierMock = $this->createMock( Entity\Carrier::class );
		$carrierMock->method( 'supportsAgeVerification' )->willReturn( true );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'getAgeVerificationFee' )->willReturn( 50.0 );

		$this->cartServiceMock->method( 'isAgeVerificationRequired' )->willReturn( true );
		$this->currencySwitcherServiceMock->method( 'getConvertedPrice' )->willReturn( 50.0 );
		$cartMock->expects( $this->once() )
				->method( 'add_fee' )
				->with( 'Age verification fee', 50.0, false, null );

		$reflection = new \ReflectionClass( $checkout );
		$method     = $reflection->getMethod( 'addAgeVerificationFee' );
		$method->setAccessible( true );
		$method->invoke( $checkout, $cartMock, $carrierMock, $carrierOptionsMock, false, null );
	}

	public function testAddCodSurchargeFeeHappyPathWithBlocksCheckout(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'toArray' )->willReturn( [] );

		$this->checkoutServiceMock->method( 'areBlocksUsedInCheckout' )->willReturn( true );
		$this->wcAdapterMock->method( 'sessionGetString' )->with( 'packetery_checkout_payment_method' )->willReturn( 'cod' );
		$this->paymentHelperMock->method( 'isCodPaymentMethod' )->willReturn( true );
		$this->rateCalculatorMock->method( 'getCODSurcharge' )->willReturn( 30.0 );
		$this->wcAdapterMock->method( 'cartGetSubtotal' )->willReturn( 1000.0 );
		$this->currencySwitcherServiceMock->method( 'getConvertedPrice' )->willReturn( 30.0 );
		$this->optionsProviderMock->method( 'arePricesTaxInclusive' )->willReturn( false );

		$cartMock->expects( $this->once() )
				->method( 'add_fee' )
				->with( 'COD surcharge', 30.0, true, 'standard' );

		$reflection = new \ReflectionClass( $checkout );
		$method     = $reflection->getMethod( 'addCodSurchargeFee' );
		$method->setAccessible( true );
		$method->invoke( $checkout, $cartMock, $carrierOptionsMock, true, 'standard' );
	}

	public function testAddCodSurchargeFeeHappyPathWithClassicCheckout(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'toArray' )->willReturn( [] );

		$this->checkoutServiceMock->method( 'areBlocksUsedInCheckout' )->willReturn( false );
		$this->sessionServiceMock->method( 'getChosenPaymentMethod' )->willReturn( 'cod' );
		$this->paymentHelperMock->method( 'isCodPaymentMethod' )->willReturn( true );
		$this->rateCalculatorMock->method( 'getCODSurcharge' )->willReturn( 25.0 );
		$this->wcAdapterMock->method( 'cartGetSubtotal' )->willReturn( 800.0 );
		$this->currencySwitcherServiceMock->method( 'getConvertedPrice' )->willReturn( 25.0 );
		$this->optionsProviderMock->method( 'arePricesTaxInclusive' )->willReturn( false );

		$cartMock->expects( $this->once() )
				->method( 'add_fee' )
				->with( 'COD surcharge', 25.0, true, 'standard' );

		$reflection = new \ReflectionClass( $checkout );
		$method     = $reflection->getMethod( 'addCodSurchargeFee' );
		$method->setAccessible( true );
		$method->invoke( $checkout, $cartMock, $carrierOptionsMock, true, 'standard' );
	}

	public function testAddCodSurchargeFeeWhenPaymentMethodIsNull(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );

		$this->checkoutServiceMock->method( 'areBlocksUsedInCheckout' )->willReturn( false );
		$this->sessionServiceMock->method( 'getChosenPaymentMethod' )->willReturn( null );

		$cartMock->expects( self::never() )->method( 'add_fee' );

		$reflection = new \ReflectionClass( $checkout );
		$method     = $reflection->getMethod( 'addCodSurchargeFee' );
		$method->setAccessible( true );
		$method->invoke( $checkout, $cartMock, $carrierOptionsMock, true, 'standard' );
	}

	public function testAddCodSurchargeFeeWhenPaymentMethodIsNotCod(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );

		$this->checkoutServiceMock->method( 'areBlocksUsedInCheckout' )->willReturn( false );
		$this->sessionServiceMock->method( 'getChosenPaymentMethod' )->willReturn( 'gopay' );
		$this->paymentHelperMock->method( 'isCodPaymentMethod' )->willReturn( false );

		$cartMock->expects( self::never() )->method( 'add_fee' );

		$reflection = new \ReflectionClass( $checkout );
		$method     = $reflection->getMethod( 'addCodSurchargeFee' );
		$method->setAccessible( true );
		$method->invoke( $checkout, $cartMock, $carrierOptionsMock, true, 'standard' );
	}

	public function testAddCodSurchargeFeeWhenSurchargeIsZero(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'toArray' )->willReturn( [] );

		$this->checkoutServiceMock->method( 'areBlocksUsedInCheckout' )->willReturn( false );
		$this->sessionServiceMock->method( 'getChosenPaymentMethod' )->willReturn( 'cod' );
		$this->paymentHelperMock->method( 'isCodPaymentMethod' )->willReturn( true );
		$this->rateCalculatorMock->method( 'getCODSurcharge' )->willReturn( 0.0 );
		$this->wcAdapterMock->method( 'cartGetSubtotal' )->willReturn( 1000.0 );
		$this->currencySwitcherServiceMock->method( 'getConvertedPrice' )->willReturn( 0.0 );

		$cartMock->expects( self::never() )->method( 'add_fee' );

		$reflection = new \ReflectionClass( $checkout );
		$method     = $reflection->getMethod( 'addCodSurchargeFee' );
		$method->setAccessible( true );
		$method->invoke( $checkout, $cartMock, $carrierOptionsMock, true, 'standard' );
	}

	public function testAddCodSurchargeFeeWhenSurchargeIsNegative(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'toArray' )->willReturn( [] );

		$this->checkoutServiceMock->method( 'areBlocksUsedInCheckout' )->willReturn( false );
		$this->sessionServiceMock->method( 'getChosenPaymentMethod' )->willReturn( 'cod' );
		$this->paymentHelperMock->method( 'isCodPaymentMethod' )->willReturn( true );
		$this->rateCalculatorMock->method( 'getCODSurcharge' )->willReturn( -5.0 );
		$this->wcAdapterMock->method( 'cartGetSubtotal' )->willReturn( 1000.0 );
		$this->currencySwitcherServiceMock->method( 'getConvertedPrice' )->willReturn( -5.0 );

		$cartMock->expects( self::never() )->method( 'add_fee' );

		$reflection = new \ReflectionClass( $checkout );
		$method     = $reflection->getMethod( 'addCodSurchargeFee' );
		$method->setAccessible( true );
		$method->invoke( $checkout, $cartMock, $carrierOptionsMock, true, 'standard' );
	}

	public function testAddCodSurchargeFeeWithTaxInclusivePricing(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'toArray' )->willReturn( [] );

		$this->checkoutServiceMock->method( 'areBlocksUsedInCheckout' )->willReturn( false );
		$this->sessionServiceMock->method( 'getChosenPaymentMethod' )->willReturn( 'cod' );
		$this->paymentHelperMock->method( 'isCodPaymentMethod' )->willReturn( true );
		$this->rateCalculatorMock->method( 'getCODSurcharge' )->willReturn( 30.0 );
		$this->wcAdapterMock->method( 'cartGetSubtotal' )->willReturn( 1000.0 );
		$this->currencySwitcherServiceMock->method( 'getConvertedPrice' )->willReturn( 30.0 );
		$this->optionsProviderMock->method( 'arePricesTaxInclusive' )->willReturn( true );
		$this->wcAdapterMock->method( 'calcTax' )->willReturn( [ 5.206612 ] );

		$cartMock->expects( $this->once() )
				->method( 'add_fee' )
				->with( 'COD surcharge', 24.793388, true, 'standard' );

		$reflection = new \ReflectionClass( $checkout );
		$method     = $reflection->getMethod( 'addCodSurchargeFee' );
		$method->setAccessible( true );
		$method->invoke( $checkout, $cartMock, $carrierOptionsMock, true, 'standard' );
	}

	public function testAddCodSurchargeFeeWithNonTaxableScenario(): void {
		$checkout = $this->createCheckout();
		$cartMock = $this->createMock( WC_Cart::class );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'toArray' )->willReturn( [] );

		$this->checkoutServiceMock->method( 'areBlocksUsedInCheckout' )->willReturn( false );
		$this->sessionServiceMock->method( 'getChosenPaymentMethod' )->willReturn( 'cod' );
		$this->paymentHelperMock->method( 'isCodPaymentMethod' )->willReturn( true );
		$this->rateCalculatorMock->method( 'getCODSurcharge' )->willReturn( 20.0 );
		$this->wcAdapterMock->method( 'cartGetSubtotal' )->willReturn( 500.0 );
		$this->currencySwitcherServiceMock->method( 'getConvertedPrice' )->willReturn( 20.0 );

		$cartMock->expects( $this->once() )
				->method( 'add_fee' )
				->with( 'COD surcharge', 20.0, false, null );

		$reflection = new \ReflectionClass( $checkout );
		$method     = $reflection->getMethod( 'addCodSurchargeFee' );
		$method->setAccessible( true );
		$method->invoke( $checkout, $cartMock, $carrierOptionsMock, false, null );
	}

	public function testFilterPaymentGatewaysWhenAvailableGatewaysIsNotArray(): void {
		$checkout = $this->createCheckout();

		$result = $checkout->filterPaymentGateways( 'not_an_array' );

		$this->assertEquals( 'not_an_array', $result );
	}

	public function testFilterPaymentGatewaysWhenChosenMethodIsNotPacketeryShippingMethod(): void {
		$checkout = $this->createCheckout();

		$gatewayGopayMock     = $this->createMock( WC_Payment_Gateway::class );
		$gatewayGopayMock->id = 'gopay';
		$gatewayCodMock       = $this->createMock( WC_Payment_Gateway::class );
		$gatewayCodMock->id   = 'cod';

		$availableGateways = [
			'gopay' => $gatewayGopayMock,
			'cod'   => $gatewayCodMock,
		];

		$this->checkoutServiceMock->method( 'getOrderPayParameter' )->willReturn( null );
		$this->sessionServiceMock->method( 'getChosenMethodFromSession' )->willReturn( 'other_shipping_method' );
		$this->checkoutServiceMock->method( 'isPacketeryShippingMethod' )->willReturn( false );

		$result = $checkout->filterPaymentGateways( $availableGateways );

		$this->assertEquals( $availableGateways, $result );
	}

	public function testFilterPaymentGatewaysWhenCarrierIsNull(): void {
		$checkout = $this->createCheckout();

		$gatewayGopayMock     = $this->createMock( WC_Payment_Gateway::class );
		$gatewayGopayMock->id = 'gopay';
		$gatewayCodMock       = $this->createMock( WC_Payment_Gateway::class );
		$gatewayCodMock->id   = 'cod';

		$availableGateways = [
			'gopay' => $gatewayGopayMock,
			'cod'   => $gatewayCodMock,
		];

		$this->checkoutServiceMock->method( 'getOrderPayParameter' )->willReturn( null );
		$this->sessionServiceMock->method( 'getChosenMethodFromSession' )->willReturn( 'packetery_carrier_123' );
		$this->checkoutServiceMock->method( 'isPacketeryShippingMethod' )->willReturn( true );
		$this->checkoutServiceMock->method( 'getCarrierIdFromPacketeryShippingMethod' )->willReturn( '123' );
		$this->carrierEntityRepositoryMock->method( 'getAnyById' )->willReturn( null );

		$result = $checkout->filterPaymentGateways( $availableGateways );

		$this->assertEquals( $availableGateways, $result );
	}

	public function testFilterPaymentGatewaysFiltersCodWhenCarrierDoesNotSupportCod(): void {
		$checkout = $this->createCheckout();

		$gatewayGopayMock     = $this->createMock( WC_Payment_Gateway::class );
		$gatewayGopayMock->id = 'gopay';
		$gatewayCodMock       = $this->createMock( WC_Payment_Gateway::class );
		$gatewayCodMock->id   = 'cod';

		$availableGateways = [
			'gopay' => $gatewayGopayMock,
			'cod'   => $gatewayCodMock,
		];

		$carrierMock = $this->createMock( Entity\Carrier::class );
		$carrierMock->method( 'supportsCod' )->willReturn( false );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'hasCheckoutPaymentMethodDisallowed' )->willReturn( false );

		$this->checkoutServiceMock->method( 'getOrderPayParameter' )->willReturn( null );
		$this->sessionServiceMock->method( 'getChosenMethodFromSession' )->willReturn( 'packetery_carrier_123' );
		$this->checkoutServiceMock->method( 'isPacketeryShippingMethod' )->willReturn( true );
		$this->checkoutServiceMock->method( 'getCarrierIdFromPacketeryShippingMethod' )->willReturn( '123' );
		$this->carrierEntityRepositoryMock->method( 'getAnyById' )->willReturn( $carrierMock );
		$this->carrierOptionsFactoryMock->method( 'createByCarrierId' )->willReturn( $carrierOptionsMock );
		$this->paymentHelperMock->method( 'isCodPaymentMethod' )
								->willReturnCallback(
									function ( $id ) {
										return $id === 'cod';
									}
								);

		$result = $checkout->filterPaymentGateways( $availableGateways );

		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'gopay', $result );
		$this->assertArrayNotHasKey( 'cod', $result );
	}

	public function testFilterPaymentGatewaysFiltersDisallowedPaymentMethods(): void {
		$checkout = $this->createCheckout();

		$gatewayGopayMock     = $this->createMock( WC_Payment_Gateway::class );
		$gatewayGopayMock->id = 'gopay';
		$gatewayCodMock       = $this->createMock( WC_Payment_Gateway::class );
		$gatewayCodMock->id   = 'paypal';

		$availableGateways = [
			'gopay'  => $gatewayGopayMock,
			'paypal' => $gatewayCodMock,
		];

		$carrierMock = $this->createMock( Entity\Carrier::class );
		$carrierMock->method( 'supportsCod' )->willReturn( true );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'hasCheckoutPaymentMethodDisallowed' )
							->willReturnCallback(
								function ( $id ) {
									return $id === 'paypal';
								}
							);

		$this->checkoutServiceMock->method( 'getOrderPayParameter' )->willReturn( null );
		$this->sessionServiceMock->method( 'getChosenMethodFromSession' )->willReturn( 'packetery_carrier_123' );
		$this->checkoutServiceMock->method( 'isPacketeryShippingMethod' )->willReturn( true );
		$this->checkoutServiceMock->method( 'getCarrierIdFromPacketeryShippingMethod' )->willReturn( '123' );
		$this->carrierEntityRepositoryMock->method( 'getAnyById' )->willReturn( $carrierMock );
		$this->carrierOptionsFactoryMock->method( 'createByCarrierId' )->willReturn( $carrierOptionsMock );
		$this->paymentHelperMock->method( 'isCodPaymentMethod' )->willReturn( false );

		$result = $checkout->filterPaymentGateways( $availableGateways );

		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'gopay', $result );
		$this->assertArrayNotHasKey( 'paypal', $result );
	}

	public function testFilterPaymentGatewaysWithOrderPayScenario(): void {
		$checkout = $this->createCheckout();

		$gatewayGopayMock     = $this->createMock( WC_Payment_Gateway::class );
		$gatewayGopayMock->id = 'gopay';
		$gatewayCodMock       = $this->createMock( WC_Payment_Gateway::class );
		$gatewayCodMock->id   = 'cod';

		$availableGateways = [
			'gopay' => $gatewayGopayMock,
			'cod'   => $gatewayCodMock,
		];

		$orderCarrierMock = $this->createMock( Entity\Carrier::class );
		$orderCarrierMock->method( 'getId' )->willReturn( '456' );

		$orderMock = $this->createMock( Order::class );
		$orderMock->method( 'getCarrier' )->willReturn( $orderCarrierMock );

		$carrierMock = $this->createMock( Entity\Carrier::class );
		$carrierMock->method( 'supportsCod' )->willReturn( true );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'hasCheckoutPaymentMethodDisallowed' )->willReturn( false );

		$this->checkoutServiceMock->method( 'getOrderPayParameter' )->willReturn( '123' );
		$this->orderRepositoryMock->method( 'getByIdWithValidCarrier' )->willReturn( $orderMock );
		$this->checkoutServiceMock->method( 'isPacketeryShippingMethod' )->willReturn( true );
		$this->checkoutServiceMock->method( 'getCarrierIdFromPacketeryShippingMethod' )->willReturn( '456' );
		$this->carrierEntityRepositoryMock->method( 'getAnyById' )->willReturn( $carrierMock );
		$this->carrierOptionsFactoryMock->method( 'createByCarrierId' )->willReturn( $carrierOptionsMock );
		$this->paymentHelperMock->method( 'isCodPaymentMethod' )->willReturn( false );

		$result = $checkout->filterPaymentGateways( $availableGateways );

		$this->assertEquals( $availableGateways, $result );
	}

	public function testFilterPaymentGatewaysWithInvalidGatewayInstance(): void {
		$checkout = $this->createCheckout();

		$gatewayGopayMock     = $this->createMock( WC_Payment_Gateway::class );
		$gatewayGopayMock->id = 'gopay';
		$invalidGateway       = 'not_a_gateway_object';

		$availableGateways = [
			'gopay'   => $gatewayGopayMock,
			'invalid' => $invalidGateway,
		];

		$carrierMock = $this->createMock( Entity\Carrier::class );
		$carrierMock->method( 'supportsCod' )->willReturn( true );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'hasCheckoutPaymentMethodDisallowed' )->willReturn( false );

		$this->checkoutServiceMock->method( 'getOrderPayParameter' )->willReturn( null );
		$this->sessionServiceMock->method( 'getChosenMethodFromSession' )->willReturn( 'packetery_carrier_123' );
		$this->checkoutServiceMock->method( 'isPacketeryShippingMethod' )->willReturn( true );
		$this->checkoutServiceMock->method( 'getCarrierIdFromPacketeryShippingMethod' )->willReturn( '123' );
		$this->carrierEntityRepositoryMock->method( 'getAnyById' )->willReturn( $carrierMock );
		$this->carrierOptionsFactoryMock->method( 'createByCarrierId' )->willReturn( $carrierOptionsMock );
		$this->paymentHelperMock->method( 'isCodPaymentMethod' )->willReturn( false );

		$result = $checkout->filterPaymentGateways( $availableGateways );

		$this->assertEquals( $availableGateways, $result );
	}

	public function testFilterPaymentGatewaysHappyPathNoFiltering(): void {
		$checkout = $this->createCheckout();

		$gatewayGopayMock     = $this->createMock( WC_Payment_Gateway::class );
		$gatewayGopayMock->id = 'gopay';
		$gatewayCodMock       = $this->createMock( WC_Payment_Gateway::class );
		$gatewayCodMock->id   = 'cod';

		$availableGateways = [
			'gopay' => $gatewayGopayMock,
			'cod'   => $gatewayCodMock,
		];

		$carrierMock = $this->createMock( Entity\Carrier::class );
		$carrierMock->method( 'supportsCod' )->willReturn( true );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'hasCheckoutPaymentMethodDisallowed' )->willReturn( false );

		$this->checkoutServiceMock->method( 'getOrderPayParameter' )->willReturn( null );
		$this->sessionServiceMock->method( 'getChosenMethodFromSession' )->willReturn( 'packetery_carrier_123' );
		$this->checkoutServiceMock->method( 'isPacketeryShippingMethod' )->willReturn( true );
		$this->checkoutServiceMock->method( 'getCarrierIdFromPacketeryShippingMethod' )->willReturn( '123' );
		$this->carrierEntityRepositoryMock->method( 'getAnyById' )->willReturn( $carrierMock );
		$this->carrierOptionsFactoryMock->method( 'createByCarrierId' )->willReturn( $carrierOptionsMock );
		$this->paymentHelperMock->method( 'isCodPaymentMethod' )
								->willReturnCallback(
									function ( $id ) {
										return $id === 'cod';
									}
								);

		$result = $checkout->filterPaymentGateways( $availableGateways );

		$this->assertEquals( $availableGateways, $result );
	}

	public function testFilterPaymentGatewaysMultipleFilteringConditions(): void {
		$checkout = $this->createCheckout();

		$gatewayGopayMock      = $this->createMock( WC_Payment_Gateway::class );
		$gatewayGopayMock->id  = 'gopay';
		$gatewayCodMock        = $this->createMock( WC_Payment_Gateway::class );
		$gatewayCodMock->id    = 'cod';
		$gatewayPaypalMock     = $this->createMock( WC_Payment_Gateway::class );
		$gatewayPaypalMock->id = 'paypal';

		$availableGateways = [
			'gopay'  => $gatewayGopayMock,
			'cod'    => $gatewayCodMock,
			'paypal' => $gatewayPaypalMock,
		];

		$carrierMock = $this->createMock( Entity\Carrier::class );
		$carrierMock->method( 'supportsCod' )->willReturn( false );

		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'hasCheckoutPaymentMethodDisallowed' )
							->willReturnCallback(
								function ( $id ) {
									return $id === 'paypal';
								}
							);

		$this->checkoutServiceMock->method( 'getOrderPayParameter' )->willReturn( null );
		$this->sessionServiceMock->method( 'getChosenMethodFromSession' )->willReturn( 'packetery_carrier_123' );
		$this->checkoutServiceMock->method( 'isPacketeryShippingMethod' )->willReturn( true );
		$this->checkoutServiceMock->method( 'getCarrierIdFromPacketeryShippingMethod' )->willReturn( '123' );
		$this->carrierEntityRepositoryMock->method( 'getAnyById' )->willReturn( $carrierMock );
		$this->carrierOptionsFactoryMock->method( 'createByCarrierId' )->willReturn( $carrierOptionsMock );
		$this->paymentHelperMock->method( 'isCodPaymentMethod' )
								->willReturnCallback(
									function ( $id ) {
										return $id === 'cod';
									}
								);

		$result = $checkout->filterPaymentGateways( $availableGateways );

		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'gopay', $result );
		$this->assertArrayNotHasKey( 'cod', $result );
		$this->assertArrayNotHasKey( 'paypal', $result );
	}
}
