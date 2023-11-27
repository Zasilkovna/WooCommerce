<?php

declare( strict_types=1 );

namespace Tests\Core;

use InvalidArgumentException;
use Packetery\Core\Rounder;
use PHPUnit\Framework\TestCase;

class RounderTest extends TestCase {

	public function testStatic(): void {
		$testValue = 106.5;

		$this->assertSame( 106.0, Rounder::roundDown( $testValue, 0 ) );
		$this->assertSame( 107.0, Rounder::roundUp( $testValue, 0 ) );
		$this->assertSame( 110.0, Rounder::roundByCurrency( $testValue, 'HUF', Rounder::ROUND_UP ) );
		$this->assertSame( 107.0, Rounder::roundByCurrency( $testValue, 'CZK', Rounder::ROUND_UP ) );
		$this->assertSame( 106.5, Rounder::roundByCurrency( $testValue, 'EUR', Rounder::DONT_ROUND ) );
	}

	public function testPrecisionException(): void {
		$this->expectException( InvalidArgumentException::class );

		Rounder::round( 106.5, Rounder::DONT_ROUND, - 1 );
	}

	public function testDivisorException(): void {
		$this->expectException( InvalidArgumentException::class );

		Rounder::roundToMultipleOfNumber( 106.5, Rounder::DONT_ROUND, - 1 );
	}

	public function testRoundingTypeException(): void {
		$this->expectException( InvalidArgumentException::class );

		Rounder::roundByCurrency( 106.5, 'EUR', 2 );
	}

}
