<?php

declare( strict_types=1 );

namespace Tests\Core\Validator;

use Packetery\Core\Validator\Address;
use PHPUnit\Framework\TestCase;
use Tests\DummyFactory;

class AddressTest extends TestCase {

	public function testValidate() {
		$validator = new Address();

		$dummyAddress = DummyFactory::createAddress();
		$this->assertTrue( $validator->validate( $dummyAddress ) );

		$dummyAddressInvalid = DummyFactory::createInvalidAddress();
		$this->assertFalse( $validator->validate( $dummyAddressInvalid ) );
	}

}
