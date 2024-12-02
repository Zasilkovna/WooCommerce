<?php

declare( strict_types=1 );

namespace Tests\Module\Checkout;

use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Checkout\CheckoutService;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\ShippingMethod;
use Packetery\Nette\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Module\MockFactory;
use WC_Shipping_Rate;

class CheckoutServiceTest extends TestCase {

	private WpAdapter|MockObject $wpAdapter;
	private WpAdapter|MockObject $wcAdapter;
	private WpAdapter|MockObject $carrierEntityRepository;
	private WpAdapter|MockObject $carDeliveryConfig;
	private WpAdapter|MockObject $provider;
	private CheckoutService $resolver;

	private function createCheckoutServiceMock(): void {
		$this->wpAdapter               = MockFactory::createWpAdapter( $this );
		$this->wcAdapter               = $this->createMock( WcAdapter::class );
		$this->carrierEntityRepository = $this->createMock( Carrier\EntityRepository::class );
		$this->carDeliveryConfig       = $this->createMock( CarDeliveryConfig::class );
		$this->provider                = $this->createMock( OptionsProvider::class );
		$this->httpRequest             = $this->createMock( Request::class );

		$this->resolver = new CheckoutService(
			$this->wpAdapter,
			$this->wcAdapter,
			$this->httpRequest,
			$this->carDeliveryConfig,
			$this->createMock( Carrier\Repository::class ),
			$this->carrierEntityRepository,
			$this->createMock( PacketaPickupPointsConfig::class ),
			$this->provider,
		);
	}

	public function testCalculateShippingAndGetIdReturnsEmptyStringWhenNoShippingRatesExists(): void {
		$this->createCheckoutServiceMock();

		$this->wcAdapter->method( 'cartCalculateShipping' )->willReturn( [] );
		$this->assertEquals( '', $this->resolver->calculateShippingAndGetId() );
	}

	public function testCalculateShippingAndGetIdReturnsShippingRateIdStrippedOfPrefix(): void {
		$this->createCheckoutServiceMock();

		$shippingRateId     = 'packetery_carrier_zpointcz';
		$shippingRateFullId = ShippingMethod::PACKETERY_METHOD_ID . ':' . $shippingRateId;
		$mockedShippingRate = $this->createMock( WC_Shipping_Rate::class );
		$mockedShippingRate->method( 'get_id' )->willReturn( $shippingRateFullId );
		$this->wcAdapter->method( 'cartCalculateShipping' )->willReturn( [ $mockedShippingRate ] );

		$this->assertEquals( $shippingRateId, $this->resolver->calculateShippingAndGetId() );
	}

	public function testGetChosenMethodWhenPostShippingMethodIsNull(): void {
		$this->createCheckoutServiceMock();

		$this->httpRequest->method( 'getPost' )->with( 'shipping_method' )->willReturn( null );

		$this->assertEquals( '', $this->resolver->getChosenMethod() );
	}

	public function testGetChosenMethodWhenPostShippingMethodIsNotNull(): void {
		$this->createCheckoutServiceMock();

		$shippingRateId     = 'packetery_carrier_zpointcz';
		$shippingRateFullId = ShippingMethod::PACKETERY_METHOD_ID . ':' . $shippingRateId;
		$this->httpRequest->method( 'getPost' )->with( 'shipping_method' )->willReturn( [ $shippingRateFullId ] );

		$this->assertEquals( $shippingRateId, $this->resolver->getChosenMethod() );
	}

	public function testAreBlocksUsedInCheckoutBlockDetection(): void {
		$this->createCheckoutServiceMock();

		$this->provider->method( 'getCheckoutDetection' )->willReturn( OptionsProvider::BLOCK_CHECKOUT_DETECTION );
		$this->assertTrue( $this->resolver->areBlocksUsedInCheckout() );
	}

	public function testAreBlocksUsedInCheckoutClassicDetection(): void {
		$this->createCheckoutServiceMock();

		$this->provider->method( 'getCheckoutDetection' )->willReturn( OptionsProvider::CLASSIC_CHECKOUT_DETECTION );
		$this->assertFalse( $this->resolver->areBlocksUsedInCheckout() );
	}

	public function testAreBlocksUsedInCheckoutAutomaticDetectionWithBlock(): void {
		$this->createCheckoutServiceMock();

		$this->provider->method( 'getCheckoutDetection' )->willReturn( OptionsProvider::AUTOMATIC_CHECKOUT_DETECTION );

		$this->wpAdapter->method( 'hasBlock' )->willReturn( true );
		$this->assertTrue( $this->resolver->areBlocksUsedInCheckout() );
	}

	public function testAreBlocksUsedInCheckoutAutomaticDetectionWithoutBlock(): void {
		$this->createCheckoutServiceMock();

		$this->provider->method( 'getCheckoutDetection' )->willReturn( OptionsProvider::AUTOMATIC_CHECKOUT_DETECTION );

		$this->wpAdapter->method( 'hasBlock' )->willReturn( false );
		$this->assertFalse( $this->resolver->areBlocksUsedInCheckout() );
	}
}
