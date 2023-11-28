<?php

declare( strict_types=1 );

namespace Tests\Core\PickupPointProvider;

use PHPUnit\Framework\TestCase;
use Tests\DummyFactory;

class VendorProviderTest extends TestCase {

	public function testBaseProvider() {
		$vendor = DummyFactory::createVendor();
		$vendor->setTranslatedName( 'Dummy vendor translated' );
		$this->assertIsString( $vendor->getId() );
		$this->assertIsString( $vendor->getCountry() );
		$this->assertIsString( $vendor->getName() );
		$this->assertIsString( $vendor->getCurrency() );
		$this->assertTrue( $vendor->supportsCod() );
		$this->assertTrue( $vendor->supportsAgeVerification() );
		$this->assertTrue( $vendor->hasPickupPoints() );
	}

	public function testGetGroup() {
		$vendor = DummyFactory::createVendor();
		$this->assertIsString( $vendor->getGroup() );
	}

}
