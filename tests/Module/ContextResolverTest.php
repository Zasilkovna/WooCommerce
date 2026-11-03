<?php

declare( strict_types=1 );

namespace Tests\Module;

use Packetery\Module\ContextResolver;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Nette\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use WP_REST_Request;

class ContextResolverTest extends TestCase {
	private const SHIPPING_ZONE_METHODS_ROUTE = '/wc/v3/shipping/zones/5/methods';
	private const STORE_API_ROUTE             = '/wc/store/v1/cart';

	private function createContextResolver( bool $isAdmin ): ContextResolver {
		$wpAdapter = $this->createMock( WpAdapter::class );
		$wpAdapter
			->method( 'isAdmin' )
			->willReturn( $isAdmin );

		return new ContextResolver( $this->createMock( Request::class ), $wpAdapter );
	}

	/**
	 * @return WP_REST_Request<string[]>&MockObject
	 */
	private function createRestRequest( string $route ): WP_REST_Request&MockObject {
		$restRequest = $this->createMock( WP_REST_Request::class );
		$restRequest
			->method( 'get_route' )
			->willReturn( $route );

		return $restRequest;
	}

	/**
	 * @return array<string, array{route: string, expectedIsContext: bool}>
	 */
	public static function restRouteProvider(): array {
		return [
			'REST API v1'              => [
				'route'             => '/wc/v1/x',
				'expectedIsContext' => true,
			],
			'shipping zone methods'    => [
				'route'             => self::SHIPPING_ZONE_METHODS_ROUTE,
				'expectedIsContext' => true,
			],
			'namespace root'           => [
				'route'             => '/wc/v3',
				'expectedIsContext' => true,
			],
			'Store API'                => [
				'route'             => self::STORE_API_ROUTE,
				'expectedIsContext' => false,
			],
			'version without boundary' => [
				'route'             => '/wc/v3x/a',
				'expectedIsContext' => false,
			],
			'unsupported version'      => [
				'route'             => '/wc/v4/a',
				'expectedIsContext' => false,
			],
			'WordPress REST API'       => [
				'route'             => '/wp/v2/posts',
				'expectedIsContext' => false,
			],
			'empty route'              => [
				'route'             => '',
				'expectedIsContext' => false,
			],
		];
	}

	/**
	 * @dataProvider restRouteProvider
	 *
	 * @param string $route             Route of the REST request.
	 * @param bool   $expectedIsContext Expected result.
	 */
	public function testIsCarrierSettingsLinkContextInRestCallback( string $route, bool $expectedIsContext ): void {
		$contextResolver = $this->createContextResolver( false );
		$response        = new stdClass();

		self::assertSame( $response, $contextResolver->pushRestRoute( $response, [], $this->createRestRequest( $route ) ) );
		self::assertSame( $expectedIsContext, $contextResolver->isCarrierSettingsLinkContext() );
	}

	public function testIsCarrierSettingsLinkContextOutsideRestCallback(): void {
		self::assertFalse( $this->createContextResolver( false )->isCarrierSettingsLinkContext() );
	}

	public function testIsCarrierSettingsLinkContextInAdmin(): void {
		self::assertTrue( $this->createContextResolver( true )->isCarrierSettingsLinkContext() );
	}

	public function testIsCarrierSettingsLinkContextAfterRestCallback(): void {
		$contextResolver = $this->createContextResolver( false );
		$response        = new stdClass();

		$contextResolver->pushRestRoute( $response, [], $this->createRestRequest( self::SHIPPING_ZONE_METHODS_ROUTE ) );

		self::assertSame( $response, $contextResolver->popRestRoute( $response ) );
		self::assertFalse( $contextResolver->isCarrierSettingsLinkContext() );
	}

	public function testIsCarrierSettingsLinkContextAfterNestedRestCallback(): void {
		$contextResolver = $this->createContextResolver( false );
		$response        = new stdClass();

		$contextResolver->pushRestRoute( $response, [], $this->createRestRequest( self::SHIPPING_ZONE_METHODS_ROUTE ) );
		$contextResolver->pushRestRoute( $response, [], $this->createRestRequest( self::STORE_API_ROUTE ) );
		$contextResolver->popRestRoute( $response );

		self::assertTrue( $contextResolver->isCarrierSettingsLinkContext() );
	}
}
