<?php

declare( strict_types=1 );

namespace Tests\Module;

use Packetery\Module\ModuleHelper;
use PHPUnit\Framework\TestCase;

class ModuleHelperTest extends TestCase {
	public static function convertToMillimetersProvider(): array {
		return [
			[
				'input'    => 0.0,
				'expected' => null,
			],
			[
				'input'    => 0.1,
				'expected' => 1.0,
			],
			[
				'input'    => 1.0,
				'expected' => 10.0,
			],
			[
				'input'    => 10.0,
				'expected' => 100.0,
			],
			[
				'input'    => - 0.1,
				'expected' => null,
			],
			[
				'input'    => 100.0,
				'expected' => 1000.0,
			],
		];
	}

	/**
	 * @dataProvider convertToMillimetersProvider
	 */
	public function testConvertToMillimeters( float $input, ?float $expected ): void {
		$result = ModuleHelper::convertToMillimeters( $input );
		$this->assertSame( $expected, $result );
	}
}
