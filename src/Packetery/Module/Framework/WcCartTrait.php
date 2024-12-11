<?php
/**
 * Trait WcCartTrait.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

use stdClass;
use WC_Cart;
use WC_Customer;
use WP_Error;

/**
 * Trait WcCartTrait.
 *
 * @package Packetery
 */
trait WcCartTrait {
	/**
	 * Gets cart contents.
	 *
	 * @return array<string|int, mixed> of cart items
	 */
	public function cartGetCartContents(): array {
		return WC()->cart->get_cart_contents();
	}

	/**
	 * Gets cart total. This is the total of items in the cart, but after discounts. Subtotal is before discounts.
	 *
	 * @return float
	 */
	public function cartGetCartContentsTotal(): float {
		return (float) WC()->cart->get_cart_contents_total();
	}

	/**
	 * Gets cart tax amount.
	 *
	 * @return float
	 */
	public function cartGetCartContentsTax(): float {
		return (float) WC()->cart->get_cart_contents_tax();
	}

	/**
	 * Get weight of items in the cart.
	 *
	 * @return float
	 */
	public function cartGetCartContentsWeight(): float {
		return WC()->cart->get_cart_contents_weight();
	}

	/**
	 * Returns the contents of the cart in an array.
	 *
	 * @return array contents of the cart
	 */
	public function cartGetCartContent(): array {
		return WC()->cart->get_cart();
	}

	/**
	 * Gets cart instance.
	 *
	 * @return WC_Cart|null
	 */
	public function cart(): ?WC_Cart {
		return WC()->cart;
	}

	public function cartCalculateTotals(): void {
		WC()->cart->calculate_totals();
	}

	/**
	 * Value is cast to float because PHPDoc is not reliable.
	 */
	public function cartGetSubtotal(): float {
		return (float) WC()->cart->get_subtotal();
	}

	public function cartGetCustomer(): WC_Customer {
		return WC()->cart->get_customer();
	}

	public function cartCalculateShipping(): array {
		return WC()->cart->calculate_shipping();
	}

	/**
	 * @param array $args
	 *
	 * @return stdClass|WP_Error Either a fee object if added, or a WP_Error if it failed.
	 */
	public function cartFeesApiAddFee( array $args ): object {
		return WC()->cart->fees_api()->add_fee( $args );
	}
}
