<?php

declare( strict_types=1 );

namespace Tests\Module\Carrier;

use Packetery\Core\Entity\Carrier;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\CarrierActivityBridge;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Carrier\Repository;
use Packetery\Module\EntityFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityRepositoryTest extends TestCase {
	private const CARRIER_ID_HOME        = '106';
	private const CARRIER_ID_BOX         = '10618';
	private const CARRIER_ID_UNKNOWN     = '999';
	private const CARRIER_ID_DELETED     = '4993';
	private const CARRIER_ID_UNAVAILABLE = '131';
	private const CARRIER_ID_SLOVAK      = '16';

	private Repository&MockObject $repositoryMock;

	protected function setUp(): void {
		EntityRepository::clearCache();
	}

	protected function tearDown(): void {
		EntityRepository::clearCache();
	}

	private function createEntityRepository(): EntityRepository {
		$this->repositoryMock = $this->createMock( Repository::class );

		$carrierEntityFactoryMock = $this->createMock( EntityFactory\Carrier::class );
		$carrierEntityFactoryMock
			->method( 'fromDbResult' )
			->willReturnCallback(
				function ( array $row ): Carrier {
					return $this->createConfiguredMock(
						Carrier::class,
						[
							'getId'           => $row['id'],
							'getCountry'      => $row['country'] ?? '',
							'isDeleted'       => (bool) ( $row['deleted'] ?? false ),
							'isAvailable'     => (bool) ( $row['available'] ?? false ),
							'hasPickupPoints' => (bool) ( $row['is_pickup_points'] ?? false ),
						]
					);
				}
			);

		return new EntityRepository(
			$this->repositoryMock,
			$carrierEntityFactoryMock,
			$this->createMock( PacketaPickupPointsConfig::class ),
			$this->createMock( CarDeliveryConfig::class ),
			$this->createMock( CarrierOptionsFactory::class ),
			$this->createConfiguredMock( CarrierActivityBridge::class, [ 'isActive' => true ] )
		);
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private static function getCarrierRows(): array {
		return [
			(int) self::CARRIER_ID_HOME => [ 'id' => self::CARRIER_ID_HOME ],
			(int) self::CARRIER_ID_BOX  => [ 'id' => self::CARRIER_ID_BOX ],
		];
	}

	public function testAllCarriersAreLoadedWithOneQuery(): void {
		$entityRepository = $this->createEntityRepository();
		$this->repositoryMock
			->expects( $this->once() )
			->method( 'getAllRawIndexed' )
			->willReturn( self::getCarrierRows() );
		$this->repositoryMock
			->expects( $this->never() )
			->method( 'getById' );

		$homeCarrier = $entityRepository->getAnyById( self::CARRIER_ID_HOME, true );
		$boxCarrier  = $entityRepository->getAnyById( self::CARRIER_ID_BOX, true );

		self::assertNotNull( $homeCarrier );
		self::assertNotNull( $boxCarrier );
		self::assertSame( self::CARRIER_ID_HOME, $homeCarrier->getId() );
		self::assertSame( self::CARRIER_ID_BOX, $boxCarrier->getId() );
		self::assertSame( $homeCarrier, $entityRepository->getAnyById( self::CARRIER_ID_HOME, true ) );
	}

	public function testUnknownCarrierIsAnsweredFromMemory(): void {
		$entityRepository = $this->createEntityRepository();
		$this->repositoryMock
			->expects( $this->once() )
			->method( 'getAllRawIndexed' )
			->willReturn( self::getCarrierRows() );
		$this->repositoryMock
			->expects( $this->never() )
			->method( 'getById' );

		self::assertNull( $entityRepository->getAnyById( self::CARRIER_ID_UNKNOWN, true ) );
		self::assertNull( $entityRepository->getAnyById( self::CARRIER_ID_UNKNOWN, true ) );
	}

	public function testClearCacheLoadsCarriersAgain(): void {
		$entityRepository = $this->createEntityRepository();
		$this->repositoryMock
			->expects( $this->exactly( 2 ) )
			->method( 'getAllRawIndexed' )
			->willReturn( self::getCarrierRows() );

		$entityRepository->getAnyById( self::CARRIER_ID_HOME, true );
		EntityRepository::clearCache();
		$entityRepository->getAnyById( self::CARRIER_ID_HOME, true );
	}

	/**
	 * Rows are not sorted by id, so the tests can check that the table order is kept.
	 *
	 * @return array<int, array<string, string>>
	 */
	private static function getCarrierRowsWithState(): array {
		return [
			(int) self::CARRIER_ID_BOX         => [
				'id'               => self::CARRIER_ID_BOX,
				'country'          => 'cz',
				'deleted'          => '0',
				'available'        => '1',
				'is_pickup_points' => '1',
			],
			(int) self::CARRIER_ID_HOME        => [
				'id'               => self::CARRIER_ID_HOME,
				'country'          => 'cz',
				'deleted'          => '0',
				'available'        => '1',
				'is_pickup_points' => '0',
			],
			(int) self::CARRIER_ID_UNAVAILABLE => [
				'id'               => self::CARRIER_ID_UNAVAILABLE,
				'country'          => 'cz',
				'deleted'          => '0',
				'available'        => '0',
				'is_pickup_points' => '0',
			],
			(int) self::CARRIER_ID_DELETED     => [
				'id'               => self::CARRIER_ID_DELETED,
				'country'          => 'cz',
				'deleted'          => '1',
				'available'        => '1',
				'is_pickup_points' => '0',
			],
			(int) self::CARRIER_ID_SLOVAK      => [
				'id'               => self::CARRIER_ID_SLOVAK,
				'country'          => 'sk',
				'deleted'          => '0',
				'available'        => '1',
				'is_pickup_points' => '0',
			],
		];
	}

	private function expectOneQueryForAllLookups(): void {
		$this->repositoryMock
			->expects( $this->once() )
			->method( 'getAllRawIndexed' )
			->willReturn( self::getCarrierRowsWithState() );
		$this->repositoryMock
			->expects( $this->never() )
			->method( 'getById' );
	}

	public function testActiveCarriersShareTheCacheWithGetAnyById(): void {
		$entityRepository = $this->createEntityRepository();
		$this->expectOneQueryForAllLookups();

		$homeCarrier    = $entityRepository->getAnyById( self::CARRIER_ID_HOME, true );
		$activeCarriers = $entityRepository->getActiveCarriers();

		self::assertSame(
			[
				(int) self::CARRIER_ID_BOX,
				(int) self::CARRIER_ID_HOME,
				(int) self::CARRIER_ID_SLOVAK,
			],
			array_keys( $activeCarriers )
		);
		self::assertSame( $homeCarrier, $activeCarriers[ (int) self::CARRIER_ID_HOME ] );
		self::assertSame( $activeCarriers[ (int) self::CARRIER_ID_BOX ], $entityRepository->getAnyById( self::CARRIER_ID_BOX, true ) );
		self::assertSame( $activeCarriers, $entityRepository->getActiveCarriers() );
	}

	/**
	 * @return array<string, array{string, bool, list<string>}>
	 */
	public static function getByCountryProvider(): array {
		return [
			'available only'      => [ 'cz', false, [ self::CARRIER_ID_BOX, self::CARRIER_ID_HOME ] ],
			'unavailable too'     => [ 'cz', true, [ self::CARRIER_ID_BOX, self::CARRIER_ID_HOME, self::CARRIER_ID_UNAVAILABLE ] ],
			'upper case country'  => [ 'CZ', false, [ self::CARRIER_ID_BOX, self::CARRIER_ID_HOME ] ],
			'other country'       => [ 'sk', true, [ self::CARRIER_ID_SLOVAK ] ],
			'country without any' => [ 'de', true, [] ],
		];
	}

	/**
	 * @param string   $country            ISO code.
	 * @param bool     $includeUnavailable Include unavailable carriers.
	 * @param string[] $expectedCarrierIds Carrier ids in table order.
	 */
	#[DataProvider( 'getByCountryProvider' )]
	public function testGetByCountryIsReadFromTheCache( string $country, bool $includeUnavailable, array $expectedCarrierIds ): void {
		$entityRepository = $this->createEntityRepository();
		$this->expectOneQueryForAllLookups();

		$carriers = $entityRepository->getByCountry( $country, $includeUnavailable );

		self::assertSame(
			$expectedCarrierIds,
			array_map(
				static function ( Carrier $carrier ): string {
					return $carrier->getId();
				},
				$carriers
			)
		);
		self::assertSame( $carriers, $entityRepository->getByCountry( $country, $includeUnavailable ) );
	}

	public function testCarrierChecksAreReadFromTheCache(): void {
		$entityRepository = $this->createEntityRepository();
		$this->expectOneQueryForAllLookups();

		self::assertTrue( $entityRepository->hasPickupPoints( (int) self::CARRIER_ID_BOX ) );
		self::assertFalse( $entityRepository->hasPickupPoints( (int) self::CARRIER_ID_HOME ) );
		self::assertFalse( $entityRepository->hasPickupPoints( (int) self::CARRIER_ID_UNKNOWN ) );
		self::assertTrue( $entityRepository->isHomeDeliveryCarrier( self::CARRIER_ID_HOME ) );
		self::assertFalse( $entityRepository->isHomeDeliveryCarrier( self::CARRIER_ID_BOX ) );
		self::assertTrue( $entityRepository->isValidForCountry( self::CARRIER_ID_HOME, 'cz' ) );
		self::assertFalse( $entityRepository->isValidForCountry( self::CARRIER_ID_UNAVAILABLE, 'cz' ) );
		self::assertFalse( $entityRepository->isValidForCountry( self::CARRIER_ID_DELETED, 'cz' ) );
		self::assertFalse( $entityRepository->isValidForCountry( self::CARRIER_ID_SLOVAK, 'cz' ) );
	}

	public function testCacheIsNotUsedWithoutTheFlag(): void {
		$entityRepository = $this->createEntityRepository();
		$this->repositoryMock
			->expects( $this->never() )
			->method( 'getAllRawIndexed' );
		$this->repositoryMock
			->expects( $this->once() )
			->method( 'getById' )
			->with( (int) self::CARRIER_ID_HOME )
			->willReturn( [ 'id' => self::CARRIER_ID_HOME ] );

		$carrier = $entityRepository->getAnyById( self::CARRIER_ID_HOME );

		self::assertNotNull( $carrier );
		self::assertSame( self::CARRIER_ID_HOME, $carrier->getId() );
	}
}
