<?php
/**
 * Main Packeta plugin class.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery;

/**
 * Class Plugin
 *
 * @package Packetery
 */
class Plugin {
	public const DOMAIN = 'packetery';

	/**
	 * Method to register hooks
	 */
	public function run() {
		// TODO: register hooks.
	}

	/**
	 * Adds Packeta method to available shipping methods.
	 *
	 * @param array $methods Previous state.
	 *
	 * @return array
	 */
	public static function add_shipping_method( array $methods ): array {
		$methods['packetery_shipping_method'] = \WC_Packetery_Shipping_Method::class;

		return $methods;
	}

}
