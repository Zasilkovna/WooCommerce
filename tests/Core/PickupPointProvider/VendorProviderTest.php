<?php

declare( strict_types=1 );

namespace Tests\Core\PickupPointProvider;

use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class VendorProviderTest extends TestCase {

	public function testBaseProvider(): void {
		$vendor = DummyFactory::createVendor();
		$vendor->setTranslatedName( 'Dummy vendor translated' );
		self::assertIsString( $vendor->getId() );
		self::assertIsString( $vendor->getCountry() );
		self::assertIsString( $vendor->getName() );
		self::assertIsString( $vendor->getCurrency() );
		self::assertTrue( $vendor->supportsCod() );
		self::assertTrue( $vendor->supportsAgeVerification() );
		self::assertTrue( $vendor->hasPickupPoints() );
	}

	public function testGetGroup(): void {
		$vendor = DummyFactory::createVendor();
		self::assertIsString( $vendor->getGroup() );
	}

}
