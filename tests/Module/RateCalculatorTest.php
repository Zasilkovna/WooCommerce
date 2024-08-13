<?php

declare( strict_types=1 );

namespace Tests\Module;

use Packetery\Module\Bridge;
use Packetery\Module\Carrier\Options;
use Packetery\Module\RateCalculator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RateCalculatorTest extends TestCase {
	use WithMockFactory;

	private MockObject|Bridge $bridge;

	private RateCalculator $rateCalculator;

	public function setUp(): void {
		parent::setUp();

		$this->bridge         = $this->getPacketeryMockFactory()->createBridge();
		$this->rateCalculator = new RateCalculator(
			$this->bridge,
			$this->getPacketeryMockFactory()->createCurrencySwitcherFacade()
		);
	}

	/**
	 * @return mixed[]
	 */
	public static function calculationDataProvider(): array {
		$defaultCarrierOptionsArray = [
			'id'                   => '106',
			'free_shipping_limit'  => 10000.0,
			'pricing_type'         => Options::PRICING_TYPE_BY_WEIGHT,
			'weight_limits'        => [
				[
					'weight' => 10,
					'price'  => 11,
				],
				[
					'weight' => 20,
					'price'  => 22,
				],
				[
					'weight' => 30,
					'price'  => 33,
				],
			],
			'product_value_limits' => [
				[
					'value' => 100,
					'price' => 111,
				],
				[
					'value' => 200,
					'price' => 222,
				],
				[
					'value' => 300,
					'price' => 333,
				],
			],
			'coupon_free_shipping' => [
				'active'         => true,
				'allow_for_fees' => false,
			],
		];

		$defaultCarrierOptions             = new Options( 'any', $defaultCarrierOptionsArray );
		$carrierOptionsNoCoupon            = new Options(
			'any',
			array_merge(
				$defaultCarrierOptionsArray,
				[
					'coupon_free_shipping' => [
						'active' => false,
					],
				]
			)
		);
		$carrierOptionsProductValuePricing = new Options(
			'any',
			array_merge(
				$defaultCarrierOptionsArray,
				[
					'pricing_type' => Options::PRICING_TYPE_BY_PRODUCT_VALUE,
				]
			)
		);
		$carrierOptionsNoFreeShippingLimit = new Options(
			'any',
			array_merge(
				$defaultCarrierOptionsArray,
				[
					'free_shipping_limit' => null,
				]
			)
		);

		return [
			[ 11, $defaultCarrierOptions, 100, 0, false ],
			[ 22, $defaultCarrierOptions, 100, 15, false ],
			[ 22, $defaultCarrierOptions, 100, 20, false ],
			[ 33, $defaultCarrierOptions, 100, 25, false ],
			[ null, $defaultCarrierOptions, 100, 31, false ],
			[ 111, $carrierOptionsProductValuePricing, 100, 1, false ],
			[ 222, $carrierOptionsProductValuePricing, 150, 15, false ],
			[ 222, $carrierOptionsProductValuePricing, 200, 20, false ],
			[ 333, $carrierOptionsProductValuePricing, 300, 25, false ],
			[ null, $carrierOptionsProductValuePricing, 400, 31, false ],
			'free shipping threshold must be reached'     => [
				0,
				$defaultCarrierOptions,
				15000,
				25,
				false
			],
			'free shipping threshold must not be reached' => [
				33,
				$carrierOptionsNoFreeShippingLimit,
				15000,
				25,
				false
			],
			'free shipping coupon must effect cost'       => [
				0,
				$defaultCarrierOptions,
				100,
				25,
				true
			],
			'free shipping coupon must not effect cost'   => [
				33,
				$carrierOptionsNoCoupon,
				100,
				25,
				true
			],
		];
	}

	/**
	 * @dataProvider calculationDataProvider
	 */
	public function testCalculation( ?float $expectedCost, Options $carrierOptions, float $totalProductValue, float $cartWeight, bool $isCouponApplied ): void {
		$this->bridge
			->expects( self::once() )
			->method( 'applyFilters' )
			->with( 'packeta_shipping_price', self::anything(), self::anything() );

		$cost = $this->rateCalculator->getShippingRateCost(
			$carrierOptions,
			$totalProductValue,
			$totalProductValue,
			$cartWeight,
			$isCouponApplied
		);

		self::assertEquals( $expectedCost, $cost );
	}
}