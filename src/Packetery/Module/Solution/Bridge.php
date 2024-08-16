<?php
/**
 * Class Bridge.
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module\Solution;

/**
 * Class Bridge.
 *
 * @package Packetery
 */
class Bridge {
	use WcCustomerTrait;
	use WcCartTrait;
	use WcTaxTrait;

	/**
	 * Applies filters.
	 *
	 * @param string $hookName Hook name.
	 * @param mixed  $value Value.
	 * @param mixed  ...$args Arguments.
	 *
	 * @return mixed
	 */
	public function applyFilters( string $hookName, $value, ...$args ) {
		/**
		 * Bridged function call.
		 *
		 * @since 1.7.7
		 */
		return apply_filters( $hookName, $value, ...$args );
	}

	/**
	 * Converts weight from global unit to kg.
	 *
	 * @param int|float $weight Weight.
	 * @param string    $toUnit To unit.
	 *
	 * @return string
	 */
	public function getWcGetWeight( $weight, $toUnit ) {
		return wc_get_weight( $weight, $toUnit );
	}

	/**
	 * Gets WC product.
	 *
	 * @param mixed $product_id Product ID.
	 *
	 * @return bool|\WC_Product
	 */
	public function getProduct( $product_id ) {
		return WC()->product_factory->get_product( $product_id );
	}

	/**
	 * Tells if action was performed.
	 *
	 * @param string $hookName Name of hook.
	 *
	 * @return int
	 */
	public function didAction( $hookName ) {
		return did_action( $hookName );
	}
}
