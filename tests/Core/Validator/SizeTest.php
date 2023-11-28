<?php

declare( strict_types=1 );

namespace Tests\Core\Validator;

use Packetery\Core\Validator\Size;
use PHPUnit\Framework\TestCase;
use Tests\DummyFactory;

class SizeTest extends TestCase {

	public function testValidate() {
		$validator = new Size();

		$dummySize = DummyFactory::createSize();
		$this->assertTrue( $validator->validate( $dummySize ) );

		$dummySize->setLength(null);
		$dummySize->setWidth(null);
		$dummySize->setHeight(null);
		$this->assertFalse( $validator->validate( $dummySize ) );
	}

}
