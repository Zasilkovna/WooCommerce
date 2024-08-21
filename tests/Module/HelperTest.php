<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Packetery\Module\Helper;

class HelperTest extends TestCase
{
	public static function convertToCentimetersProvider(): array
	{
		return [
			[0, null],
			[1, 0.1],
			[10, 1.0],
			[100, 10.0],
			[-1, null],
			[1000, 100.0],
		];
	}

	/**
	* @dataProvider convertToCentimetersProvider
	*/
	public function testConvertToCentimeters(int $input, ?float $expected): void
	{
		$result = Helper::convertToCentimeters($input);
		$this->assertSame($expected, $result);
	}

	public static function convertToMillimetersProvider(): array
	{
		return [
			[0.0, null],
			[0.1, 1.0],
			[1.0, 10.0],
			[10.0, 100.0],
			[-0.1, null],
			[100.0, 1000.0],
		];
	}

	/**
	* @dataProvider convertToMillimetersProvider
	*/
	public function testConvertToMillimeters(float $input, ?float $expected): void
	{
		$result = Helper::convertToMillimeters($input);
		$this->assertSame($expected, $result);
	}

}