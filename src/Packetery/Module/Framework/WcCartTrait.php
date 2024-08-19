<?php
/**
 * Trait WcCartTrait.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

/**
 * Trait WcCartTrait.
 *
 * @package Packetery
 */
trait WcCartTrait {

	/**
	 * Gets cart contents.
	 *
	 * @return array of cart items
	 */
	public function getCartContents(): array {
		return WC()->cart->get_cart_contents();
	}

	/**
	 * Gets cart total. This is the total of items in the cart, but after discounts. Subtotal is before discounts.
	 *
	 * @return float
	 */
	public function getCartContentsTotal(): float {
		return WC()->cart->get_cart_contents_total();
	}

	/**
	 * Gets cart tax amount.
	 *
	 * @return float
	 */
	public function getCartContentsTax(): float {
		return WC()->cart->get_cart_contents_tax();
	}

	/**
	 * Get weight of items in the cart.
	 *
	 * @return float
	 */
	public function getCartContentsWeight(): float {
		return WC()->cart->get_cart_contents_weight();
	}

	/**
	 * Returns the contents of the cart in an array.
	 *
	 * @return array contents of the cart
	 */
	public function getCartContent(): array {
		return WC()->cart->get_cart();
	}

	/**
	 * Gets cart instance.
	 *
	 * @return \WC_Cart|null
	 */
	public function getCart(): ?\WC_Cart {
		return WC()->cart;
	}

}
