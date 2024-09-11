<?php

declare( strict_types=1 );

namespace Tests\Module;

use Packetery\Module\Carrier\Options;

class CarrierOptionsDummyFactory {

	private static function getDefaultOptionsArray(): array {
		return [
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
	}

	public static function getDefaultCarrier(): Options {
		return new Options( 'any', self::getDefaultOptionsArray() );
	}

	public static function getNoCouponCarrier(): Options {
		return new Options(
			'any',
			array_merge(
				self::getDefaultOptionsArray(),
				[
					'coupon_free_shipping' => [
						'active' => false,
					],
				]
			)
		);
	}

	public static function getProductValuePricingCarrier(): Options {
		return new Options(
			'any',
			array_merge(
				self::getDefaultOptionsArray(),
				[
					'pricing_type' => Options::PRICING_TYPE_BY_PRODUCT_VALUE,
				]
			)
		);
	}

	public static function getNoFreeShippingLimitCarrier(): Options {
		return new Options(
			'any',
			array_merge(
				self::getDefaultOptionsArray(),
				[
					'free_shipping_limit' => null,
				]
			)
		);
	}

}
