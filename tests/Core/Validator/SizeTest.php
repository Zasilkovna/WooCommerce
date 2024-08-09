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
		$validationReport = $validator->validate( $dummySize );
		self::assertTrue( $validationReport->isHeightValid() );
		self::assertTrue( $validationReport->isWidthValid() );
		self::assertTrue( $validationReport->isLengthValid() );

		$dummySize->setLength( null );
		$validationReport = $validator->validate( $dummySize );
		self::assertTrue( $validationReport->isHeightValid() );
		self::assertTrue( $validationReport->isWidthValid() );
		self::assertFalse( $validationReport->isLengthValid() );

		$dummySize->setWidth( null );
		$validationReport = $validator->validate( $dummySize );
		self::assertTrue( $validationReport->isHeightValid() );
		self::assertFalse( $validationReport->isWidthValid() );
		self::assertFalse( $validationReport->isLengthValid() );

		$dummySize->setHeight( null );
		$validationReport = $validator->validate( $dummySize );
		self::assertFalse( $validationReport->isHeightValid() );
		self::assertFalse( $validationReport->isWidthValid() );
		self::assertFalse( $validationReport->isLengthValid() );
	}

}
