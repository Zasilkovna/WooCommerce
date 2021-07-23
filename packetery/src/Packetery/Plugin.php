<?php

declare( strict_types=1 );

namespace Packetery;

class Plugin {
	public const DOMAIN = 'packetery';

	public function run() {
		// register hooks
	}

	/**
	 * @param array $methods
	 *
	 * @return array
	 */
	public static function add_shipping_method( array $methods ): array {
		$methods['packetery_shipping_method'] = \WC_Packetery_Shipping_Method::class;

		return $methods;
	}

}
