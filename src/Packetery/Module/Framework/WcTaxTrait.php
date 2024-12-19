<?php
/**
 * Trait WcTaxTrait.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

use WC_Tax;

/**
 * Trait WcTaxTrait.
 *
 * @package Packetery
 */
trait WcTaxTrait {
	public function taxGetRates( string $taxClass, ?object $customer = null ): array {
		return WC_Tax::get_rates( $taxClass, $customer );
	}

	/**
	 * @return array<int, array<string, string|float>>|array{}
	 */
	public function taxGetShippingTaxRates(): array {
		return (array) WC_Tax::get_shipping_tax_rates();
	}

	public function calcTax( float $price, array $rates, bool $priceIncludesTax ): array {
		return WC_Tax::calc_tax( $price, $rates, $priceIncludesTax );
	}

	public function taxCalcInclusiveTax( float $cost, array $rates ): array {
		return WC_Tax::calc_inclusive_tax( $cost, $rates );
	}
}
