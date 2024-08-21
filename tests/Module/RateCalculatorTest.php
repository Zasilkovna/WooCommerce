<?php

declare( strict_types=1 );

namespace Tests\Module;

use Packetery\Module\Carrier\Options;
use Packetery\Module\RateCalculator;
use PHPUnit\Framework\TestCase;

class RateCalculatorTest extends TestCase {

	/**
	 * @return array
	 */
	public static function calculationDataProvider(): array {
		return [
			[
				'expectedCost'      => 11,
				CarrierOptionsDummyFactory::getDefaultCarrier(),
				'totalProductValue' => 100,
				'cartWeightKg'      => 0,
				'isCouponApplied'   => false,
			],
			[
				'expectedCost'      => 22,
				CarrierOptionsDummyFactory::getDefaultCarrier(),
				'totalProductValue' => 100,
				'cartWeightKg'      => 15,
				'isCouponApplied'   => false,
			],
			[
				'expectedCost'      => 22,
				CarrierOptionsDummyFactory::getDefaultCarrier(),
				'totalProductValue' => 100,
				'cartWeightKg'      => 20,
				'isCouponApplied'   => false,
			],
			[
				'expectedCost'      => 33,
				CarrierOptionsDummyFactory::getDefaultCarrier(),
				'totalProductValue' => 100,
				'cartWeightKg'      => 25,
				'isCouponApplied'   => false,
			],
			[
				'expectedCost'      => null,
				CarrierOptionsDummyFactory::getDefaultCarrier(),
				'totalProductValue' => 100,
				'cartWeightKg'      => 31,
				'isCouponApplied'   => false,
			],
			[
				'expectedCost'      => 111,
				CarrierOptionsDummyFactory::getProductValuePricingCarrier(),
				'totalProductValue' => 100,
				'cartWeightKg'      => 1,
				'isCouponApplied'   => false,
			],
			[
				'expectedCost'      => 222,
				CarrierOptionsDummyFactory::getProductValuePricingCarrier(),
				'totalProductValue' => 150,
				'cartWeightKg'      => 15,
				'isCouponApplied'   => false,
			],
			[
				'expectedCost'      => 222,
				CarrierOptionsDummyFactory::getProductValuePricingCarrier(),
				'totalProductValue' => 200,
				'cartWeightKg'      => 20,
				'isCouponApplied'   => false,
			],
			[
				'expectedCost'      => 333,
				CarrierOptionsDummyFactory::getProductValuePricingCarrier(),
				'totalProductValue' => 300,
				'cartWeightKg'      => 25,
				'isCouponApplied'   => false,
			],
			[
				'expectedCost'      => null,
				CarrierOptionsDummyFactory::getProductValuePricingCarrier(),
				'totalProductValue' => 400,
				'cartWeightKg'      => 31,
				'isCouponApplied'   => false,
			],
			'free shipping threshold must be reached'     => [
				'expectedCost'      => 0,
				CarrierOptionsDummyFactory::getDefaultCarrier(),
				'totalProductValue' => 15000,
				'cartWeightKg'      => 25,
				'isCouponApplied'   => false,
			],
			'free shipping threshold must not be reached' => [
				'expectedCost'      => 33,
				CarrierOptionsDummyFactory::getNoFreeShippingLimitCarrier(),
				'totalProductValue' => 15000,
				'cartWeightKg'      => 25,
				'isCouponApplied'   => false,
			],
			'free shipping coupon must affect cost'       => [
				'expectedCost'      => 0,
				CarrierOptionsDummyFactory::getDefaultCarrier(),
				'totalProductValue' => 100,
				'cartWeightKg'      => 25,
				'isCouponApplied'   => true,
			],
			'free shipping coupon must not affect cost'   => [
				'expectedCost'      => 33,
				CarrierOptionsDummyFactory::getNoCouponCarrier(),
				'totalProductValue' => 100,
				'cartWeightKg'      => 25,
				'isCouponApplied'   => true,
			],
		];
	}

	/**
	 * @dataProvider calculationDataProvider
	 */
	public function testGetShippingRateCost( ?float $expectedCost, Options $carrierOptions, float $totalProductValue, float $cartWeight, bool $isCouponApplied ): void {
		$wpAdapter = MockFactory::createWpAdapter( $this );

		$rateCalculator = new RateCalculator(
			$wpAdapter,
			MockFactory::createCurrencySwitcherFacade( $this )
		);

		$cost = $rateCalculator->getShippingRateCost(
			$carrierOptions,
			$totalProductValue,
			$totalProductValue,
			$cartWeight,
			$isCouponApplied
		);

		self::assertEquals( $expectedCost, $cost );
	}

}
