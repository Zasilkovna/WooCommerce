<?php

declare( strict_types=1 );

namespace Tests\Module\Checkout;

use Packetery\Module\Checkout\SessionService;
use Packetery\Module\Framework\WcAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SessionServiceTest extends TestCase {

	private SessionService $sessionService;
	private WcAdapter|MockObject $wcAdapter;

	private function createSessionServiceMock(): void {
		$this->wcAdapter      = $this->createMock( WcAdapter::class );
		$this->sessionService = new SessionService( $this->wcAdapter );
	}

	public function testGetChosenMethodFromSession(): void {
		$this->createSessionServiceMock();

		$this->wcAdapter
			->method( 'session' )
			->willReturn( true );

		$this->wcAdapter
			->method( 'sessionGetArray' )
			->with( $this->equalTo( 'chosen_shipping_methods' ) )
			->willReturn( [ 'dummy_shipping_method' ] );

		$this->assertEquals( 'dummy_shipping_method', $this->sessionService->getChosenMethodFromSession() );
	}

	public function testGetChosenMethodFromSessionWithNoShippingMethods(): void {
		$this->createSessionServiceMock();

		$this->wcAdapter
			->method( 'session' )
			->willReturn( true );

		$this->wcAdapter
			->method( 'sessionGetArray' )
			->with( $this->equalTo( 'chosen_shipping_methods' ) )
			->willReturn( [] );

		$this->assertEquals( '', $this->sessionService->getChosenMethodFromSession() );
	}

	public function testGetChosenMethodFromSessionWithFalseShippingMethod(): void {
		$this->createSessionServiceMock();

		$this->wcAdapter
			->method( 'session' )
			->willReturn( true );

		$this->wcAdapter
			->method( 'sessionGetArray' )
			->with( $this->equalTo( 'chosen_shipping_methods' ) )
			->willReturn( [ false ] );

		$this->assertEquals( '', $this->sessionService->getChosenMethodFromSession() );
	}

	public function testGetChosenMethodFromSessionWithoutSession(): void {
		$this->createSessionServiceMock();

		$this->wcAdapter
			->method( 'session' )
			->willReturn( null );

		$this->assertEquals( '', $this->sessionService->getChosenMethodFromSession() );
	}

	public function testGetChosenPaymentMethodChosen(): void {
		$this->createSessionServiceMock();

		$this->wcAdapter
			->method( 'sessionGetString' )
			->with( $this->equalTo( 'chosen_payment_method' ) )
			->willReturn( 'dummy_shipping_method' );

		$this->assertEquals( 'dummy_shipping_method', $this->sessionService->getChosenPaymentMethod() );
	}

	public function testGetChosenPaymentMethodNotChosen(): void {
		$this->createSessionServiceMock();

		$this->wcAdapter
			->method( 'sessionGetString' )
			->with( $this->equalTo( 'chosen_payment_method' ) )
			->willReturn( null );

		$this->assertNull( $this->sessionService->getChosenPaymentMethod() );
	}

	public function testActionUpdateShippingRates(): void {
		$this->createSessionServiceMock();
		$this->wcAdapter
			->expects( $this->exactly( 2 ) )
			->method( 'sessionSet' );
		$this->wcAdapter
			->method( 'shippingGetPackages' )
			->willReturn(
				[
					1 => [],
					2 => [],
				]
			);
		$this->sessionService->actionUpdateShippingRates();
	}

	public function testFilterUpdateShippingPackagesWithPaymentMethod(): void {
		$this->createSessionServiceMock();

		$this->wcAdapter
			->method( 'sessionGetString' )
			->with( $this->equalTo( 'chosen_payment_method' ) )
			->willReturn( 'dummy_payment_method' );

		$packages = [
			[ 'random_data' => 'foo' ],
			[ 'random_data' => 'bar' ],
		];

		$expectedPackages = [
			[
				'random_data'              => 'foo',
				'packetery_payment_method' => 'dummy_payment_method',
			],
			[
				'random_data'              => 'bar',
				'packetery_payment_method' => 'dummy_payment_method',
			],
		];

		$this->assertEquals(
			$expectedPackages,
			$this->sessionService->filterUpdateShippingPackages( $packages )
		);
	}

	public function testFilterUpdateShippingPackagesWithoutPaymentMethod(): void {
		$this->createSessionServiceMock();

		$this->wcAdapter
			->method( 'sessionGetString' )
			->with( $this->equalTo( 'chosen_payment_method' ) )
			->willReturn( null );

		$packages = [
			[ 'random_data' => 'foo' ],
			[ 'random_data' => 'bar' ],
		];

		$expectedPackages = [
			[
				'random_data'              => 'foo',
				'packetery_payment_method' => null,
			],
			[
				'random_data'              => 'bar',
				'packetery_payment_method' => null,
			],
		];

		$this->assertEquals(
			$expectedPackages,
			$this->sessionService->filterUpdateShippingPackages( $packages )
		);
	}
}
