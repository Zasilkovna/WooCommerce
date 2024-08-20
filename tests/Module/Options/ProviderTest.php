<?php

declare(strict_types=1);

use Packetery\Module\Options\Provider;
use PHPUnit\Framework\TestCase;

class ProviderTest extends TestCase {

	/**
	 * @dataProvider dimensionsUnitProvider
	 */
	public function testGetDimensionsNumberOfDecimals( string $unit, int $expectedDecimals ): void {
		$provider = $this->getMockBuilder( Provider::class )
		                 ->onlyMethods( [ 'getDimensionsUnit' ] )
		                 ->getMock();

		$provider->method( 'getDimensionsUnit' )
		         ->willReturn( $unit );

		$result = $provider->getDimensionsNumberOfDecimals();
		$this->assertSame( $expectedDecimals, $result );
	}

	public static function dimensionsUnitProvider(): array {
		return [
			[ 'cm', 1 ],
			[ 'mm', 0 ],
		];
	}

	/**
	 * @dataProvider dimensionsUnitProviderInvalid
	 */
	public function testGetDimensionsNumberOfDecimalsForInvalidVals( string $unit, int $expectedDecimals ): void {
		$provider = $this->getMockBuilder( Provider::class )
		                 ->onlyMethods( [ 'getDimensionsUnit' ] )
		                 ->getMock();

		$provider->method( 'getDimensionsUnit' )
		         ->willReturn( $unit );

		$result = $provider->getDimensionsNumberOfDecimals();
		$this->assertNotSame( $expectedDecimals, $result );
	}

	public static function dimensionsUnitProviderInvalid(): array {
		return [
			[ 'in', 1 ],
			[ 'l', 1 ],
		];
	}
}