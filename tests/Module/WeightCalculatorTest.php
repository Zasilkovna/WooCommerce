<?php

declare( strict_types=1 );

namespace Tests\Module;

use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\WeightCalculator;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;

class WeightCalculatorTest extends TestCase {
	private function createOrderItemMock( float $productQuantity, float $weight ): WC_Order_Item_Product {
		$wcProduct = $this->createMock( WC_Product::class );
		$wcProduct
			->method( 'get_weight' )
			->willReturn( $weight );

		$wcOrderItemProduct = $this->createMock( WC_Order_Item_Product::class );
		$wcOrderItemProduct
			->method( 'get_product' )
			->willReturn( $wcProduct );
		$wcOrderItemProduct
			->method( 'get_quantity' )
			->willReturn( $productQuantity );

		return $wcOrderItemProduct;
	}

	public static function getOrderWeightCalculationProvider(): array {
		return [
			'no items, only packaging weight'            => [
				'isDefaultWeightEnabled' => true,
				'defaultWeight'          => 1.3,
				'packagingWeight'        => 0.1,
				'expectedWeight'         => 0.1,
				'orderItems'             => [],
			],
			'no items, no packaging weight'              => [
				'isDefaultWeightEnabled' => true,
				'defaultWeight'          => 100,
				'packagingWeight'        => 0,
				'expectedWeight'         => 0,
				'orderItems'             => [],
			],
			'no items, only high packaging weight'       => [
				'isDefaultWeightEnabled' => true,
				'defaultWeight'          => 0,
				'packagingWeight'        => 100,
				'expectedWeight'         => 100,
				'orderItems'             => [],
			],
			'no items, no weight at all'                 => [
				'isDefaultWeightEnabled' => true,
				'defaultWeight'          => 0,
				'packagingWeight'        => 0,
				'expectedWeight'         => 0,
				'orderItems'             => [],
			],
			'no items, small packaging weight'           => [
				'isDefaultWeightEnabled' => true,
				'defaultWeight'          => 1.0,
				'packagingWeight'        => 0.1,
				'expectedWeight'         => 0.1,
				'orderItems'             => [],
			],
			'no items, small packaging weight duplicate' => [
				'isDefaultWeightEnabled' => true,
				'defaultWeight'          => 1.0,
				'packagingWeight'        => 0.1,
				'expectedWeight'         => 0.1,
				'orderItems'             => [],
			],
			'one item, with packaging weight'            => [
				'isDefaultWeightEnabled' => true,
				'defaultWeight'          => 0.0,
				'packagingWeight'        => 0.1,
				'expectedWeight'         => 2.1,
				'orderItems'             => [
					[
						'productQuantity' => 1,
						'weight'          => 2,
					],
				],
			],
			'one item, default weight disabled'          => [
				'isDefaultWeightEnabled' => false,
				'defaultWeight'          => 10.0,
				'packagingWeight'        => 0.1,
				'expectedWeight'         => 2.1,
				'orderItems'             => [
					[
						'productQuantity' => 1,
						'weight'          => 2,
					],
				],
			],
			'one item, no packaging weight'              => [
				'isDefaultWeightEnabled' => true,
				'defaultWeight'          => 10.0,
				'packagingWeight'        => 0.0,
				'expectedWeight'         => 2.0,
				'orderItems'             => [
					[
						'productQuantity' => 1,
						'weight'          => 2,
					],
				],
			],
			'two items, high packaging weight'           => [
				'isDefaultWeightEnabled' => true,
				'defaultWeight'          => 10.0,
				'packagingWeight'        => 20.0,
				'expectedWeight'         => 24.4,
				'orderItems'             => [
					[
						'productQuantity' => 2,
						'weight'          => 2.2,
					],
				],
			],
			'two items, one with zero quantity'          => [
				'isDefaultWeightEnabled' => true,
				'defaultWeight'          => 10.0,
				'packagingWeight'        => 20.0,
				'expectedWeight'         => 24.4,
				'orderItems'             => [
					[
						'productQuantity' => 2,
						'weight'          => 2.2,
					],
					[
						'productQuantity' => 0,
						'weight'          => 2.2,
					],
				],
			],
			'negative weight item'                       => [
				'isDefaultWeightEnabled' => true,
				'defaultWeight'          => 10.0,
				'packagingWeight'        => 20.0,
				'expectedWeight'         => 20.0,
				'orderItems'             => [
					[
						'productQuantity' => 2,
						'weight'          => -2.2,
					],
				],
			],
			'multiple items, high packaging weight'      => [
				'isDefaultWeightEnabled' => true,
				'defaultWeight'          => 10.0,
				'packagingWeight'        => 20.0,
				'expectedWeight'         => 28.8,
				'orderItems'             => [
					[
						'productQuantity' => 2,
						'weight'          => 2.2,
					],
					[
						'productQuantity' => 2,
						'weight'          => 2.2,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider getOrderWeightCalculationProvider
	 *
	 * @param bool    $isDefaultWeightEnabled
	 * @param float   $defaultWeight
	 * @param float   $packagingWeight
	 * @param float   $expectedWeight
	 * @param array[] $orderItems
	 *
	 * @throws Exception
	 */
	public function testOrderWeightCalculation(
		bool $isDefaultWeightEnabled,
		float $defaultWeight,
		float $packagingWeight,
		float $expectedWeight,
		array $orderItems
	): void {
		$optionsProvider = $this->createMock( OptionsProvider::class );
		$optionsProvider
			->method( 'isDefaultWeightEnabled' )
			->willReturn( $isDefaultWeightEnabled );
		$optionsProvider
			->method( 'getDefaultWeight' )
			->willReturn( $defaultWeight );
		$optionsProvider
			->method( 'getPackagingWeight' )
			->willReturn( $packagingWeight );

		$wcAdapter = $this->createMock( WcAdapter::class );
		$wcAdapter
			->method( 'getWeight' )
			->willReturnCallback(
				static function ( $weight ): float {
					if ( $weight < 0 ) {
						return 0.0;
					}

					return (float) $weight;
				}
			);

		$weightCalculator = new WeightCalculator(
			$optionsProvider,
			$wcAdapter
		);

		$items = [];
		foreach ( $orderItems as $item ) {
			$items[] = $this->createOrderItemMock( ...$item );
		}

		$order = $this->createMock( WC_Order::class );
		$order
			->method( 'get_items' )
			->willReturn( $items );

		$weight = $weightCalculator->calculateOrderWeight( $order );

		$this->assertSame( $expectedWeight, $weight );
	}
}
