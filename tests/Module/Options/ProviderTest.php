<?php

declare( strict_types=1 );

use Packetery\Module\Options\Provider;
use PHPUnit\Framework\TestCase;

class ProviderTest extends TestCase {

	public static function dimensionsUnitProvider(): array {
		return [
			[ 'cm', 1 ],
			[ 'mm', 0 ],
			[ 'in', 0 ],
			[ 'l', 0 ],
		];
	}

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

	public static function sanitiseDimensionProvider(): array {
		return [
			[ '', null, 1, 'cm' ],
			[ 23.3567, 234, 1, 'cm' ],
			[ 10.0, 100, 1, 'cm' ],
			[ 0.100000000, 1, 1, 'cm' ],
			[ 200, 200, 0, 'mm' ],
			[ 200, 200, 0, 'mm' ],
		];
	}

	/**
	 * @dataProvider sanitiseDimensionProvider
	 */
	public function testGetSanitizedDimensionValueInMm(
		float|int|string $dimensionValue,
		?int $expectedValue,
		int $numberOfDecimals,
		string $unit
	): void {
		$provider = $this->getMockBuilder( Provider::class )
		                 ->onlyMethods( [ 'getDimensionsNumberOfDecimals', 'getDimensionsUnit' ] )
		                 ->getMock();

		$provider->method( 'getDimensionsNumberOfDecimals' )
		         ->willReturn( $numberOfDecimals );

		$provider->method( 'getDimensionsUnit' )
		         ->willReturn( $unit );

		$result = $provider->getSanitizedDimensionValueInMm( $dimensionValue );
		$this->assertEquals( $expectedValue, $result );
	}

}
