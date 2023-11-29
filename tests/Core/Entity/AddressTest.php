<?php

declare( strict_types=1 );

namespace Tests\Core\Entity;

use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class AddressTest extends TestCase {

	public function testSettersAndGetters(): void {
		$address = DummyFactory::createAddress();

		self::assertIsString( $address->getStreet() );
		self::assertIsString( $address->getZip() );
		self::assertIsString( $address->getCity() );

		$dummyHouseNumber = '789/12';
		$address->setHouseNumber( $dummyHouseNumber );
		self::assertSame( $dummyHouseNumber, $address->getHouseNumber() );

		$dummyLongitude = '14.012';
		$address->setLongitude( $dummyLongitude );
		self::assertSame( $dummyLongitude, $address->getLongitude() );

		$dummyLatitude = '50.321';
		$address->setLatitude( $dummyLatitude );
		self::assertSame( $dummyLatitude, $address->getLatitude() );

		$dummyCounty = 'dummyCounty';
		$address->setCounty( $dummyCounty );
		self::assertSame( $dummyCounty, $address->getCounty() );

		self::assertIsString( $address->getFullAddress() );
		self::assertIsArray( $address->export() );
	}

}
