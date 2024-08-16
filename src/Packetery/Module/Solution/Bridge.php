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
	use HookTrait;

	/**
	 * Converts weight from global unit to kg.
	 *
	 * @param int|float $weight Weight.
	 * @param string    $toUnit To unit.
	 *
	 * @return float
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
}
