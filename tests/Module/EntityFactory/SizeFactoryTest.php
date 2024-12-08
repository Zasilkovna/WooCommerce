<?php

declare(strict_types=1);

namespace Tests\Packetery\Module\EntityFactory;

use Packetery\Core\Entity\Order;
use Packetery\Core\Entity\Size;
use Packetery\Module\EntityFactory\SizeFactory;
use Packetery\Module\Options\OptionsProvider;
use PHPUnit\Framework\TestCase;

class SizeFactoryTest extends TestCase {
	/**
	 * @dataProvider provideDataForCreateSizeInSetDimensionUnit
	 */
	public function testCreateSizeInSetDimensionUnit(
		?float $length,
		?float $width,
		?float $height,
		string $dimensionsUnit,
		array $expectedResult
	): void {
		$optionsProvider = $this->createMock( OptionsProvider::class );
		$optionsProvider->method( 'getDimensionsUnit' )->willReturn( $dimensionsUnit );

		$sizeFactory = new SizeFactory( $optionsProvider );

		$order = $this->createMock( Order::class );
		$order->method( 'getLength' )->willReturn( $length );
		$order->method( 'getWidth' )->willReturn( $width );
		$order->method( 'getHeight' )->willReturn( $height );

		$result = $sizeFactory->createSizeInSetDimensionUnit( $order );

		$this->assertInstanceOf( Size::class, $result );
		$this->assertSame( $expectedResult[0], $result->getLength() );
		$this->assertSame( $expectedResult[1], $result->getWidth() );
		$this->assertSame( $expectedResult[2], $result->getHeight() );
	}

	public static function provideDataForCreateSizeInSetDimensionUnit(): array {
		return [
			[ 100, 200, 300, OptionsProvider::DIMENSIONS_UNIT_CM, [ 10.0, 20.0, 30.0 ] ],
			[ 150, null, 375, OptionsProvider::DIMENSIONS_UNIT_CM, [ 15.0, null, 37.5 ] ],
			[ 100, 200, 300, OptionsProvider::DEFAULT_DIMENSIONS_UNIT_MM, [ 100.0, 200.0, 300.0 ] ],
			[ 125.5, 250.7, 375.9, OptionsProvider::DEFAULT_DIMENSIONS_UNIT_MM, [ 125.5, 250.7, 375.9 ] ],
		];
	}

	/**
	 * @dataProvider provideDataForCreateDefaultSizeForNewOrder
	 */
	public function testCreateDefaultSizeForNewOrder(
		string $dimensionsUnit,
		float $defaultLength,
		float $defaultWidth,
		float $defaultHeight,
		array $expectedResult
	): void {
		$optionsProvider = $this->createMock( OptionsProvider::class );
		$optionsProvider->method( 'getDimensionsUnit' )->willReturn( $dimensionsUnit );
		$optionsProvider->method( 'getDefaultLength' )->willReturn( $defaultLength );
		$optionsProvider->method( 'getDefaultWidth' )->willReturn( $defaultWidth );
		$optionsProvider->method( 'getDefaultHeight' )->willReturn( $defaultHeight );

		$sizeFactory = new SizeFactory( $optionsProvider );

		$result = $sizeFactory->createDefaultSizeForNewOrder();

		$this->assertInstanceOf( Size::class, $result );
		$this->assertSame( $expectedResult[0], $result->getLength() );
		$this->assertSame( $expectedResult[1], $result->getWidth() );
		$this->assertSame( $expectedResult[2], $result->getHeight() );
	}

	public static function provideDataForCreateDefaultSizeForNewOrder(): array {
		return [
			[ OptionsProvider::DIMENSIONS_UNIT_CM, 12.5, 25.0, 37.5, [ 125.0, 250.0, 375.0 ] ],
			[ OptionsProvider::DEFAULT_DIMENSIONS_UNIT_MM, 100.0, 200.0, 300.0, [ 100.0, 200.0, 300.0 ] ],
			[ OptionsProvider::DEFAULT_DIMENSIONS_UNIT_MM, 125.5, 250.7, 375.9, [ 125.5, 250.7, 375.9 ] ],
		];
	}
}