<?php

declare( strict_types=1 );

namespace Tests\Core\Entity;

use PHPUnit\Framework\TestCase;
use Tests\DummyFactory;

class PickupPointTest extends TestCase {

	public function testSettersAndGetters() {
		$pickupPoint = DummyFactory::createPickupPoint();

		$dummyId = 'dummyId';
		$pickupPoint->setId( $dummyId );
		$this->assertSame( $dummyId, $pickupPoint->getId() );

		$dummyName = 'dummyName';
		$pickupPoint->setName( $dummyName );
		$this->assertSame( $dummyName, $pickupPoint->getName() );

		$dummyUrl = 'dummyUrl';
		$pickupPoint->setUrl( $dummyUrl );
		$this->assertSame( $dummyUrl, $pickupPoint->getUrl() );

		$dummyStreet = 'dummyStreet';
		$pickupPoint->setStreet( $dummyStreet );
		$this->assertSame( $dummyStreet, $pickupPoint->getStreet() );

		$dummyZip = 'dummyZip';
		$pickupPoint->setZip( $dummyZip );
		$this->assertSame( $dummyZip, $pickupPoint->getZip() );

		$dummyCity = 'dummyCity';
		$pickupPoint->setCity( $dummyCity );
		$this->assertSame( $dummyCity, $pickupPoint->getCity() );

		$this->assertIsString( $pickupPoint->getFullAddress() );
	}

}
