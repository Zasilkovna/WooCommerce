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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityRepositoryTest extends TestCase {
	private const CARRIER_ID_HOME    = '106';
	private const CARRIER_ID_BOX     = '10618';
	private const CARRIER_ID_UNKNOWN = '999';

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
					return $this->createConfiguredMock( Carrier::class, [ 'getId' => $row['id'] ] );
				}
			);

		return new EntityRepository(
			$this->repositoryMock,
			$carrierEntityFactoryMock,
			$this->createMock( PacketaPickupPointsConfig::class ),
			$this->createMock( CarDeliveryConfig::class ),
			$this->createMock( CarrierOptionsFactory::class ),
			$this->createMock( CarrierActivityBridge::class )
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
