<?php

declare( strict_types=1 );

namespace Tests\Module;

use DateTimeImmutable;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\ModuleHelper;
use PHPUnit\Framework\TestCase;

class ModuleHelperTest extends TestCase {
	public static function convertToCentimetersProvider(): array {
		return [
			[
				'input'    => 0,
				'expected' => null,
			],
			[
				'input'    => 1,
				'expected' => 0.1,
			],
			[
				'input'    => 10,
				'expected' => 1.0,
			],
			[
				'input'    => 100,
				'expected' => 10.0,
			],
			[
				'input'    => - 1,
				'expected' => null,
			],
			[
				'input'    => 1000,
				'expected' => 100.0,
			],
		];
	}

	/**
	 * @dataProvider convertToCentimetersProvider
	 */
	public function testConvertToCentimeters( int $input, ?float $expected ): void {
		$result = ModuleHelper::convertToCentimeters( $input );
		$this->assertSame( $expected, $result );
	}

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

	public function testGetTranslatedStringFromDateTimeReturnsFormattedDate(): void {
		$moduleHelper = new ModuleHelper(
			$this->createMock( WpAdapter::class )
		);

		$dummyDateTimeImmutable = new DateTimeImmutable( '2024-03-10 15:30:00' );

		add_filter(
			'woocommerce_admin_order_date_format',
			function () {
				return 'M j, Y';
			}
		);

		$expectedTranslatedString = date_i18n( 'M j, Y', $dummyDateTimeImmutable->getTimestamp() );

		$this->assertEquals(
			$expectedTranslatedString,
			$moduleHelper->getTranslatedStringFromDateTime( $dummyDateTimeImmutable )
		);
	}

	public function testGetTranslatedStringFromDateTimeReturnsNullForNullInput(): void {
		$moduleHelper = new ModuleHelper(
			$this->createMock( WpAdapter::class )
		);

		$this->assertNull( $moduleHelper->getTranslatedStringFromDateTime( null ) );
	}
}
