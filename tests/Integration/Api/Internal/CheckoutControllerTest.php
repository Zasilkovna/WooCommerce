<?php

declare( strict_types=1 );

namespace Tests\Integration\Api\Internal;

use Packetery\Module\Api\Internal\CheckoutRouter;
use Packetery\Module\Api\Registrar;
use Packetery\Module\Checkout\CheckoutStorage;
use Packetery\Module\Order\Attribute;
use ReflectionClass;
use Tests\Integration\AbstractIntegrationTestCase;
use WP_REST_Request;

class CheckoutControllerTest extends AbstractIntegrationTestCase {
	/**
	 * @return array{CheckoutRouter, CheckoutStorage}
	 */
	private function registerRoutes(): array {
		/** @var Registrar $registrar */
		$registrar = $this->container->getByType( Registrar::class );
		add_action( 'rest_api_init', [ $registrar, 'registerRoutes' ] );
		/**
		 * Register routes on the required WordPress hook to avoid notices and trigger the hook so routes are actually registered for this test run.
		 *
		 * @since 1.0
		 */
		do_action( 'rest_api_init' );

		/** @var CheckoutRouter $router */
		$router = $this->container->getByType( CheckoutRouter::class );
		/** @var CheckoutStorage $storage */
		$storage = $this->container->getByType( CheckoutStorage::class );

		$storage->deleteTransient();

		return [ $router, $storage ];
	}

	protected function tearDown(): void {
		remove_all_actions( 'rest_api_init' );
		parent::tearDown();
	}

	private function buildPickupPointPayload( string $rateId ): array {
		return array_merge(
			[
				'packetery_rate_id' => $rateId,
			],
			[
				Attribute::POINT_ID     => 'PP123',
				Attribute::POINT_NAME   => 'Point Name',
				Attribute::POINT_CITY   => 'City',
				Attribute::POINT_ZIP    => '12345',
				Attribute::POINT_STREET => 'Street 1',
				Attribute::POINT_PLACE  => 'Business XYZ',
				Attribute::CARRIER_ID   => 'carrier_1',
				Attribute::POINT_URL    => 'dummyUrl',
			]
		);
	}

	private function buildValidatedAddressPayload( string $rateId ): array {
		return [
			'packetery_rate_id'             => $rateId,
			Attribute::ADDRESS_IS_VALIDATED => '1',
			Attribute::ADDRESS_HOUSE_NUMBER => '10',
			Attribute::ADDRESS_STREET       => 'Home Street',
			Attribute::ADDRESS_CITY         => 'Praha',
			Attribute::ADDRESS_POST_CODE    => '19000',
			Attribute::ADDRESS_COUNTY       => 'Praha',
			Attribute::ADDRESS_COUNTRY      => 'CZ',
			Attribute::ADDRESS_LATITUDE     => '50.087',
			Attribute::ADDRESS_LONGITUDE    => '14.421',
		];
	}

	private function buildCarDeliveryPayload( string $rateId ): array {
		return [
			'packetery_rate_id'               => $rateId,
			Attribute::CAR_DELIVERY_ID        => 'cd_123',
			Attribute::ADDRESS_STREET         => 'Car Street',
			Attribute::ADDRESS_HOUSE_NUMBER   => '1',
			Attribute::ADDRESS_CITY           => 'City',
			Attribute::ADDRESS_POST_CODE      => '19000',
			Attribute::ADDRESS_COUNTRY        => 'CZ',
			Attribute::EXPECTED_DELIVERY_FROM => '2025-01-01',
			Attribute::EXPECTED_DELIVERY_TO   => '2025-01-02',
		];
	}

	public function testRegisterRoutesAndEndpointsReturn200(): void {
		[ $router ] = $this->registerRoutes();

		$request1 = new WP_REST_Request( 'POST', '/packeta/internal' . $router->getRoute( CheckoutRouter::PATH_SAVE_SELECTED_PICKUP_POINT ) );
		$request1->set_body_params( $this->buildPickupPointPayload( 'rate_pickup_ok' ) );
		$response1 = rest_do_request( $request1 );
		$this->assertSame( 200, $response1->get_status() );

		$request2 = new WP_REST_Request( 'POST', '/packeta/internal' . $router->getRoute( CheckoutRouter::PATH_SAVE_VALIDATED_ADDRESS ) );
		$request2->set_body_params( $this->buildValidatedAddressPayload( 'rate_addr_ok' ) );
		$response2 = rest_do_request( $request2 );
		$this->assertSame( 200, $response2->get_status() );

		$request3 = new WP_REST_Request( 'POST', '/packeta/internal' . $router->getRoute( CheckoutRouter::PATH_SAVE_DELIVERY_ADDRESS ) );
		$request3->set_body_params( $this->buildCarDeliveryPayload( 'rate_car_ok' ) );
		$response3 = rest_do_request( $request3 );
		$this->assertSame( 200, $response3->get_status() );

		$request4  = new WP_REST_Request( 'POST', '/packeta/internal' . $router->getRoute( CheckoutRouter::PATH_REMOVE_SAVED_DATA ) );
		$response4 = rest_do_request( $request4 );
		$this->assertSame( 200, $response4->get_status() );
	}

	public function testSaveSelectedPickupPointPersistsAndIsReadableFromTransient(): void {
		[ $router, $storage ] = $this->registerRoutes();

		$rateId  = 'rate_123';
		$payload = $this->buildPickupPointPayload( $rateId );

		$request = new WP_REST_Request( 'POST', '/packeta/internal' . $router->getRoute( CheckoutRouter::PATH_SAVE_SELECTED_PICKUP_POINT ) );
		$request->set_body_params( $payload );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );

		$saved = $storage->getFromTransient();
		$this->assertIsArray( $saved );
		$this->assertArrayHasKey( $rateId, $saved );
		foreach ( Attribute::$pickupPointAttributes as $attributeProperties ) {
			$name = $attributeProperties['name'];
			$this->assertSame( $payload[ $name ], $saved[ $rateId ][ $name ] ?? null, 'Saved value mismatch for ' . $name );
		}
	}

	public function testRemoveSavedDataBehaviourAllSpecificAndInvalidState(): void {
		[ $router, $storage ] = $this->registerRoutes();

		$storage->setTransient(
			[
				'rate_1' => [ Attribute::POINT_ID => 'A' ],
				'rate_2' => [ Attribute::POINT_ID => 'B' ],
			]
		);

		$requestSpecific = new WP_REST_Request( 'POST', '/packeta/internal' . $router->getRoute( CheckoutRouter::PATH_REMOVE_SAVED_DATA ) );
		$requestSpecific->set_body_params( [ 'carrierId' => 'rate_1' ] );
		$responseSpecific = rest_do_request( $requestSpecific );
		$this->assertSame( 200, $responseSpecific->get_status() );
		$afterSpecific = $storage->getFromTransient();
		$this->assertIsArray( $afterSpecific );
		$this->assertArrayNotHasKey( 'rate_1', $afterSpecific );
		$this->assertArrayHasKey( 'rate_2', $afterSpecific );

		$requestAll  = new WP_REST_Request( 'POST', '/packeta/internal' . $router->getRoute( CheckoutRouter::PATH_REMOVE_SAVED_DATA ) );
		$responseAll = rest_do_request( $requestAll );
		$this->assertSame( 200, $responseAll->get_status() );
		$this->assertFalse( $storage->getFromTransient() );

		// Invalid state: transient contains empty string -> endpoint should still return 200 and clear it.
		$reflection = new ReflectionClass( $storage );
		$method     = $reflection->getMethod( 'getTransientNamePacketaCheckoutData' );
		$method->setAccessible( true );
		$transientName = $method->invoke( $storage );
		// Set invalid transient value directly via WordPress function, because the dedicated setter should not work.
		set_transient( $transientName, '', DAY_IN_SECONDS );

		$requestInvalid = new WP_REST_Request( 'POST', '/packeta/internal' . $router->getRoute( CheckoutRouter::PATH_REMOVE_SAVED_DATA ) );
		$requestInvalid->set_body_params( [ 'carrierId' => 'any' ] );
		$responseInvalid = rest_do_request( $requestInvalid );
		$this->assertSame( 200, $responseInvalid->get_status() );
		$this->assertFalse( $storage->getFromTransient() );
	}

	public function testEndpointsReturn404WhenGetUsed(): void {
		[ $router ] = $this->registerRoutes();

		$paths = [
			CheckoutRouter::PATH_SAVE_SELECTED_PICKUP_POINT,
			CheckoutRouter::PATH_SAVE_VALIDATED_ADDRESS,
			CheckoutRouter::PATH_SAVE_DELIVERY_ADDRESS,
			CheckoutRouter::PATH_REMOVE_SAVED_DATA,
		];

		foreach ( $paths as $path ) {
			$request  = new WP_REST_Request( 'GET', '/packeta/internal' . $router->getRoute( $path ) );
			$response = rest_do_request( $request );
			$this->assertSame( 404, $response->get_status(), 'Expected 404 for GET on ' . $path );
		}
	}
}
