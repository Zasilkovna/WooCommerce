<?php
/**
 * Class Bridge.
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module;

use WC_Tax;

/**
 * Class Bridge.
 *
 * @package Packetery
 */
class Bridge {
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
	 * Gets customer shipping country.
	 *
	 * @return mixed
	 */
	public function getCustomerShippingCountry() {
		return WC()->customer->get_shipping_country();
	}

	/**
	 * Gets customer billing country.
	 *
	 * @return mixed
	 */
	public function getCustomerBillingCountry() {
		return WC()->customer->get_billing_country();
	}

	/**
	 * Gets cart contents.
	 *
	 * @return mixed
	 */
	public function getCartContents() {
		return WC()->cart->get_cart_contents();
	}

	/**
	 * Gets cart contents total.
	 *
	 * @return mixed
	 */
	public function getCartContentsTotal() {
		return WC()->cart->get_cart_contents_total();
	}

	/**
	 * Gets cart contents tax.
	 *
	 * @return mixed
	 */
	public function getCartContentsTax() {
		return WC()->cart->get_cart_contents_tax();
	}

	/**
	 * Gets cart contents weight.
	 *
	 * @return mixed
	 */
	public function getCartContentsWeight() {
		return WC()->cart->cart_contents_weight;
	}

	/**
	 * Converts weight from global unit to kg.
	 *
	 * @param mixed $weight Weight.
	 *
	 * @return mixed
	 */
	public function getWcGetWeight( $weight ) {
		return wc_get_weight( $weight, 'kg' );
	}

	/**
	 * Gets cart content.
	 *
	 * @return mixed
	 */
	public function getCartContent() {
		return WC()->cart->get_cart();
	}

	/**
	 * Gets cart.
	 *
	 * @return mixed
	 */
	public function getCart() {
		return WC()->cart;
	}

	/**
	 * Gets shipping tax rates.
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
	 * @param mixed $rates Rates.
	 *
	 * @return mixed
	 */
	public function calcInclusiveTax( float $cost, $rates ) {
		return WC_Tax::calc_inclusive_tax( $cost, $rates );
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
