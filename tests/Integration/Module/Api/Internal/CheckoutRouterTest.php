<?php

declare( strict_types=1 );

namespace Tests\Integration\Module\Api\Internal;

use Packetery\Module\Api\Internal\CheckoutRouter;
use Tests\Integration\AbstractIntegrationTestCase;

class CheckoutRouterTest extends AbstractIntegrationTestCase {
	private const NAMESPACE = 'packeta/internal';
	private const REST_BASE = 'checkout';

	private function createCheckoutRouter(): CheckoutRouter {
		/** @var CheckoutRouter $router */
		$router = $this->container->getByType( CheckoutRouter::class );

		return $router;
	}

	public function testGetRouteReturnsExpectedPaths(): void {
		$router = $this->createCheckoutRouter();

		self::assertSame(
			'/' . self::REST_BASE . CheckoutRouter::PATH_SAVE_SELECTED_PICKUP_POINT,
			$router->getRoute( CheckoutRouter::PATH_SAVE_SELECTED_PICKUP_POINT )
		);

		self::assertSame(
			'/' . self::REST_BASE . CheckoutRouter::PATH_SAVE_VALIDATED_ADDRESS,
			$router->getRoute( CheckoutRouter::PATH_SAVE_VALIDATED_ADDRESS )
		);

		self::assertSame(
			'/' . self::REST_BASE . CheckoutRouter::PATH_SAVE_DELIVERY_ADDRESS,
			$router->getRoute( CheckoutRouter::PATH_SAVE_DELIVERY_ADDRESS )
		);

		self::assertSame(
			'/' . self::REST_BASE . CheckoutRouter::PATH_REMOVE_SAVED_DATA,
			$router->getRoute( CheckoutRouter::PATH_REMOVE_SAVED_DATA )
		);
	}

	public function testGetUrlReturnsExpectedAbsoluteUrls(): void {
		$router = $this->createCheckoutRouter();

		$expectedPickupPointUrl = get_rest_url( null, self::NAMESPACE . $router->getRoute( CheckoutRouter::PATH_SAVE_SELECTED_PICKUP_POINT ) );
		self::assertSame( $expectedPickupPointUrl, $router->getSaveSelectedPickupPointUrl() );

		$expectedValidatedAddressUrl = get_rest_url( null, self::NAMESPACE . $router->getRoute( CheckoutRouter::PATH_SAVE_VALIDATED_ADDRESS ) );
		self::assertSame( $expectedValidatedAddressUrl, $router->getSaveValidatedAddressUrl() );

		$expectedCarDeliveryUrl = get_rest_url( null, self::NAMESPACE . $router->getRoute( CheckoutRouter::PATH_SAVE_DELIVERY_ADDRESS ) );
		self::assertSame( $expectedCarDeliveryUrl, $router->getSaveCarDeliveryDetailsUrl() );

		$expectedRemoveDataUrl = get_rest_url( null, self::NAMESPACE . $router->getRoute( CheckoutRouter::PATH_REMOVE_SAVED_DATA ) );
		self::assertSame( $expectedRemoveDataUrl, $router->getRemoveSavedDataUrl() );
	}
}
