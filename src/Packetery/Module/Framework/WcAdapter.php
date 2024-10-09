<?php
/**
 * Class WcAdapter.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

use WC_Logger;

/**
 * Class WcAdapter.
 *
 * @package Packetery
 */
class WcAdapter {
	use WcCustomerTrait;
	use WcCartTrait;
	use WcTaxTrait;
	use WcPageTrait;

	/**
	 * Converts weight from global unit to kg.
	 *
	 * @param int|float $weight Weight.
	 * @param string    $toUnit To unit.
	 *
	 * @return float
	 */
	public function getWeight( $weight, string $toUnit ): float {
		return (float) wc_get_weight( $weight, $toUnit );
	}

	/**
	 * Gets WC product by id.
	 *
	 * @param mixed $product_id Product ID.
	 *
	 * @return \WC_Product|null
	 */
	public function productFactoryGetProduct( $product_id ) {
		$product = WC()->product_factory->get_product( $product_id );
		if ( $product instanceof \WC_Product ) {
			return $product;
		}

		return null;
	}

	/**
	 * Gets product by post id.
	 *
	 * @param int $postId Post id.
	 *
	 * @return false|\WC_Product|null
	 */
	public function getProduct( int $postId ) {
		return wc_get_product( $postId );
	}

	/**
	 * Creates new logger instance.
	 *
	 * @return WC_Logger
	 */
	public function createLogger(): WC_Logger {
		return new WC_Logger();
	}

}
