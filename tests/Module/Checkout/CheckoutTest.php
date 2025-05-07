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
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\Repository;
use Packetery\Module\Payment\PaymentHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;
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
	private SessionService|MockObject $sessionService;
	private CheckoutValidator|MockObject $checkoutValidator;
	private OrderUpdater|MockObject $orderUpdater;
	private Checkout $checkout;
	private WC_Cart|MockObject $WCCart;

	protected function createCheckout(): void {
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
			$this->cartService,
			$this->sessionService,
			$this->checkoutValidator,
			$this->orderUpdater,
		);
	}

	public function testDoesNothingIfNoChosenShippingMethod(): void {
		$this->createCheckout();
		$this->checkoutService->method( 'calculateShippingAndGetOptionId' )->willReturn( null );

		$this->rateCalculator->expects( $this->never() )->method( 'getCODSurcharge' );

		$this->checkout->actionCalculateFees( $this->WCCart );
	}

	public function testDoesNothingIfShippingMethodNotPacketery(): void {
		$this->createCheckout();
		$this->checkoutService->method( 'calculateShippingAndGetOptionId' )->willReturn( 'non_packetery_method' );
		$this->checkoutService->method( 'isPacketeryShippingMethod' )->willReturn( false );

		$this->rateCalculator->expects( $this->never() )->method( 'getCODSurcharge' );

		$this->checkout->actionCalculateFees( $this->WCCart );
	}

	public function testSkipsWhenCouponAllowsFreeShipping(): void {
		$this->createCheckout();
		$this->checkoutService->method( 'calculateShippingAndGetOptionId' )->willReturn( 'packetery_method' );
		$this->checkoutService->method( 'isPacketeryShippingMethod' )->willReturn( true );

		$carrierOptions = $this->createMock( Carrier\Options::class );
		$carrierOptions->method( 'hasCouponFreeShippingForFeesAllowed' )->willReturn( true );

		$this->carrierOptionsFactory->method( 'createByOptionId' )->willReturn( $carrierOptions );
		$this->rateCalculator->method( 'isFreeShippingCouponApplied' )->willReturn( true );

		$this->rateCalculator->expects( $this->never() )->method( 'getCODSurcharge' );
		$this->checkout->actionCalculateFees( $this->WCCart );
	}

	public function testAddsFeesSuccessfully(): void {
		$this->createCheckout();
		$this->checkoutService->method( 'calculateShippingAndGetOptionId' )->willReturn( 'packetery_method' );
		$this->checkoutService->method( 'isPacketeryShippingMethod' )->willReturn( true );

		$carrierOptions = $this->createMock( Carrier\Options::class );
		$carrierOptions->method( 'hasCouponFreeShippingForFeesAllowed' )->willReturn( false );
		$carrierOptions->method( 'toArray' )->willReturn( [ 'key' => 'value' ] );

		$this->carrierOptionsFactory->method( 'createByOptionId' )->willReturn( $carrierOptions );
		$this->rateCalculator->method( 'isFreeShippingCouponApplied' )->willReturn( false );

		$this->cartService->method( 'getTaxClassWithMaxRate' )->willReturn( 'standard' );
		$this->rateCalculator
			->method( 'getCODSurcharge' )
			->with( [ 'key' => 'value' ], $this->anything() )
			->willReturn( 5.00 );

		$carrier = $this->createMock( Entity\Carrier::class );
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

	public static function filterPaymentGatewaysProvider(): array {
		$gateway1   = (object) [ 'id' => 'gateway1' ];
		$gateway2   = (object) [ 'id' => 'gateway2' ];
		$codGateway = (object) [ 'id' => 'cod_gateway' ];

		$carrierSupportingCod = DummyFactory::createCarrierCzechPp();
		$carrierWithoutCod    = DummyFactory::createCarDeliveryCarrier();

		$order = DummyFactory::createOrderCzPp();

		return [
			'no packetery method selected'             => [
				'availableGateways'        => [ $gateway1, $gateway2, $codGateway ],
				'orderPayParameter'        => null,
				'order'                    => null,
				'chosenMethod'             => null,
				'isPacketeryMethod'        => false,
				'carrier'                  => null,
				'disallowedPaymentMethods' => [],
				'codPaymentMethods'        => [],
				'expectedResult'           => [ $gateway1, $gateway2, $codGateway ],
			],
			'packetery method with valid carrier'      => [
				'availableGateways'        => [ $gateway1, $gateway2, $codGateway ],
				'orderPayParameter'        => 123,
				'order'                    => $order,
				'chosenMethod'             => 'dummyRate',
				'isPacketeryMethod'        => true,
				'carrier'                  => $carrierSupportingCod,
				'disallowedPaymentMethods' => [],
				'codPaymentMethods'        => [
					'cod_gateway',
				],
				'expectedResult'           => [ $gateway1, $gateway2, $codGateway ],
			],
			'packetery method with disallowed gateway' => [
				'availableGateways'        => [ $gateway1, $gateway2, $codGateway ],
				'orderPayParameter'        => null,
				'order'                    => null,
				'chosenMethod'             => 'dummyRate',
				'isPacketeryMethod'        => true,
				'carrier'                  => $carrierSupportingCod,
				'disallowedPaymentMethods' => [
					'gateway1',
				],
				'codPaymentMethods'        => [
					'cod_gateway',
				],
				'expectedResult'           => [ $gateway2, $codGateway ],
			],
			'packetery method, carrier without COD support' => [
				'availableGateways'        => [ $gateway1, $gateway2, $codGateway ],
				'orderPayParameter'        => null,
				'order'                    => null,
				'chosenMethod'             => 'dummyRate',
				'isPacketeryMethod'        => true,
				'carrier'                  => $carrierWithoutCod,
				'disallowedPaymentMethods' => [],
				'codPaymentMethods'        => [
					'cod_gateway',
				],
				'expectedResult'           => [ $gateway1, $gateway2 ],
			],
			'packetery method with invalid carrier'    => [
				'availableGateways'        => [ $gateway1, $gateway2, $codGateway ],
				'orderPayParameter'        => null,
				'order'                    => null,
				'chosenMethod'             => 'dummyRate',
				'isPacketeryMethod'        => true,
				'carrier'                  => null,
				'disallowedPaymentMethods' => [],
				'codPaymentMethods'        => [
					'cod_gateway',
				],
				'expectedResult'           => [ $gateway1, $gateway2, $codGateway ],
			],
		];
	}

	/**
	 * @dataProvider filterPaymentGatewaysProvider
	 */
	public function testFilterPaymentGateways(
		array $availableGateways,
		?int $orderPayParameter,
		?Order $order,
		?string $chosenMethod,
		bool $isPacketeryMethod,
		?Entity\Carrier $carrier,
		array $disallowedPaymentMethods,
		array $codPaymentMethods,
		array $expectedResult
	): void {
		$this->createCheckout();

		$this->checkoutService
			->method( 'getOrderPayParameter' )
			->willReturn( $orderPayParameter );

		$this->orderRepository
			->method( 'getByIdWithValidCarrier' )
			->willReturn( $order );

		$this->checkoutService
			->method( 'isPacketeryShippingMethod' )
			->willReturn( $isPacketeryMethod );

		if ( $carrier !== null ) {
			$this->checkoutService
				->method( 'getCarrierIdFromPacketeryShippingMethod' )
				->willReturn( $carrier->getId() );
		}

		$this->carrierEntityRepository
			->method( 'getAnyById' )
			->willReturn( $carrier );

		if ( $chosenMethod !== null ) {
			$this->sessionService
				->method( 'getChosenMethodFromSession' )
				->willReturn( $chosenMethod );
		}

		$carrierOptions = $this->createMock( Carrier\Options::class );
		$this->carrierOptionsFactory
			->method( 'createByCarrierId' )
			->willReturn( $carrierOptions );

		$carrierOptions
			->method( 'hasCheckoutPaymentMethodDisallowed' )
			->willReturnCallback(
				function ( $gatewayId ) use ( $disallowedPaymentMethods ) {
					return in_array( $gatewayId, $disallowedPaymentMethods, true );
				}
			);

		$this->paymentHelper
			->method( 'isCodPaymentMethod' )
			->willReturnCallback(
				function ( $gatewayId ) use ( $codPaymentMethods ) {
					return in_array( $gatewayId, $codPaymentMethods, true );
				}
			);

		$result = $this->checkout->filterPaymentGateways( $availableGateways );

		$this->assertEqualsCanonicalizing( $expectedResult, $result );
	}
}
