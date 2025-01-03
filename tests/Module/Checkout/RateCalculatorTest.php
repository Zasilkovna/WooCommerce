<?php

declare( strict_types=1 );

namespace Tests\Module\Checkout;

use Packetery\Module\Carrier\Options;
use Packetery\Module\Checkout\RateCalculator;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Module\CarrierOptionsDummyFactory;
use Tests\Module\MockFactory;

class RateCalculatorTest extends TestCase {

	private WpAdapter|MockObject $wpAdapter;
	private RateCalculator $rateCalculator;

	private function createRateCalculatorMock(): void {
		$this->wpAdapter = MockFactory::createWpAdapter( $this );

		$this->rateCalculator = new RateCalculator(
			$this->wpAdapter,
			$this->createMock( WcAdapter::class ),
			MockFactory::createCurrencySwitcherFacade( $this ),
		);
	}

	/**
	 * @return array
	 */
	public static function calculationDataProvider(): array {
		return [
			[
				'expectedCost'      => 11.0,
				CarrierOptionsDummyFactory::getDefaultCarrier(),
				'totalProductValue' => 100.0,
				'cartWeightKg'      => 0.0,
				'isCouponApplied'   => false,
			],
			[
				'expectedCost'      => 22.0,
				CarrierOptionsDummyFactory::getDefaultCarrier(),
				'totalProductValue' => 100.0,
				'cartWeightKg'      => 15.0,
				'isCouponApplied'   => false,
			],
			[
				'expectedCost'      => 22.0,
				CarrierOptionsDummyFactory::getDefaultCarrier(),
				'totalProductValue' => 100.0,
				'cartWeightKg'      => 20.0,
				'isCouponApplied'   => false,
			],
			[
				'expectedCost'      => 33.0,
				CarrierOptionsDummyFactory::getDefaultCarrier(),
				'totalProductValue' => 100.0,
				'cartWeightKg'      => 25.0,
				'isCouponApplied'   => false,
			],
			[
				'expectedCost'      => null,
				CarrierOptionsDummyFactory::getDefaultCarrier(),
				'totalProductValue' => 100.0,
				'cartWeightKg'      => 31.0,
				'isCouponApplied'   => false,
			],
			[
				'expectedCost'      => 111.0,
				CarrierOptionsDummyFactory::getProductValuePricingCarrier(),
				'totalProductValue' => 100.0,
				'cartWeightKg'      => 1.0,
				'isCouponApplied'   => false,
			],
			[
				'expectedCost'      => 222.0,
				CarrierOptionsDummyFactory::getProductValuePricingCarrier(),
				'totalProductValue' => 150.0,
				'cartWeightKg'      => 15.0,
				'isCouponApplied'   => false,
			],
			[
				'expectedCost'      => 222.0,
				CarrierOptionsDummyFactory::getProductValuePricingCarrier(),
				'totalProductValue' => 200.0,
				'cartWeightKg'      => 20.0,
				'isCouponApplied'   => false,
			],
			[
				'expectedCost'      => 333.0,
				CarrierOptionsDummyFactory::getProductValuePricingCarrier(),
				'totalProductValue' => 300.0,
				'cartWeightKg'      => 25.0,
				'isCouponApplied'   => false,
			],
			[
				'expectedCost'      => null,
				CarrierOptionsDummyFactory::getProductValuePricingCarrier(),
				'totalProductValue' => 400.0,
				'cartWeightKg'      => 31.0,
				'isCouponApplied'   => false,
			],
			'free shipping threshold must be reached'     => [
				'expectedCost'      => 0.0,
				CarrierOptionsDummyFactory::getDefaultCarrier(),
				'totalProductValue' => 15000.0,
				'cartWeightKg'      => 25.0,
				'isCouponApplied'   => false,
			],
			'free shipping threshold must not be reached' => [
				'expectedCost'      => 33.0,
				CarrierOptionsDummyFactory::getNoFreeShippingLimitCarrier(),
				'totalProductValue' => 15000.0,
				'cartWeightKg'      => 25.0,
				'isCouponApplied'   => false,
			],
			'free shipping coupon must affect cost'       => [
				'expectedCost'      => 0.0,
				CarrierOptionsDummyFactory::getDefaultCarrier(),
				'totalProductValue' => 100.0,
				'cartWeightKg'      => 25.0,
				'isCouponApplied'   => true,
			],
			'free shipping coupon must not affect cost'   => [
				'expectedCost'      => 33.0,
				CarrierOptionsDummyFactory::getNoCouponCarrier(),
				'totalProductValue' => 100.0,
				'cartWeightKg'      => 25.0,
				'isCouponApplied'   => true,
			],
		];
	}

	/**
	 * @dataProvider calculationDataProvider
	 */
	public function testGetShippingRateCost( ?float $expectedCost, Options $carrierOptions, float $totalProductValue, float $cartWeightKg, bool $isCouponApplied ): void {
		$this->createRateCalculatorMock();

		$cost = $this->rateCalculator->getShippingRateCost(
			$carrierOptions,
			$totalProductValue,
			$totalProductValue,
			$cartWeightKg,
			$isCouponApplied
		);

		self::assertEquals( $expectedCost, $cost );
	}

	public function testGetCODSurchargeDefault(): void {
		$this->createRateCalculatorMock();

		$carrierOptions = [];
		$cartPrice      = 100.0;

		$surcharge = $this->rateCalculator->getCODSurcharge( $carrierOptions, $cartPrice );

		self::assertEquals( 0.0, $surcharge );
	}

	public function testGetCODSurchargeWithDefault(): void {
		$this->createRateCalculatorMock();

		$carrierOptions = [ 'default_COD_surcharge' => 15.0 ];
		$cartPrice      = 100.0;

		$surcharge = $this->rateCalculator->getCODSurcharge( $carrierOptions, $cartPrice );

		self::assertEquals( 15.0, $surcharge );
	}

	public function testGetCODSurchargeWithSurchargeLimits(): void {
		$this->createRateCalculatorMock();

		$carrierOptions = [
			'surcharge_limits'      => [
				[
					'order_price' => 50.0,
					'surcharge'   => 10.0,
				],
				[
					'order_price' => 150.0,
					'surcharge'   => 5.0,
				],
			],
			'default_COD_surcharge' => 15.0,
		];
		$cartPrice      = 100.0;

		$surcharge = $this->rateCalculator->getCODSurcharge( $carrierOptions, $cartPrice );

		self::assertEquals( 5.0, $surcharge );
	}

	public function testGetCODSurchargeBelowSurchargeLimits(): void {
		$this->createRateCalculatorMock();

		$carrierOptions = [
			'surcharge_limits'      => [
				[
					'order_price' => 50.0,
					'surcharge'   => 10.45,
				],
				[
					'order_price' => 150.0,
					'surcharge'   => 5.0,
				],
			],
			'default_COD_surcharge' => 15.0,
		];
		$cartPrice      = 40.0;

		$surcharge = $this->rateCalculator->getCODSurcharge( $carrierOptions, $cartPrice );

		self::assertEquals( 10.45, $surcharge );
	}

	public function testIsFreeShippingCouponAppliedReturnsFalseWhenCartOrOrderIsNull(): void {
		$this->createRateCalculatorMock();

		$isFreeShippingCouponApplied = $this->rateCalculator->isFreeShippingCouponApplied( null );

		self::assertFalse( $isFreeShippingCouponApplied, 'Expected isFreeShippingCouponApplied to return false when $cartOrOrder is null.' );
	}

	public function testIsFreeShippingCouponAppliedReturnsFalseWhenNoFreeShippingCoupons(): void {
		$this->createRateCalculatorMock();

		$cartWithNoFreeShippingCoupon = $this->createMock( \WC_Cart::class );
		$cartWithNoFreeShippingCoupon->method( 'get_coupons' )->willReturn( [] );
		$isFreeShippingCouponApplied = $this->rateCalculator->isFreeShippingCouponApplied( $cartWithNoFreeShippingCoupon );

		self::assertFalse( $isFreeShippingCouponApplied, 'Expected isFreeShippingCouponApplied to return false when $cartOrOrder does not have any free shipping coupons.' );
	}

	public function testIsFreeShippingCouponAppliedReturnsTrueWhenFreeShippingCouponExists(): void {
		$this->createRateCalculatorMock();

		$couponWithFreeShipping = $this->createMock( \WC_Coupon::class );
		$couponWithFreeShipping->method( 'get_free_shipping' )->willReturn( true );
		$orderWithFreeShippingCoupon = $this->createMock( \WC_Order::class );
		$orderWithFreeShippingCoupon->method( 'get_coupons' )->willReturn( [ $couponWithFreeShipping ] );
		$isFreeShippingCouponApplied = $this->rateCalculator->isFreeShippingCouponApplied( $orderWithFreeShippingCoupon );

		self::assertTrue( $isFreeShippingCouponApplied, 'Expected isFreeShippingCouponApplied to return true when $cartOrOrder has a free shipping coupon.' );
	}
}
