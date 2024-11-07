<?php

declare( strict_types=1 );

namespace Tests\Core;

use DateTimeImmutable;
use Packetery\Core\CoreHelper;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase {

	private CoreHelper $helper;

	public function __construct( string $name ) {
		parent::__construct( $name );
		$this->helper = new CoreHelper();
	}

	public function testGetTrackingUrl(): void {
		self::assertIsString( $this->helper->get_tracking_url( 'dummyPacketId' ) );
	}

	public function testGetStringFromDateTime(): void {
		$dummyDateString = '2023-11-17';
		$dummyDate       = $this->helper->getDateTimeFromString( $dummyDateString );

		self::assertSame(
			$this->helper->getStringFromDateTime( $dummyDate, CoreHelper::MYSQL_DATE_FORMAT ),
			$dummyDateString
		);
	}

	public function testNow(): void {
		self::assertInstanceOf( DateTimeImmutable::class, CoreHelper::now() );
	}

	public static function simplifyWeightProvider(): array {
		return [
			'float-with-3-decimals' => [
					'expected' => 10.222,
					'weight' => 10.2222
			],
			'full-number-without-decimals' => [
					'expected' => 1,
					'weight' => 1.000000
			],
			'null' => [
					'expected' => null,
					'weight' => null
			]
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
			'float-with-3-decimals' => [
				'expected' => '4.123',
				'value' => 4.1234566778,
				'decimals' => 3
			],
			'single-digit-full-number-without-decimals' => [
				'expected' => '4',
				'value' => 4.1234566778,
				'decimals' => 0
			],
			'full-number-without-3-decimals' => [
				'expected' => '20',
				'value' => 20.0,
				'decimals' => 3
			],
			'full-number-without-decimals' => [
				'expected' => '10',
				'value' => 10.0,
				'decimals' => 0
			],
			'negative-float-with-3-decimals' => [
				'expected' => '-4.123',
				'value' => - 4.1234566778,
				'decimals' => 3
			],
			'negative-single-digit-full-number-without-decimals' => [
				'expected' => '-4',
				'value' => - 4.1234566778,
				'decimals' => 0
			],
		];
	}

	/**
	 * @dataProvider trimDecimalPlacesProvider
	 */
	public function testTrimDecimalPlaces( string $expected, float $value, int $decimals): void {
		self::assertSame( $expected, CoreHelper::trimDecimalPlaces( $value, $decimals ) );
	}

}
