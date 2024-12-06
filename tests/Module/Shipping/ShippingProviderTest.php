<?php

declare( strict_types=1 );

namespace Tests\Module\Shipping;

use Packetery\Module\Shipping\BaseShippingMethod;
use Packetery\Module\Shipping\ShippingProvider;
use Packetery\Module\ShippingMethod;
use PHPUnit\Framework\TestCase;

class ShippingProviderTest extends TestCase {
	/**
	 * @return array
	 */
	public static function shippingMethodsProvider(): array {
		return [
			[
				'methodId'        => 'free_shipping',
				'isPacketaMethod' => false,
			],
			[
				'methodId'        => 'flat_rate',
				'isPacketaMethod' => false,
			],
			[
				'methodId'        => ShippingMethod::PACKETERY_METHOD_ID,
				'isPacketaMethod' => true,
			],
			[
				'methodId'        => BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpointcz',
				'isPacketaMethod' => true,
			],
			[
				'methodId'        => BaseShippingMethod::PACKETA_METHOD_PREFIX . 'czzpoint',
				'isPacketaMethod' => true,
			],
			[
				'methodId'        => BaseShippingMethod::PACKETA_METHOD_PREFIX . '106',
				'isPacketaMethod' => true,
			],
		];
	}

	/**
	 * @dataProvider shippingMethodsProvider
	 */
	public function testIsPacketaMethod( $methodId, $isPacketaMethod ): void {
		self::assertEquals( $isPacketaMethod, ShippingProvider::isPacketaMethod( $methodId ) );
	}
}
