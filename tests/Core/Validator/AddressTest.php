<?php

declare( strict_types=1 );

namespace Tests\Core\Validator;

use Packetery\Core\Validator\Address;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class AddressTest extends TestCase {

	public function testValidate(): void {
		$validator = new Address();

		$dummyAddress = DummyFactory::createAddress();
		self::assertTrue( $validator->validate( $dummyAddress ) );

		$dummyAddressInvalid = DummyFactory::createInvalidAddress();
		self::assertFalse( $validator->validate( $dummyAddressInvalid ) );
	}

}
