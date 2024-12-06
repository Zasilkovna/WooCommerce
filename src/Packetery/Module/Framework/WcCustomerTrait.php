<?php
/**
 * Trait WcCustomerTrait.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

/**
 * Trait WcCustomerTrait.
 *
 * @package Packetery
 */
trait WcCustomerTrait {
	/**
	 * Gets customer shipping country.
	 *
	 * @return string|null
	 */
	public function customerGetShippingCountry(): ?string {
		return WC()->customer->get_shipping_country();
	}

	/**
	 * Gets customer billing country.
	 *
	 * @return string|null
	 */
	public function customerGetBillingCountry(): ?string {
		return WC()->customer->get_billing_country();
	}
}
