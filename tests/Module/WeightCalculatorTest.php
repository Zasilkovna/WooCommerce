<?php

declare( strict_types=1 );

namespace packeta\tests\Module;

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

	/**
	 * @return array<array{0: bool, 1: float, 2: float, 3: float, 4: array[]}>
	 */
	public static function getOrderWeightCalculationProvider(): array {
		return [
			[ true, 1.3, 0.1, 1.4, [] ],
			[ true, 100, 0, 100, [] ],
			[ true, 0, 100, 100, [] ],
			[ true, 0, 0, 0, [] ],
			[ true, 1.0, 0.1, 1.1, [] ],
			[
				true,
				1.0,
				0.1,
				1.1,
				[],
			],
			[
				true,
				0.0,
				0.1,
				2.1,
				[
					[
						'productQuantity' => 1,
						'weight'          => 2,
					],
				],
			],
			[
				false,
				10.0,
				0.1,
				2.1,
				[
					[
						'productQuantity' => 1,
						'weight'          => 2,
					],
				],
			],
			[
				true,
				10.0,
				0.0,
				2.0,
				[
					[
						'productQuantity' => 1,
						'weight'          => 2,
					],
				],
			],
			[
				true,
				10.0,
				20.0,
				24.4,
				[
					[
						'productQuantity' => 2,
						'weight'          => 2.2,
					],
				],
			],
			[
				true,
				10.0,
				20.0,
				24.4,
				[
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
			[
				true,
				10.0,
				20.0,
				30.0,
				[
					[
						'productQuantity' => 2,
						'weight'          => -2.2,
					],
				],
			],
			[
				true,
				10.0,
				20.0,
				28.8,
				[
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

		$this->assertEquals( $expectedWeight, $weight );
	}
}
