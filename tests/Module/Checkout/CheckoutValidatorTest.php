<?php

namespace Tests\Module\Checkout;

use Packetery\Core\Entity\Carrier;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\Options;
use Packetery\Module\Checkout\CartService;
use Packetery\Module\Checkout\CheckoutService;
use Packetery\Module\Checkout\CheckoutStorage;
use Packetery\Module\Checkout\CheckoutValidator;
use Packetery\Module\Checkout\SessionService;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Order\PickupPointValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WC_Order;
use WC_Order_Item_Fee;

class CheckoutValidatorTest extends TestCase {
	private CheckoutService&MockObject $checkoutService;
	private WpAdapter&MockObject $wpAdapter;
	private CartService&MockObject $cartService;
	private EntityRepository&MockObject $carrierEntityRepository;
	private CarrierOptionsFactory&MockObject $carrierOptionsFactory;
	private SessionService&MockObject $sessionService;
	private CheckoutStorage&MockObject $checkoutStorage;
	private WC_Order&MockObject $order;
	private CheckoutValidator $checkoutValidator;

	protected function createCheckoutValidatorMock(): void {
		$pickupPointValidator          = $this->createMock( PickupPointValidator::class );
		$this->checkoutService         = $this->createMock( CheckoutService::class );
		$this->wpAdapter               = $this->createMock( WpAdapter::class );
		$wcAdapter                     = $this->createMock( WcAdapter::class );
		$this->cartService             = $this->createMock( CartService::class );
		$this->carrierEntityRepository = $this->createMock( EntityRepository::class );
		$this->carrierOptionsFactory   = $this->createMock( CarrierOptionsFactory::class );
		$this->sessionService          = $this->createMock( SessionService::class );
		$this->checkoutStorage         = $this->createMock( CheckoutStorage::class );

		$this->order = $this->createMock( WC_Order::class );

		$this->order->method( 'get_fees' )->willReturn( $this->getMockFees() );

		$this->checkoutValidator = new CheckoutValidator(
			$pickupPointValidator,
			$this->wpAdapter,
			$wcAdapter,
			$this->checkoutService,
			$this->cartService,
			$this->sessionService,
			$this->checkoutStorage,
			$this->carrierOptionsFactory,
			$this->carrierEntityRepository,
		);
	}

	public function testValidatePacketeryFeesInOrderRemovesCODFeeWhenSurchargeIsZero(): void {
		$this->createCheckoutValidatorMock();
		$this->setupMocksForCODFee( 0.0 );

		$this->order->expects( $this->exactly( 2 ) )->method( 'remove_item' )->with( 0 );
		$this->order->expects( $this->exactly( 2 ) )->method( 'calculate_totals' );

		$this->checkoutValidator->validatePacketeryFeesInOrder( $this->order );
	}

	public function testValidatePacketeryFeesInOrderRemovesAgeVerificationFeeWhenConditionsAreNotMet(): void {
		$this->createCheckoutValidatorMock();
		$this->setupMocksForAgeVerificationFee( false );
		$this->setupMocksForCODFee( 10.0 );

		$this->order->expects( $this->once() )->method( 'remove_item' );
		$this->order->expects( $this->once() )->method( 'calculate_totals' );

		$this->checkoutValidator->validatePacketeryFeesInOrder( $this->order );
	}

	public function testValidatePacketeryFeesInOrderDoesNotRemoveAgeVerificationFeeWhenConditionsAreMet(): void {
		$this->createCheckoutValidatorMock();
		$this->setupMocksForAgeVerificationFee( true );
		$this->setupMocksForCODFee( 10.0 );

		$this->order->expects( $this->never() )->method( 'remove_item' );

		$this->checkoutValidator->validatePacketeryFeesInOrder( $this->order );
	}

	public function testValidatePacketeryFeesInOrderDoesNothingWhenCarrierIsNull(): void {
		$this->createCheckoutValidatorMock();
		$this->checkoutService->method( 'resolveChosenMethod' )->willReturn( 'some_shipping_method' );
		$this->checkoutService->method( 'getCarrierIdFromPacketeryShippingMethod' )->willReturn( '123' );
		$this->carrierEntityRepository->method( 'getAnyById' )->willReturn( null );

		$this->order->expects( $this->never() )->method( 'remove_item' );
		$this->order->expects( $this->never() )->method( 'calculate_totals' );

		$this->checkoutValidator->validatePacketeryFeesInOrder( $this->order );
	}

	private function setupMocksForCODFee( float $surcharge ): void {
		$this->setupBasicMocks();
		$this->checkoutService->method( 'getApplicableSurcharge' )->willReturn( $surcharge );
	}

	private function setupMocksForAgeVerificationFee( bool $isRequired ): void {
		$this->setupBasicMocks();
		$this->cartService->method( 'isAgeVerificationRequired' )->willReturn( $isRequired );
	}

	private function setupBasicMocks(): void {
		$this->checkoutService->method( 'resolveChosenMethod' )->willReturn( 'some_shipping_method' );
		$this->checkoutService->method( 'getCarrierIdFromPacketeryShippingMethod' )->willReturn( '123' );
		$this->sessionService->method( 'getChosenPaymentMethod' )->willReturn( 'cod' );

		$carrier = $this->createMock( Carrier::class );
		$carrier->method( 'supportsAgeVerification' )->willReturn( true );
		$this->carrierEntityRepository->method( 'getAnyById' )->willReturn( $carrier );

		$carrierOptions = $this->createMock( Options::class );
		$carrierOptions->method( 'getAgeVerificationFee' )->willReturn( 10.0 );
		$this->carrierOptionsFactory->method( 'createByCarrierId' )->willReturn( $carrierOptions );
	}

	private function getMockFees(): array {
		return [
			$this->createMockFee( 'COD surcharge', 0 ),
			$this->createMockFee( 'Age verification fee', 1 ),
		];
	}

	private function createMockFee( string $name, int $key ): WC_Order_Item_Fee {
		$this->wpAdapter->method( '__' )->willReturn( $name );
		$fee = $this->createMock( WC_Order_Item_Fee::class );
		$fee->method( 'get_name' )->willReturn( $name );

		return $fee;
	}
}
