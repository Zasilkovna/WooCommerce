<?php

declare( strict_types=1 );

namespace Tests\Module\Checkout;

use Packetery\Module\Carrier\Options;
use Packetery\Module\Carrier\OptionsPage;
use Packetery\Module\Checkout\CurrencySwitcherService;
use Packetery\Module\Checkout\RateCalculator;
use Packetery\Module\DiagnosticsLogger\DiagnosticsLogger;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Module\CarrierOptionsDummyFactory;
use Tests\Module\MockFactory;

class RateCalculatorTest extends TestCase {

	private WpAdapter|MockObject $wpAdapter;
	private RateCalculator $rateCalculator;

	private function createRateCalculator( ?WpAdapter $wpAdapter = null, ?CurrencySwitcherService $currencySwitcherService = null ): RateCalculator {
		return new RateCalculator(
			$wpAdapter ?? MockFactory::createWpAdapter( $this ),
			$this->createMock( WcAdapter::class ),
			$currencySwitcherService ?? MockFactory::createCurrencySwitcherFacade( $this ),
			$this->createMock( DiagnosticsLogger::class )
		);
	}

	private function createRateCalculatorMock(): void {
		$this->wpAdapter      = MockFactory::createWpAdapter( $this );
		$this->rateCalculator = $this->createRateCalculator( $this->wpAdapter );
	}

	/**
	 * @param array<string, mixed> $overriddenOptions
	 * @param string[]             $removedOptions
	 */
	private static function createCarrier( array $overriddenOptions = [], array $removedOptions = [] ): Options {
		$options = array_merge( CarrierOptionsDummyFactory::getDefaultCarrier()->toArray(), $overriddenOptions );
		foreach ( $removedOptions as $removedOption ) {
			unset( $options[ $removedOption ] );
		}

		return new Options( 'any', $options );
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function weightPricingProvider(): array {
		$carrier = self::createCarrier();

		return [
			'weight below the lowest rule'             => [
				'carrierOptions' => $carrier,
				'cartWeight'     => 0.0,
				'expectedCost'   => 11.0,
			],
			'integer weight is accepted'               => [
				'carrierOptions' => $carrier,
				'cartWeight'     => 5,
				'expectedCost'   => 11.0,
			],
			'weight exactly on the lowest rule limit'  => [
				'carrierOptions' => $carrier,
				'cartWeight'     => 10.0,
				'expectedCost'   => 11.0,
			],
			'weight between the first and second rule' => [
				'carrierOptions' => $carrier,
				'cartWeight'     => 15.0,
				'expectedCost'   => 22.0,
			],
			'weight exactly on the second rule limit'  => [
				'carrierOptions' => $carrier,
				'cartWeight'     => 20.0,
				'expectedCost'   => 22.0,
			],
			'weight between the second and third rule' => [
				'carrierOptions' => $carrier,
				'cartWeight'     => 25.0,
				'expectedCost'   => 33.0,
			],
			'weight exactly on the highest rule limit' => [
				'carrierOptions' => $carrier,
				'cartWeight'     => 30.0,
				'expectedCost'   => 33.0,
			],
			'weight just above the highest rule limit' => [
				'carrierOptions' => $carrier,
				'cartWeight'     => 30.01,
				'expectedCost'   => null,
			],
			'weight above the highest rule limit ignores matching product value rules' => [
				'carrierOptions' => $carrier,
				'cartWeight'     => 31.0,
				'expectedCost'   => null,
			],
			'no weight rules configured'               => [
				'carrierOptions' => self::createCarrier( [ OptionsPage::FORM_FIELD_WEIGHT_LIMITS => [] ] ),
				'cartWeight'     => 0.0,
				'expectedCost'   => null,
			],
			'weight rules missing in carrier options'  => [
				'carrierOptions' => self::createCarrier( [], [ OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] ),
				'cartWeight'     => 0.0,
				'expectedCost'   => null,
			],
		];
	}

	/**
	 * @dataProvider weightPricingProvider
	 */
	public function testGetShippingRateCostByWeight( Options $carrierOptions, float|int $cartWeight, ?float $expectedCost ): void {
		$this->createRateCalculatorMock();

		$cost = $this->rateCalculator->getShippingRateCost(
			$carrierOptions,
			100.0,
			100.0,
			$cartWeight,
			false
		);

		self::assertSame( $expectedCost, $cost );
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function productValuePricingProvider(): array {
		$carrier = self::createCarrier( [ OptionsPage::FORM_FIELD_PRICING_TYPE => Options::PRICING_TYPE_BY_PRODUCT_VALUE ] );

		return [
			'product value below the lowest rule' => [
				'carrierOptions'        => $carrier,
				'totalCartProductValue' => 0.0,
				'expectedCost'          => 111.0,
			],
			'product value exactly on the lowest rule limit' => [
				'carrierOptions'        => $carrier,
				'totalCartProductValue' => 100.0,
				'expectedCost'          => 111.0,
			],
			'product value between the first and second rule' => [
				'carrierOptions'        => $carrier,
				'totalCartProductValue' => 150.0,
				'expectedCost'          => 222.0,
			],
			'product value exactly on the second rule limit' => [
				'carrierOptions'        => $carrier,
				'totalCartProductValue' => 200.0,
				'expectedCost'          => 222.0,
			],
			'product value between the second and third rule' => [
				'carrierOptions'        => $carrier,
				'totalCartProductValue' => 250.0,
				'expectedCost'          => 333.0,
			],
			'product value exactly on the highest rule limit' => [
				'carrierOptions'        => $carrier,
				'totalCartProductValue' => 300.0,
				'expectedCost'          => 333.0,
			],
			'product value just above the highest rule limit' => [
				'carrierOptions'        => $carrier,
				'totalCartProductValue' => 300.01,
				'expectedCost'          => null,
			],
			'product value far above the highest rule limit' => [
				'carrierOptions'        => $carrier,
				'totalCartProductValue' => 400.0,
				'expectedCost'          => null,
			],
			'no product value rules configured'   => [
				'carrierOptions'        => self::createCarrier(
					[
						OptionsPage::FORM_FIELD_PRICING_TYPE         => Options::PRICING_TYPE_BY_PRODUCT_VALUE,
						OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS => [],
					]
				),
				'totalCartProductValue' => 0.0,
				'expectedCost'          => null,
			],
			'product value rules missing in carrier options' => [
				'carrierOptions'        => self::createCarrier(
					[ OptionsPage::FORM_FIELD_PRICING_TYPE => Options::PRICING_TYPE_BY_PRODUCT_VALUE ],
					[ OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ]
				),
				'totalCartProductValue' => 0.0,
				'expectedCost'          => null,
			],
		];
	}

	/**
	 * @dataProvider productValuePricingProvider
	 */
	public function testGetShippingRateCostByProductValue( Options $carrierOptions, float $totalCartProductValue, ?float $expectedCost ): void {
		$this->createRateCalculatorMock();

		$cost = $this->rateCalculator->getShippingRateCost(
			$carrierOptions,
			100.0,
			$totalCartProductValue,
			999.0,
			false
		);

		self::assertSame( $expectedCost, $cost, 'Weight must not influence the cost of a carrier priced by product value.' );
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function ruleOrderProvider(): array {
		return [
			'unsorted weight rules use the first matching one, not the cheapest' => [
				'carrierOptions'        => self::createCarrier(
					[
						OptionsPage::FORM_FIELD_WEIGHT_LIMITS => [
							[
								'weight' => 30,
								'price'  => 33,
							],
							[
								'weight' => 10,
								'price'  => 11,
							],
						],
					]
				),
				'totalCartProductValue' => 100.0,
				'cartWeight'            => 5.0,
				'expectedCost'          => 33.0,
			],
			'unsorted weight rules skip the non matching first rule' => [
				'carrierOptions'        => self::createCarrier(
					[
						OptionsPage::FORM_FIELD_WEIGHT_LIMITS => [
							[
								'weight' => 5,
								'price'  => 5,
							],
							[
								'weight' => 30,
								'price'  => 33,
							],
							[
								'weight' => 10,
								'price'  => 11,
							],
						],
					]
				),
				'totalCartProductValue' => 100.0,
				'cartWeight'            => 7.0,
				'expectedCost'          => 33.0,
			],
			'duplicate weight rules use the first one' => [
				'carrierOptions'        => self::createCarrier(
					[
						OptionsPage::FORM_FIELD_WEIGHT_LIMITS => [
							[
								'weight' => 10,
								'price'  => 11,
							],
							[
								'weight' => 10,
								'price'  => 99,
							],
						],
					]
				),
				'totalCartProductValue' => 100.0,
				'cartWeight'            => 10.0,
				'expectedCost'          => 11.0,
			],
			'unsorted product value rules use the first matching one' => [
				'carrierOptions'        => self::createCarrier(
					[
						OptionsPage::FORM_FIELD_PRICING_TYPE         => Options::PRICING_TYPE_BY_PRODUCT_VALUE,
						OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS => [
							[
								'value' => 300,
								'price' => 333,
							],
							[
								'value' => 100,
								'price' => 111,
							],
						],
					]
				),
				'totalCartProductValue' => 50.0,
				'cartWeight'            => 0.0,
				'expectedCost'          => 333.0,
			],
			'unsorted product value rules skip the non matching first rule' => [
				'carrierOptions'        => self::createCarrier(
					[
						OptionsPage::FORM_FIELD_PRICING_TYPE         => Options::PRICING_TYPE_BY_PRODUCT_VALUE,
						OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS => [
							[
								'value' => 50,
								'price' => 55,
							],
							[
								'value' => 300,
								'price' => 333,
							],
							[
								'value' => 100,
								'price' => 111,
							],
						],
					]
				),
				'totalCartProductValue' => 70.0,
				'cartWeight'            => 0.0,
				'expectedCost'          => 333.0,
			],
		];
	}

	/**
	 * @dataProvider ruleOrderProvider
	 */
	public function testGetShippingRateCostUsesFirstMatchingRule( Options $carrierOptions, float $totalCartProductValue, float $cartWeight, ?float $expectedCost ): void {
		$this->createRateCalculatorMock();

		$cost = $this->rateCalculator->getShippingRateCost(
			$carrierOptions,
			100.0,
			$totalCartProductValue,
			$cartWeight,
			false
		);

		self::assertSame( $expectedCost, $cost );
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function freeShippingLimitProvider(): array {
		return [
			'cart price below the free shipping limit' => [
				'carrierOptions' => self::createCarrier(),
				'cartPrice'      => 9999.99,
				'cartWeight'     => 25.0,
				'expectedCost'   => 33.0,
			],
			'cart price exactly on the free shipping limit' => [
				'carrierOptions' => self::createCarrier(),
				'cartPrice'      => 10000.0,
				'cartWeight'     => 25.0,
				'expectedCost'   => 0.0,
			],
			'cart price above the free shipping limit' => [
				'carrierOptions' => self::createCarrier(),
				'cartPrice'      => 10000.01,
				'cartWeight'     => 25.0,
				'expectedCost'   => 0.0,
			],
			'no free shipping limit configured'        => [
				'carrierOptions' => CarrierOptionsDummyFactory::getNoFreeShippingLimitCarrier(),
				'cartPrice'      => 15000.0,
				'cartWeight'     => 25.0,
				'expectedCost'   => 33.0,
			],
			'zero free shipping limit is treated as disabled' => [
				'carrierOptions' => self::createCarrier( [ 'free_shipping_limit' => 0.0 ] ),
				'cartPrice'      => 0.0,
				'cartWeight'     => 25.0,
				'expectedCost'   => 33.0,
			],
			'free shipping limit does not make an unavailable carrier free' => [
				'carrierOptions' => self::createCarrier(),
				'cartPrice'      => 20000.0,
				'cartWeight'     => 31.0,
				'expectedCost'   => null,
			],
		];
	}

	/**
	 * @dataProvider freeShippingLimitProvider
	 */
	public function testGetShippingRateCostFreeShippingLimit( Options $carrierOptions, float $cartPrice, float $cartWeight, ?float $expectedCost ): void {
		$this->createRateCalculatorMock();

		$cost = $this->rateCalculator->getShippingRateCost(
			$carrierOptions,
			$cartPrice,
			100.0,
			$cartWeight,
			false
		);

		self::assertSame( $expectedCost, $cost );
	}

	public function testFreeShippingLimitIsConvertedByCurrencySwitcher(): void {
		$currencySwitcherService = $this->createMock( CurrencySwitcherService::class );
		$currencySwitcherService->method( 'getConvertedPrice' )
			->willReturnCallback(
				static function ( float $price ): float {
					return $price * 2;
				}
			);
		$rateCalculator = $this->createRateCalculator( null, $currencySwitcherService );

		$costBelowConvertedLimit = $rateCalculator->getShippingRateCost(
			self::createCarrier(),
			15000.0,
			100.0,
			25.0,
			false
		);
		$costOnConvertedLimit    = $rateCalculator->getShippingRateCost(
			self::createCarrier(),
			20000.0,
			100.0,
			25.0,
			false
		);

		self::assertSame( 33.0, $costBelowConvertedLimit, 'Free shipping must be evaluated against the converted limit.' );
		self::assertSame( 0.0, $costOnConvertedLimit );
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function couponFreeShippingProvider(): array {
		return [
			'coupon applied and carrier coupon free shipping active' => [
				'carrierOptions'  => self::createCarrier(),
				'cartPrice'       => 100.0,
				'cartWeight'      => 25.0,
				'isCouponApplied' => true,
				'expectedCost'    => 0.0,
			],
			'coupon applied and carrier coupon free shipping inactive' => [
				'carrierOptions'  => CarrierOptionsDummyFactory::getNoCouponCarrier(),
				'cartPrice'       => 100.0,
				'cartWeight'      => 25.0,
				'isCouponApplied' => true,
				'expectedCost'    => 33.0,
			],
			'coupon not applied and carrier coupon free shipping active' => [
				'carrierOptions'  => self::createCarrier(),
				'cartPrice'       => 100.0,
				'cartWeight'      => 25.0,
				'isCouponApplied' => false,
				'expectedCost'    => 33.0,
			],
			'coupon not applied and carrier coupon free shipping inactive' => [
				'carrierOptions'  => CarrierOptionsDummyFactory::getNoCouponCarrier(),
				'cartPrice'       => 100.0,
				'cartWeight'      => 25.0,
				'isCouponApplied' => false,
				'expectedCost'    => 33.0,
			],
			'coupon keeps cost already zeroed by the free shipping limit' => [
				'carrierOptions'  => self::createCarrier(),
				'cartPrice'       => 10000.0,
				'cartWeight'      => 25.0,
				'isCouponApplied' => true,
				'expectedCost'    => 0.0,
			],
			'coupon does not make an unavailable carrier free' => [
				'carrierOptions'  => self::createCarrier(),
				'cartPrice'       => 100.0,
				'cartWeight'      => 31.0,
				'isCouponApplied' => true,
				'expectedCost'    => null,
			],
		];
	}

	/**
	 * @dataProvider couponFreeShippingProvider
	 */
	public function testGetShippingRateCostCouponFreeShipping(
		Options $carrierOptions,
		float $cartPrice,
		float $cartWeight,
		bool $isCouponApplied,
		?float $expectedCost
	): void {
		$this->createRateCalculatorMock();

		$cost = $this->rateCalculator->getShippingRateCost(
			$carrierOptions,
			$cartPrice,
			100.0,
			$cartWeight,
			$isCouponApplied
		);

		self::assertSame( $expectedCost, $cost );
	}

	public function testGetShippingRateCostIsPassedThroughFilter(): void {
		$wpAdapter = $this->createMock( WpAdapter::class );
		$wpAdapter->method( 'applyFilters' )
			->willReturnCallback(
				static function ( string $hookName, $value ) {
					return $value + 5;
				}
			);
		$rateCalculator = $this->createRateCalculator( $wpAdapter );

		$cost = $rateCalculator->getShippingRateCost(
			self::createCarrier(),
			100.0,
			100.0,
			25.0,
			false
		);

		self::assertSame( 38.0, $cost );
	}

	public function testFilterIsNotAppliedWhenNoPricingRuleMatches(): void {
		$wpAdapter = $this->createMock( WpAdapter::class );
		$wpAdapter->expects( self::never() )->method( 'applyFilters' );
		$rateCalculator = $this->createRateCalculator( $wpAdapter );

		$cost = $rateCalculator->getShippingRateCost(
			self::createCarrier(),
			100.0,
			100.0,
			31.0,
			false
		);

		self::assertNull( $cost );
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function filterParametersProvider(): array {
		$defaultWeightLimits = [
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
		];

		$defaultProductValueLimits = [
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
		];

		return [
			'weight pricing'                         => [
				'carrierOptions'           => self::createCarrier(),
				'cartPrice'                => 100.0,
				'expectedCost'             => 33.0,
				'expectedFilterParameters' => [
					'carrier_id'                          => '106',
					'free_shipping_limit'                 => 10000.0,
					OptionsPage::FORM_FIELD_PRICING_TYPE  => Options::PRICING_TYPE_BY_WEIGHT,
					OptionsPage::FORM_FIELD_WEIGHT_LIMITS => $defaultWeightLimits,
					OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS => $defaultProductValueLimits,
				],
			],
			'product value pricing'                  => [
				'carrierOptions'           => self::createCarrier( [ OptionsPage::FORM_FIELD_PRICING_TYPE => Options::PRICING_TYPE_BY_PRODUCT_VALUE ] ),
				'cartPrice'                => 100.0,
				'expectedCost'             => 111.0,
				'expectedFilterParameters' => [
					'carrier_id'                          => '106',
					'free_shipping_limit'                 => 10000.0,
					OptionsPage::FORM_FIELD_PRICING_TYPE  => Options::PRICING_TYPE_BY_PRODUCT_VALUE,
					OptionsPage::FORM_FIELD_WEIGHT_LIMITS => $defaultWeightLimits,
					OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS => $defaultProductValueLimits,
				],
			],
			'no free shipping limit configured'      => [
				'carrierOptions'           => CarrierOptionsDummyFactory::getNoFreeShippingLimitCarrier(),
				'cartPrice'                => 100.0,
				'expectedCost'             => 33.0,
				'expectedFilterParameters' => [
					'carrier_id'                          => '106',
					'free_shipping_limit'                 => null,
					OptionsPage::FORM_FIELD_PRICING_TYPE  => Options::PRICING_TYPE_BY_WEIGHT,
					OptionsPage::FORM_FIELD_WEIGHT_LIMITS => $defaultWeightLimits,
					OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS => $defaultProductValueLimits,
				],
			],
			'cost zeroed by the free shipping limit' => [
				'carrierOptions'           => self::createCarrier(),
				'cartPrice'                => 10000.0,
				'expectedCost'             => 0.0,
				'expectedFilterParameters' => [
					'carrier_id'                          => '106',
					'free_shipping_limit'                 => 10000.0,
					OptionsPage::FORM_FIELD_PRICING_TYPE  => Options::PRICING_TYPE_BY_WEIGHT,
					OptionsPage::FORM_FIELD_WEIGHT_LIMITS => $defaultWeightLimits,
					OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS => $defaultProductValueLimits,
				],
			],
		];
	}

	/**
	 * @dataProvider filterParametersProvider
	 */
	public function testFilterReceivesExpectedParameters( Options $carrierOptions, float $cartPrice, float $expectedCost, array $expectedFilterParameters ): void {
		$capturedHookName   = null;
		$capturedCost       = null;
		$capturedParameters = null;

		$wpAdapter = $this->createMock( WpAdapter::class );
		$wpAdapter->method( 'applyFilters' )
			->willReturnCallback(
				static function ( string $hookName, $value, ...$args ) use ( &$capturedHookName, &$capturedCost, &$capturedParameters ) {
					$capturedHookName   = $hookName;
					$capturedCost       = $value;
					$capturedParameters = $args[0] ?? null;

					return $value;
				}
			);
		$rateCalculator = $this->createRateCalculator( $wpAdapter );

		$cost = $rateCalculator->getShippingRateCost(
			$carrierOptions,
			$cartPrice,
			100.0,
			25.0,
			false
		);

		self::assertSame( $expectedCost, $cost );
		self::assertSame( 'packeta_shipping_price', $capturedHookName );
		self::assertSame( $expectedCost, $capturedCost, 'Filter must receive the cost as float.' );
		self::assertSame( $expectedFilterParameters, $capturedParameters );
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

	/**
	 * Both keys used to be read without a fallback, so a carrier whose options were written outside
	 * the admin form - an import, an older plugin version, a WP-CLI fixture - priced correctly but
	 * emitted "Undefined array key". failOnWarning turns that into a failure here.
	 */
	public function testMissingOptionKeysDoNotWarn(): void {
		$this->createRateCalculatorMock();

		$carrier = self::createCarrier(
			[
				OptionsPage::FORM_FIELD_PRICING_TYPE => Options::PRICING_TYPE_BY_PRODUCT_VALUE,
				OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS => [
					[
						'value' => 1000.0,
						'price' => 55.0,
					],
				],
			],
			[
				OptionsPage::FORM_FIELD_WEIGHT_LIMITS,
				'free_shipping_limit',
			]
		);

		$cost = $this->rateCalculator->getShippingRateCost( $carrier, 500.0, 500.0, 1.0, false );

		self::assertSame( 55.0, $cost );
	}
}
