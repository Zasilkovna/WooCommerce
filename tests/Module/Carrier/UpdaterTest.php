<?php

declare( strict_types=1 );

namespace Tests\Module\Carrier;

use Packetery\Core\Log\ILogger;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\Repository;
use Packetery\Module\Carrier\Updater;
use Packetery\Module\Framework\WpAdapter;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class UpdaterTest extends TestCase {
	private const CARRIER_ID = 106;

	protected function setUp(): void {
		EntityRepository::clearCache();
	}

	protected function tearDown(): void {
		EntityRepository::clearCache();
	}

	public function testSaveClearsCarrierCacheAfterCarriersAreSaved(): void {
		$carrierRepository = $this->createMock( Repository::class );
		$carrierRepository
			->method( 'getAllRawIndexed' )
			->willReturn( [] );
		// A cache that something loads during the save must not survive the save.
		$carrierRepository
			->expects( $this->once() )
			->method( 'insert' )
			->willReturnCallback(
				function (): int {
					self::fillCarrierCache();

					return 1;
				}
			);

		$wpAdapter = $this->createMock( WpAdapter::class );
		$wpAdapter
			->method( '__' )
			->willReturnArgument( 0 );

		$updater = new Updater(
			$carrierRepository,
			$this->createMock( ILogger::class ),
			$this->createMock( CarrierOptionsFactory::class ),
			$wpAdapter
		);

		self::fillCarrierCache();
		$updater->save( [ self::createFeedCarrier() ] );

		self::assertSame( [], self::getCarrierCacheProperty( 'carrierRowsCache' )->getValue() );
		self::assertFalse( self::getCarrierCacheProperty( 'areAllCarriersLoaded' )->getValue() );
	}

	private static function fillCarrierCache(): void {
		self::getCarrierCacheProperty( 'carrierRowsCache' )->setValue(
			null,
			[ self::CARRIER_ID => [ 'name' => 'Old name' ] ]
		);
		self::getCarrierCacheProperty( 'areAllCarriersLoaded' )->setValue( null, true );
	}

	private static function getCarrierCacheProperty( string $name ): ReflectionProperty {
		return new ReflectionProperty( EntityRepository::class, $name );
	}

	/**
	 * @return array<string, string>
	 */
	private static function createFeedCarrier(): array {
		return [
			'id'                  => (string) self::CARRIER_ID,
			'name'                => 'CZ Packeta Home HD',
			'country'             => 'cz',
			'currency'            => 'CZK',
			'maxWeight'           => '10',
			'pickupPoints'        => 'false',
			'apiAllowed'          => 'true',
			'separateHouseNumber' => 'false',
			'customsDeclarations' => 'false',
			'requiresEmail'       => 'false',
			'requiresPhone'       => 'false',
			'requiresSize'        => 'false',
			'disallowsCod'        => 'false',
			'available'           => 'true',
		];
	}
}
