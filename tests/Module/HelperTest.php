<?php

declare( strict_types=1 );

namespace Tests\Module;

use PHPUnit\Framework\TestCase;
use Packetery\Module\Helper;

class HelperTest extends TestCase {

	public static function convertToCentimetersProvider(): array {
		return [
			[
				'input'    => 0,
				'expected' => null
			],
			[
				'input'    => 1,
				'expected' => 0.1
			],
			[
				'input'    => 10,
				'expected' => 1.0
			],
			[
				'input'    => 100,
				'expected' => 10.0
			],
			[
				'input' => - 1,
				'expected' => null
			],
			[
				'input'    => 1000,
				'expected' => 100.0
			],
		];
	}

	/**
	 * @dataProvider convertToCentimetersProvider
	 */
	public function testConvertToCentimeters( int $input, ?float $expected ): void {
		$result = Helper::convertToCentimeters( $input );
		$this->assertSame( $expected, $result );
	}

	public static function convertToMillimetersProvider(): array {
		return [
			[
				'input'    => 0.0,
				'expected' => null
			],
			[
				'input'    => 0.1,
				'expected' => 1.0
			],
			[
				'input'    => 1.0,
				'expected' => 10.0
			],
			[
				'input'    => 10.0,
				'expected' => 100.0
			],
			[
				'input'    => - 0.1,
				'expected' => null
			],
			[
				'input'    => 100.0,
				'expected' => 1000.0
			],
		];
	}

	/**
	 * @dataProvider convertToMillimetersProvider
	 */
	public function testConvertToMillimeters( float $input, ?float $expected ): void {
		$result = Helper::convertToMillimeters( $input );
		$this->assertSame( $expected, $result );
	}

}
