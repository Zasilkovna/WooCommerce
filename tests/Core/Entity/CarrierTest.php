<?php

declare( strict_types=1 );

namespace Tests\Core\Entity;

use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class CarrierTest extends TestCase {

	public function testGetters(): void {
		$carrier = DummyFactory::createCarrierCzechPp();
		$carDeliveryCarrier = DummyFactory::createCarDeliveryCarrier();
		self::assertIsArray( $carrier->__toArray() );
		self::assertIsString( $carrier->getName() );
		self::assertIsBool( $carrier->hasPickupPoints() );
		self::assertIsBool( $carrier->hasDirectLabel() );
		self::assertIsBool( $carrier->requiresSeparateHouseNumber() );
		self::assertIsBool( $carrier->requiresCustomsDeclarations() );
		self::assertIsBool( $carrier->requiresEmail() );
		self::assertIsBool( $carrier->requiresPhone() );
		self::assertIsBool( $carrier->requiresSize() );
		self::assertIsBool( $carrier->supportsCod() );
		self::assertIsString( $carrier->getCountry() );
		self::assertIsString( $carrier->getCurrency() );
		self::assertIsFloat( $carrier->getMaxWeight() );
		self::assertIsBool( $carrier->isDeleted() );
		self::assertIsBool( $carrier->supportsAgeVerification() );
		self::assertIsBool( $carDeliveryCarrier->isCarDelivery() );
	}

}
