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
	 * @return string
	 */
	public function getCustomerShippingCountry() {
		return WC()->customer->get_shipping_country();
	}

	/**
	 * Gets customer billing country.
	 *
	 * @return string
	 */
	public function getCustomerBillingCountry() {
		return WC()->customer->get_billing_country();
	}

	/**
	 * Gets cart contents.
	 *
	 * @return array of cart items
	 */
	public function getCartContents() {
		return WC()->cart->get_cart_contents();
	}

	/**
	 * Gets cart total. This is the total of items in the cart, but after discounts. Subtotal is before discounts.
	 *
	 * @return float
	 */
	public function getCartContentsTotal() {
		return WC()->cart->get_cart_contents_total();
	}

	/**
	 * Gets cart tax amount.
	 *
	 * @return float
	 */
	public function getCartContentsTax() {
		return WC()->cart->get_cart_contents_tax();
	}

	/**
	 * Get weight of items in the cart.
	 *
	 * @return float
	 */
	public function getCartContentsWeight() {
		return WC()->cart->get_cart_contents_weight();
	}

	/**
	 * Converts weight from global unit to kg.
	 *
	 * @param int|float $weight Weight.
	 * @param string $toUnit To unit.
	 *
	 * @return string
	 */
	public function getWcGetWeight( $weight, $toUnit ) {
		return wc_get_weight( $weight, $toUnit );
	}

	/**
	 * Returns the contents of the cart in an array.
	 *
	 * @return array contents of the cart
	 */
	public function getCartContent() {
		return WC()->cart->get_cart();
	}

	/**
	 * Gets cart instance.
	 *
	 * @return \WC_Cart
	 */
	public function getCart() {
		return WC()->cart;
	}

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
