<?php

declare( strict_types=1 );

namespace Tests\Module\Shipping;

use Packetery\Core\Entity\Carrier;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\CompatibilityBridge;
use Packetery\Module\ContextResolver;
use Packetery\Module\Shipping\BaseShippingMethod;
use Packetery\Module\Shipping\Generated\ShippingMethod_106;
use Packetery\Module\Shipping\Generated\ShippingMethod_10618;
use Packetery\Module\Shipping\Generated\ShippingMethod_10619;
use Packetery\Module\Shipping\Generated\ShippingMethod_10693;
use Packetery\Module\Shipping\Generated\ShippingMethod_11251;
use Packetery\Module\Shipping\Generated\ShippingMethod_1160;
use Packetery\Module\Shipping\ShippingProvider;
use Packetery\Module\ShippingMethod;
use Packetery\Module\ShippingZoneRepository;
use Packetery\Nette\DI\Container;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class ShippingProviderTest extends TestCase {

	private ContextResolver&MockObject $contextResolver;
	private ShippingZoneRepository&MockObject $shippingZoneRepository;
	private EntityRepository&MockObject $carrierRepository;
	private ?Container $originalContainer;

	protected function setUp(): void {
		$this->originalContainer = self::getContainerProperty()->getValue();
	}

	private static function getContainerProperty(): ReflectionProperty {
		return new ReflectionProperty( CompatibilityBridge::class, 'diContainer' );
	}

	/**
	 * A Packeta method constructor reads its services from the container, so no call means no instance.
	 */
	private function forbidPacketaMethodInstances(): void {
		$container = $this->createMock( Container::class );
		$container
			->expects( $this->never() )
			->method( 'getByType' );
		self::getContainerProperty()->setValue( null, $container );
	}

	private function createShippingProvider(): ShippingProvider {
		$this->contextResolver        = $this->createMock( ContextResolver::class );
		$this->shippingZoneRepository = $this->createMock( ShippingZoneRepository::class );
		$this->carrierRepository      = $this->createMock( EntityRepository::class );

		return new ShippingProvider(
			$this->createMock( CarDeliveryConfig::class ),
			$this->contextResolver,
			$this->shippingZoneRepository,
			$this->carrierRepository,
			$this->createMock( CarrierOptionsFactory::class )
		);
	}

	public function tearDown(): void {
		$shippingProviderReflection = new ReflectionClass( ShippingProvider::class );
		$property                   = $shippingProviderReflection->getProperty( 'sortedMethodsCache' );
		$property->setAccessible( true );
		$property->setValue( null, [] );

		self::getContainerProperty()->setValue( null, $this->originalContainer );
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
		$property->setValue( null, [ $cacheKey => [ 'dummyMethodId' => 'dummyMethodClass' ] ] );

		$this->contextResolver->expects( $this->never() )->method( 'getShippingZoneId' );
		$this->shippingZoneRepository->expects( $this->never() )->method( 'getCountryCodesForShippingZone' );

		$shippingProvider->getSortedCachedMethods( $originalMethods );
	}

	public function testSortMethodsSortsByTitleWithoutPacketaInstances(): void {
		$shippingProvider = $this->createShippingProvider();
		$this->forbidPacketaMethodInstances();

		$carrierNames = [
			ShippingMethod_106::CARRIER_ID   => 'Home CZ',
			ShippingMethod_10618::CARRIER_ID => 'Z-BOX',
			ShippingMethod_10619::CARRIER_ID => 'Packeta pickup point',
		];
		$this->carrierRepository
			->method( 'getAnyById' )
			->willReturnCallback(
				function ( string $carrierId, bool $useCache ) use ( $carrierNames ): Carrier {
					self::assertTrue( $useCache );

					return $this->createConfiguredMock( Carrier::class, [ 'getName' => $carrierNames[ $carrierId ] ] );
				}
			);

		$foreignMethodClass  = get_class(
			new class() {
				// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
				public $method_title = 'Flat rate';
			}
		);
		$foreignMethodObject = new class() {
			// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
			public $method_title = 'Local pickup';
		};

		$methods = [
			ShippingMethod_10618::getShippingMethodId() => ShippingMethod_10618::class,
			'flat_rate'                                 => $foreignMethodClass,
			ShippingMethod_10619::getShippingMethodId() => ShippingMethod_10619::class,
			'local_pickup'                              => $foreignMethodObject,
			ShippingMethod_106::getShippingMethodId()   => ShippingMethod_106::class,
		];

		$sortMethods   = new ReflectionMethod( ShippingProvider::class, 'sortMethods' );
		$sortedMethods = $sortMethods->invoke( $shippingProvider, $methods );

		self::assertSame(
			[
				'flat_rate',
				ShippingMethod_106::getShippingMethodId(),
				'local_pickup',
				ShippingMethod_10619::getShippingMethodId(),
				ShippingMethod_10618::getShippingMethodId(),
			],
			array_keys( $sortedMethods )
		);
		self::assertSame( $foreignMethodObject, $sortedMethods['local_pickup'] );
	}

	/**
	 * @return array<string, array{methodClasses: array<int, class-string<BaseShippingMethod>>}>
	 */
	public static function packetaMethodClassesProvider(): array {
		return [
			'three methods' => [
				'methodClasses' => [
					ShippingMethod_10618::class,
					ShippingMethod_106::class,
					ShippingMethod_10619::class,
				],
			],
			'six methods'   => [
				'methodClasses' => [
					ShippingMethod_1160::class,
					ShippingMethod_10618::class,
					ShippingMethod_11251::class,
					ShippingMethod_106::class,
					ShippingMethod_10693::class,
					ShippingMethod_10619::class,
				],
			],
		];
	}

	/**
	 * The number of carrier reads grows linearly with the number of methods, not with the number of comparisons.
	 *
	 * @dataProvider packetaMethodClassesProvider
	 *
	 * @param array<int, class-string<BaseShippingMethod>> $methodClasses Generated method classes.
	 */
	public function testSortMethodsReadsEachCarrierOnce( array $methodClasses ): void {
		$shippingProvider = $this->createShippingProvider();
		$this->forbidPacketaMethodInstances();

		$methods            = [];
		$expectedCarrierIds = [];
		foreach ( $methodClasses as $methodClass ) {
			$methods[ $methodClass::getShippingMethodId() ] = $methodClass;
			$expectedCarrierIds[]                           = $methodClass::CARRIER_ID;
		}

		$requestedCarrierIds = [];
		$this->carrierRepository
			->expects( $this->exactly( count( $methodClasses ) ) )
			->method( 'getAnyById' )
			->willReturnCallback(
				function ( string $carrierId ) use ( &$requestedCarrierIds ): Carrier {
					$requestedCarrierIds[] = $carrierId;

					return $this->createConfiguredMock( Carrier::class, [ 'getName' => "Carrier {$carrierId}" ] );
				}
			);

		$sortMethods   = new ReflectionMethod( ShippingProvider::class, 'sortMethods' );
		$sortedMethods = $sortMethods->invoke( $shippingProvider, $methods );

		sort( $expectedCarrierIds );
		sort( $requestedCarrierIds );
		self::assertSame( $expectedCarrierIds, $requestedCarrierIds );
		self::assertCount( count( $methodClasses ), $sortedMethods );
	}
}
