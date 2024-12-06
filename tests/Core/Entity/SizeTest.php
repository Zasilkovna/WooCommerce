<?php

declare( strict_types=1 );

namespace Tests\Core\Entity;

use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class SizeTest extends TestCase {
	public function testSettersAndGetters(): void {
		$size = DummyFactory::createSize();

		$length = 23.0;
		$size->setLength( $length );
		self::assertSame( $length, $size->getLength() );

		$width = 32.0;
		$size->setwidth( $width );
		self::assertSame( $width, $size->getWidth() );

		$height = 13.0;
		$size->setHeight( $height );
		self::assertSame( $height, $size->getHeight() );
	}
}
