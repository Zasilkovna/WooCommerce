<?php
/**
 * Class WcAdapter.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

/**
 * Class WcAdapter.
 *
 * @package Packetery
 */
class WcAdapter {
	use WcCustomerTrait;
	use WcCartTrait;
	use WcTaxTrait;

	/**
	 * Converts weight from global unit to kg.
	 *
	 * @param int|float $weight Weight.
	 * @param string    $toUnit To unit.
	 *
	 * @return float
	 */
	public function getWeight( $weight, string $toUnit ): float {
		return wc_get_weight( $weight, $toUnit );
	}

	/**
	 * Gets WC product.
	 *
	 * @param mixed $product_id Product ID.
	 *
	 * @return false|\WC_Product
	 */
	public function productFactoryGetProduct( $product_id ) {
		return WC()->product_factory->get_product( $product_id );
	}

}
