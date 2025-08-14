<?php

declare( strict_types=1 );

namespace Tests\Module\Shipping;

use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\ContextResolver;
use Packetery\Module\Options\FlagManager\FeatureFlagProvider;
use Packetery\Module\Shipping\BaseShippingMethod;
use Packetery\Module\Shipping\ShippingProvider;
use Packetery\Module\ShippingMethod;
use Packetery\Module\ShippingZoneRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ShippingProviderTest extends TestCase {

	private ContextResolver&MockObject $contextResolver;
	private ShippingZoneRepository&MockObject $shippingZoneRepository;

	private function createShippingProvider(): ShippingProvider {
		$this->contextResolver        = $this->createMock( ContextResolver::class );
		$this->shippingZoneRepository = $this->createMock( ShippingZoneRepository::class );

		return new ShippingProvider(
			$this->createMock( FeatureFlagProvider::class ),
			$this->createMock( PacketaPickupPointsConfig::class ),
			$this->createMock( CarDeliveryConfig::class ),
			$this->contextResolver,
			$this->shippingZoneRepository,
			$this->createMock( EntityRepository::class ),
			$this->createMock( CarrierOptionsFactory::class )
		);
	}

	public function tearDown(): void {
		$shippingProviderReflection = new ReflectionClass( ShippingProvider::class );
		$property                   = $shippingProviderReflection->getProperty( 'sortedMethodsCache' );
		$property->setAccessible( true );
		$property->setValue( [] );
	}

	/**
	 * @return array
	 */
	public static function shippingMethodsProvider(): array {
		return [
			[
				'methodId'        => 'free_shipping',
				'isPacketaMethod' => false,
			],
			[
				'methodId'        => 'flat_rate',
				'isPacketaMethod' => false,
			],
			[
				'methodId'        => ShippingMethod::PACKETERY_METHOD_ID,
				'isPacketaMethod' => true,
			],
			[
				'methodId'        => BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpointcz',
				'isPacketaMethod' => true,
			],
			[
				'methodId'        => BaseShippingMethod::PACKETA_METHOD_PREFIX . 'czzpoint',
				'isPacketaMethod' => true,
			],
			[
				'methodId'        => BaseShippingMethod::PACKETA_METHOD_PREFIX . '106',
				'isPacketaMethod' => true,
			],
		];
	}

	/**
	 * @dataProvider shippingMethodsProvider
	 */
	public function testIsPacketaMethod( $methodId, $isPacketaMethod ): void {
		self::assertEquals( $isPacketaMethod, ShippingProvider::isPacketaMethod( $methodId ) );
	}

	public function testGetSortedCachedMethodsFillsCache(): void {
		$shippingProvider = $this->createShippingProvider();

		$dummyZoneId = 123;
		$this->contextResolver->expects( $this->once() )->method( 'getShippingZoneId' )->willReturn( $dummyZoneId );
		$this->shippingZoneRepository->expects( $this->once() )->method( 'getCountryCodesForShippingZone' )->willReturn( [] );

		$shippingProvider->getSortedCachedMethods( [] );
	}

	public function testGetSortedCachedMethodsGetsFromCache(): void {
		$shippingProvider = $this->createShippingProvider();

		$originalMethods = [];
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$cacheKey = crc32( serialize( $originalMethods ) );

		$shippingProviderReflection = new ReflectionClass( ShippingProvider::class );
		$property                   = $shippingProviderReflection->getProperty( 'sortedMethodsCache' );
		$property->setAccessible( true );
		$property->setValue( [ $cacheKey => [ 'dummyMethodId' => 'dummyMethodClass' ] ] );

		$this->contextResolver->expects( $this->never() )->method( 'getShippingZoneId' );
		$this->shippingZoneRepository->expects( $this->never() )->method( 'getCountryCodesForShippingZone' );

		$shippingProvider->getSortedCachedMethods( $originalMethods );
	}
}
