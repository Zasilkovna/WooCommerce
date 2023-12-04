<?php

declare( strict_types=1 );

namespace Tests\Core\Entity;

use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class PickupPointTest extends TestCase {

	public function testSettersAndGetters(): void {
		$pickupPoint = DummyFactory::createPickupPoint();

		$dummyId = 'dummyId';
		$pickupPoint->setId( $dummyId );
		self::assertSame( $dummyId, $pickupPoint->getId() );

		$dummyName = 'dummyName';
		$pickupPoint->setName( $dummyName );
		self::assertSame( $dummyName, $pickupPoint->getName() );

		$dummyUrl = 'dummyUrl';
		$pickupPoint->setUrl( $dummyUrl );
		self::assertSame( $dummyUrl, $pickupPoint->getUrl() );

		$dummyStreet = 'dummyStreet';
		$pickupPoint->setStreet( $dummyStreet );
		self::assertSame( $dummyStreet, $pickupPoint->getStreet() );

		$dummyZip = 'dummyZip';
		$pickupPoint->setZip( $dummyZip );
		self::assertSame( $dummyZip, $pickupPoint->getZip() );

		$dummyCity = 'dummyCity';
		$pickupPoint->setCity( $dummyCity );
		self::assertSame( $dummyCity, $pickupPoint->getCity() );

		self::assertIsString( $pickupPoint->getFullAddress() );
	}

}
