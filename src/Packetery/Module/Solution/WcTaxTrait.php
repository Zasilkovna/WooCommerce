<?php
/**
 * Trait WcTaxTrait.
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module\Solution;

use WC_Tax;

/**
 * Trait WcTaxTrait.
 *
 * @package Packetery
 */
trait WcTaxTrait {

	/**
	 * Gets an array of matching shipping tax rates for a given class.
	 *
	 * @return mixed
	 */
	public function getShippingTaxRates() {
		return WC_Tax::get_shipping_tax_rates();
	}

	/**
	 * Calculates inclusive tax.
	 *
	 * @param float $cost Cost.
	 * @param array $rates Rates.
	 *
	 * @return array
	 */
	public function calcInclusiveTax( float $cost, $rates ) {
		return WC_Tax::calc_inclusive_tax( $cost, $rates );
	}
}
