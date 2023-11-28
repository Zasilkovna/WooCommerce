<?php

declare( strict_types=1 );

namespace Tests\Core\Entity;

use PHPUnit\Framework\TestCase;
use Tests\DummyFactory;

class CarrierTest extends TestCase {

	public function testGetters() {
		$carrier = DummyFactory::createCarrierCzechPp();
		$this->assertIsArray($carrier->__toArray());
		$this->assertIsString($carrier->getName());
		$this->assertIsBool($carrier->hasPickupPoints());
		$this->assertIsBool($carrier->hasDirectLabel());
		$this->assertIsBool($carrier->requiresSeparateHouseNumber());
		$this->assertIsBool($carrier->requiresCustomsDeclarations());
		$this->assertIsBool($carrier->requiresEmail());
		$this->assertIsBool($carrier->requiresPhone());
		$this->assertIsBool($carrier->requiresSize());
		$this->assertIsBool($carrier->supportsCod());
		$this->assertIsString($carrier->getCountry());
		$this->assertIsString($carrier->getCurrency());
		$this->assertIsFloat($carrier->getMaxWeight());
		$this->assertIsBool($carrier->isDeleted());
		$this->assertIsBool($carrier->supportsAgeVerification());
	}

}
