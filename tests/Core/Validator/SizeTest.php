<?php

declare( strict_types=1 );

namespace Tests\Core\Validator;

use Packetery\Core\Validator\Size;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class SizeTest extends TestCase {

	public function testValidate(): void {
		$validator = new Size();

		$dummySize = DummyFactory::createSize();
		self::assertTrue( $validator->validate( $dummySize ) );

		$dummySize->setLength( null );
		$dummySize->setWidth( null );
		$dummySize->setHeight( null );
		self::assertFalse( $validator->validate( $dummySize ) );
	}

}
