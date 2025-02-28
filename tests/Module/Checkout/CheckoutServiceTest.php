<?php

declare( strict_types=1 );

namespace Tests\Module\Checkout;

use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Checkout\CheckoutService;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\ShippingMethod;
use Packetery\Nette\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WC_Shipping_Rate;

class CheckoutServiceTest extends TestCase {

	const SHIPPING_RATE_INTERNAL_PICKUP_POINTS = 'packetery_carrier_zpointcz';

	private WcAdapter|MockObject $wcAdapter;
	private Carrier\Repository|MockObject $carrierRepository;
	private Carrier\EntityRepository|MockObject $carrierEntityRepository;
	private CarDeliveryConfig|MockObject $carDeliveryConfig;
	private OptionsProvider|MockObject $provider;
	private Request|MockObject $httpRequest;
	private PacketaPickupPointsConfig|MockObject $pickupPointsConfig;
	private CheckoutService $checkoutService;

	private function createCheckoutServiceMock(): void {
		$this->wcAdapter               = $this->createMock( WcAdapter::class );
		$this->carrierRepository       = $this->createMock( Carrier\Repository::class );
		$this->carrierEntityRepository = $this->createMock( Carrier\EntityRepository::class );
		$this->carDeliveryConfig       = $this->createMock( CarDeliveryConfig::class );
		$this->provider                = $this->createMock( OptionsProvider::class );
		$this->httpRequest             = $this->createMock( Request::class );
		$this->pickupPointsConfig      = $this->createMock( PacketaPickupPointsConfig::class );

		$this->checkoutService = new CheckoutService(
			$this->wcAdapter,
			$this->httpRequest,
			$this->carDeliveryConfig,
			$this->carrierRepository,
			$this->carrierEntityRepository,
			$this->pickupPointsConfig,
			$this->provider,
		);
	}

	public function testCalculateShippingAndGetOptionIdReturnsEmptyStringWhenNoShippingRatesExists(): void {
		$this->createCheckoutServiceMock();

		$this->wcAdapter->method( 'cartCalculateShipping' )->willReturn( [] );
		$this->assertEquals( '', $this->checkoutService->calculateShippingAndGetOptionId() );
	}

	public function testCalculateShippingAndGetOptionIdReturnsShippingRateIdStrippedOfPrefix(): void {
		$this->createCheckoutServiceMock();

		$shippingRateId     = self::SHIPPING_RATE_INTERNAL_PICKUP_POINTS;
		$shippingRateFullId = ShippingMethod::PACKETERY_METHOD_ID . ':' . $shippingRateId;
		$mockedShippingRate = $this->createMock( WC_Shipping_Rate::class );
		$mockedShippingRate->method( 'get_id' )->willReturn( $shippingRateFullId );
		$this->wcAdapter->method( 'cartCalculateShipping' )->willReturn( [ $mockedShippingRate ] );

		$this->assertEquals( $shippingRateId, $this->checkoutService->calculateShippingAndGetOptionId() );
	}

	public function testGetChosenMethodWhenPostShippingMethodIsNull(): void {
		$this->createCheckoutServiceMock();

		$this->httpRequest->method( 'getPost' )->with( 'shipping_method' )->willReturn( null );

		$this->assertEquals( '', $this->checkoutService->resolveChosenMethod() );
	}

	public function testGetChosenMethodWhenPostShippingMethodIsNotNull(): void {
		$this->createCheckoutServiceMock();

		$shippingRateId     = self::SHIPPING_RATE_INTERNAL_PICKUP_POINTS;
		$shippingRateFullId = ShippingMethod::PACKETERY_METHOD_ID . ':' . $shippingRateId;
		$this->httpRequest->method( 'getPost' )->with( 'shipping_method' )->willReturn( [ $shippingRateFullId ] );

		$this->assertEquals( $shippingRateId, $this->checkoutService->resolveChosenMethod() );
	}

	public function testGetShippingMethodOptionIdWithValueNotContainingPrefix(): void {
		$this->createCheckoutServiceMock();
		$chosenMethod = 'dummyRate';
		$this->assertEquals( 'dummyRate', $this->checkoutService->getShippingMethodOptionId( $chosenMethod ) );
	}

	public function testGetShippingMethodOptionIdWithValueContainingPrefix(): void {
		$this->createCheckoutServiceMock();
		$shippingRateId     = self::SHIPPING_RATE_INTERNAL_PICKUP_POINTS;
		$shippingRateFullId = ShippingMethod::PACKETERY_METHOD_ID . ':' . $shippingRateId;
		$this->assertEquals( $shippingRateId, $this->checkoutService->getShippingMethodOptionId( $shippingRateFullId ) );
	}

	public function testGetShippingMethodOptionIdWithEmptyValue(): void {
		$this->createCheckoutServiceMock();
		$this->assertEquals( '', $this->checkoutService->getShippingMethodOptionId( '' ) );
	}

	public function testIsPacketeryShippingMethodReturnsFalseWhenGivenInvalidOptionId(): void {
		$this->createCheckoutServiceMock();
		$invalidOptionId = 'third_party_carrier_dummy';

		$this->assertFalse( $this->checkoutService->isPacketeryShippingMethod( $invalidOptionId ) );
	}

	public function testIsPacketeryShippingMethodReturnsTrueWhenGivenValidOptionId(): void {
		$this->createCheckoutServiceMock();
		$validOptionId = 'packetery_carrier_dummy';

		$this->assertTrue( $this->checkoutService->isPacketeryShippingMethod( $validOptionId ) );
	}

	public function testGetCarrierIdFromShippingMethodNonPacketery(): void {
		$this->createCheckoutServiceMock();

		$this->assertNull( $this->checkoutService->getCarrierIdFromShippingMethod( 'dummy_carrier' ) );
	}

	public function testGetCarrierIdFromShippingMethodPacketery(): void {
		$this->createCheckoutServiceMock();

		$shippingMethod = self::SHIPPING_RATE_INTERNAL_PICKUP_POINTS;
		$this->assertEquals( 'zpointcz', $this->checkoutService->getCarrierIdFromShippingMethod( $shippingMethod ) );
	}

	public function testGetCarrierIdFromShippingMethodEmpty(): void {
		$this->createCheckoutServiceMock();

		$this->assertNull( $this->checkoutService->getCarrierIdFromShippingMethod( '' ) );
	}

	public function testGetCarrierIdFromPacketeryShippingMethod(): void {
		$this->createCheckoutServiceMock();
		$expected = 'zpointcz';
		$this->assertEquals(
			$expected,
			$this->checkoutService->getCarrierIdFromPacketeryShippingMethod(
				self::SHIPPING_RATE_INTERNAL_PICKUP_POINTS
			)
		);
	}

	public function testIsPickupPointOrderWhenNoMethod(): void {
		$this->createCheckoutServiceMock();

		$this->httpRequest->method( 'getPost' )->willReturn( null );
		$this->wcAdapter->method( 'cartCalculateShipping' )->willReturn( [] );

		$this->assertFalse( $this->checkoutService->isPickupPointOrder() );
	}

	public function testIsPickupPointOrderWhenItIsNot(): void {
		$this->createCheckoutServiceMock();
		$this->httpRequest->method( 'getPost' )->willReturn( [ 'dummy_method' ] );
		$this->pickupPointsConfig->method( 'isInternalPickupPointCarrier' )->willReturn( false );
		$this->carrierRepository->method( 'hasPickupPoints' )->willReturn( false );

		$this->assertFalse( $this->checkoutService->isPickupPointOrder() );
	}

	public function testIsPickupPointOrderWhenItIs(): void {
		$this->createCheckoutServiceMock();
		$this->httpRequest->method( 'getPost' )->willReturn( [ 'packetery_carrier_3060' ] );
		$this->pickupPointsConfig->method( 'isInternalPickupPointCarrier' )->willReturn( false );
		$this->carrierRepository->method( 'hasPickupPoints' )->willReturn( true );

		$this->assertTrue( $this->checkoutService->isPickupPointOrder() );
	}

	public function testIsPickupPointOrderWhenItIsInternal(): void {
		$this->createCheckoutServiceMock();
		$this->httpRequest->method( 'getPost' )->willReturn( [ self::SHIPPING_RATE_INTERNAL_PICKUP_POINTS ] );
		$this->pickupPointsConfig->method( 'isInternalPickupPointCarrier' )->willReturn( true );
		$this->carrierRepository->method( 'hasPickupPoints' )->willReturn( false );

		$this->assertTrue( $this->checkoutService->isPickupPointOrder() );
	}

	public function testIsHomeDeliveryOrderWithHomeDelivery(): void {
		$this->createCheckoutServiceMock();

		$this->httpRequest->method( 'getPost' )->willReturn( [ 'packetery_carrier_106' ] );
		$this->carrierEntityRepository->method( 'isHomeDeliveryCarrier' )->willReturn( true );

		$this->assertTrue( $this->checkoutService->isHomeDeliveryOrder() );
	}

	public function testIsHomeDeliveryOrderWithNonHomeDelivery(): void {
		$this->createCheckoutServiceMock();

		$this->httpRequest->method( 'getPost' )->willReturn( [ self::SHIPPING_RATE_INTERNAL_PICKUP_POINTS ] );
		$this->carrierEntityRepository->method( 'isHomeDeliveryCarrier' )->willReturn( false );

		$this->assertFalse( $this->checkoutService->isHomeDeliveryOrder() );
	}

	public function testIsHomeDeliveryOrderWithNoChosenMethod(): void {
		$this->createCheckoutServiceMock();

		$this->httpRequest->method( 'getPost' )->willReturn( null );

		$this->assertFalse( $this->checkoutService->isHomeDeliveryOrder() );
	}

	public function testIsHomeDeliveryOrderWithEmptyChosenMethod(): void {
		$this->createCheckoutServiceMock();

		$this->httpRequest->method( 'getPost' )->willReturn( [ '' ] );

		$this->assertFalse( $this->checkoutService->isHomeDeliveryOrder() );
	}

	public function testIsCarDeliveryOrderWithCarDelivery(): void {
		$this->createCheckoutServiceMock();

		$this->httpRequest->method( 'getPost' )->willReturn( [ 'packetery_carrier_25061' ] );
		$this->carDeliveryConfig->method( 'isCarDeliveryCarrier' )->willReturn( true );

		$this->assertTrue( $this->checkoutService->isCarDeliveryOrder() );
	}

	public function testIsCarDeliveryOrderWithNonCarDelivery(): void {
		$this->createCheckoutServiceMock();

		$this->httpRequest->method( 'getPost' )->willReturn( [ self::SHIPPING_RATE_INTERNAL_PICKUP_POINTS ] );
		$this->carDeliveryConfig->method( 'isCarDeliveryCarrier' )->willReturn( false );

		$this->assertFalse( $this->checkoutService->isCarDeliveryOrder() );
	}

	public function testIsCarDeliveryOrderWithNoChosenMethod(): void {
		$this->createCheckoutServiceMock();

		$this->httpRequest->method( 'getPost' )->willReturn( null );

		$this->assertFalse( $this->checkoutService->isCarDeliveryOrder() );
	}

	public function testIsCarDeliveryOrderWithEmptyChosenMethod(): void {
		$this->createCheckoutServiceMock();

		$this->httpRequest->method( 'getPost' )->willReturn( [ '' ] );

		$this->assertFalse( $this->checkoutService->isCarDeliveryOrder() );
	}

	public function testGetCustomerCountryWhenCustomerShippingCountryIsNotNull(): void {
		$this->createCheckoutServiceMock();

		$expectedCountry = 'cz';
		$this->wcAdapter->method( 'customerGetShippingCountry' )->willReturn( strtoupper( $expectedCountry ) );

		$this->assertEquals( $expectedCountry, $this->checkoutService->getCustomerCountry() );
	}

	public function testGetCustomerCountryWhenCustomerShippingCountryIsNull(): void {
		$this->createCheckoutServiceMock();

		$expectedCountry = 'cz';
		$this->wcAdapter->method( 'customerGetShippingCountry' )->willReturn( null );
		$this->wcAdapter->method( 'customerGetBillingCountry' )->willReturn( strtoupper( $expectedCountry ) );

		$this->assertEquals( $expectedCountry, $this->checkoutService->getCustomerCountry() );
	}

	public function testGetCustomerCountryWhenCustomerShippingCountryAndCustomerBillingCountryAreNull(): void {
		$this->createCheckoutServiceMock();

		$this->wcAdapter->method( 'customerGetShippingCountry' )->willReturn( null );
		$this->wcAdapter->method( 'customerGetBillingCountry' )->willReturn( null );

		$this->assertEquals( '', $this->checkoutService->getCustomerCountry() );
	}

	public function testAreBlocksUsedInCheckoutBlockDetection(): void {
		$this->createCheckoutServiceMock();

		$this->provider->method( 'getCheckoutDetection' )->willReturn( OptionsProvider::BLOCK_CHECKOUT_DETECTION );
		$this->assertTrue( $this->checkoutService->areBlocksUsedInCheckout() );
	}

	public function testAreBlocksUsedInCheckoutClassicDetection(): void {
		$this->createCheckoutServiceMock();

		$this->provider->method( 'getCheckoutDetection' )->willReturn( OptionsProvider::CLASSIC_CHECKOUT_DETECTION );
		$this->assertFalse( $this->checkoutService->areBlocksUsedInCheckout() );
	}

	public function testAreBlocksUsedInCheckoutAutomaticDetectionWithBlock(): void {
		$this->createCheckoutServiceMock();

		$this->provider->method( 'getCheckoutDetection' )->willReturn( OptionsProvider::AUTOMATIC_CHECKOUT_DETECTION );

		$this->wcAdapter->method( 'hasBlockInPage' )->willReturn( true );
		$this->assertTrue( $this->checkoutService->areBlocksUsedInCheckout() );
	}

	public function testAreBlocksUsedInCheckoutAutomaticDetectionWithoutBlock(): void {
		$this->createCheckoutServiceMock();

		$this->provider->method( 'getCheckoutDetection' )->willReturn( OptionsProvider::AUTOMATIC_CHECKOUT_DETECTION );

		$this->wcAdapter->method( 'hasBlockInPage' )->willReturn( false );
		$this->assertFalse( $this->checkoutService->areBlocksUsedInCheckout() );
	}
}
