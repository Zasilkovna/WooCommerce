<?php

declare( strict_types=1 );

namespace Tests\Core;

use DateTimeImmutable;
use Packetery\Core\CoreHelper;
use PHPUnit\Framework\TestCase;

class CoreHelperTest extends TestCase {

	private CoreHelper $coreHelper;

	public function __construct( string $name ) {
		parent::__construct( $name );
		$this->coreHelper = new CoreHelper( 'dummyTrackingUrl' );
	}

	public function testGetTrackingUrl(): void {
		self::assertIsString( $this->coreHelper->getTrackingUrl( 'dummyPacketId' ) );
	}

	public function testGetTrackingUrlNull(): void {
		self::assertNull( $this->coreHelper->getTrackingUrl( null ) );
	}

	public function testGetStringFromDateTime(): void {
		$dummyDateString = '2023-11-17';
		$dummyDate       = $this->coreHelper->getDateTimeFromString( $dummyDateString );

		self::assertSame(
			$this->coreHelper->getStringFromDateTime( $dummyDate, CoreHelper::MYSQL_DATE_FORMAT ),
			$dummyDateString
		);
	}

	public function testNow(): void {
		self::assertInstanceOf( DateTimeImmutable::class, CoreHelper::now() );
	}

	public static function simplifyWeightProvider(): array {
		return [
			'float-with-3-decimals'        => [
				'expected' => 10.222,
				'weight'   => 10.2222,
			],
			'full-number-without-decimals' => [
				'expected' => 1,
				'weight'   => 1.000000,
			],
			'null'                         => [
				'expected' => null,
				'weight'   => null,
			],
		];
	}

	/**
	 * @dataProvider simplifyWeightProvider
	 */
	public function testSimplifyWeight( ?float $expected, ?float $weight ): void {
		self::assertSame( $expected, CoreHelper::simplifyWeight( $weight ) );
	}

	public static function trimDecimalPlacesProvider(): array {
		return [
			'float-with-3-decimals'                     => [
				'expected' => '4.123',
				'value'    => 4.1234566778,
				'decimals' => 3,
			],
			'single-digit-full-number-without-decimals' => [
				'expected' => '4',
				'value'    => 4.1234566778,
				'decimals' => 0,
			],
			'full-number-without-3-decimals'            => [
				'expected' => '20',
				'value'    => 20.0,
				'decimals' => 3,
			],
			'full-number-without-decimals'              => [
				'expected' => '10',
				'value'    => 10.0,
				'decimals' => 0,
			],
			'negative-float-with-3-decimals'            => [
				'expected' => '-4.123',
				'value'    => - 4.1234566778,
				'decimals' => 3,
			],
			'negative-single-digit-full-number-without-decimals' => [
				'expected' => '-4',
				'value'    => - 4.1234566778,
				'decimals' => 0,
			],
		];
	}

	/**
	 * @dataProvider trimDecimalPlacesProvider
	 */
	public function testTrimDecimalPlaces( string $expected, float $value, int $decimals ): void {
		self::assertSame( $expected, CoreHelper::trimDecimalPlaces( $value, $decimals ) );
	}

	public static function convertToCentimetersProvider(): array {
		return [
			[
				'input'     => 0,
				'inputUnit' => 'mm',
				'expected'  => 0,
			],
			[
				'input'     => 1,
				'inputUnit' => 'mm',
				'expected'  => 0.1,
			],
			[
				'input'     => 10,
				'inputUnit' => 'mm',
				'expected'  => 1.0,
			],
			[
				'input'     => - 1,
				'inputUnit' => 'mm',
				'expected'  => - 0.1,
			],

			[
				'input'     => 0,
				'inputUnit' => 'cm',
				'expected'  => 0,
			],
			[
				'input'     => 1,
				'inputUnit' => 'cm',
				'expected'  => 1,
			],
			[
				'input'     => 10,
				'inputUnit' => 'cm',
				'expected'  => 10,
			],
			[
				'input'     => - 1,
				'inputUnit' => 'cm',
				'expected'  => - 1,
			],

			[
				'input'     => 0,
				'inputUnit' => 'm',
				'expected'  => 0,
			],
			[
				'input'     => 1,
				'inputUnit' => 'm',
				'expected'  => 100,
			],
			[
				'input'     => 10,
				'inputUnit' => 'm',
				'expected'  => 1000,
			],
			[
				'input'     => - 1,
				'inputUnit' => 'm',
				'expected'  => - 100,
			],

			[
				'input'     => 0,
				'inputUnit' => 'in',
				'expected'  => 0,
			],
			[
				'input'     => 1,
				'inputUnit' => 'in',
				'expected'  => 2.54,
			],
			[
				'input'     => 10,
				'inputUnit' => 'in',
				'expected'  => 25.4,
			],
			[
				'input'     => - 1,
				'inputUnit' => 'in',
				'expected'  => - 2.54,
			],

			[
				'input'     => 0,
				'inputUnit' => 'yd',
				'expected'  => 0,
			],
			[
				'input'     => 1,
				'inputUnit' => 'yd',
				'expected'  => 91.44,
			],
			[
				'input'     => 10,
				'inputUnit' => 'yd',
				'expected'  => 914.4,
			],
			[
				'input'     => - 1,
				'inputUnit' => 'yd',
				'expected'  => - 91.44,
			],
		];
	}

	/**
	 * @dataProvider convertToCentimetersProvider
	 */
	public function testConvertToCentimeters( int $input, string $inputUnit, ?float $expected ): void {
		$result = CoreHelper::convertToCentimeters( $input, $inputUnit );
		$this->assertSame( $expected, $result );
	}
}
