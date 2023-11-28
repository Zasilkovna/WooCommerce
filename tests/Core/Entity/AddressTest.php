<?php

declare( strict_types=1 );

namespace Tests\Core\Entity;

use PHPUnit\Framework\TestCase;
use Tests\DummyFactory;

class AddressTest extends TestCase {

	public function testSettersAndGetters() {
		$address = DummyFactory::createAddress();

		$this->assertIsString( $address->getStreet() );
		$this->assertIsString( $address->getZip() );
		$this->assertIsString( $address->getCity() );

		$dummyHouseNumber = '789/12';
		$address->setHouseNumber( $dummyHouseNumber );
		$this->assertSame( $dummyHouseNumber, $address->getHouseNumber() );

		$dummyLongitude = '14.012';
		$address->setLongitude( $dummyLongitude );
		$this->assertSame( $dummyLongitude, $address->getLongitude() );

		$dummyLatitude = '50.321';
		$address->setLatitude( $dummyLatitude );
		$this->assertSame( $dummyLatitude, $address->getLatitude() );

		$dummyCounty = 'dummyCounty';
		$address->setCounty( $dummyCounty );
		$this->assertSame( $dummyCounty, $address->getCounty() );

		$this->assertIsString( $address->getFullAddress() );
		$this->assertIsArray( $address->export() );
	}

}
