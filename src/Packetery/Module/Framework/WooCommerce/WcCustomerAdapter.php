<?php
/**
 * Trait WcCustomerAdapter.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

/**
 * Trait WcCustomerAdapter.
 *
 * @package Packetery
 */
class WcCustomerAdapter {

	/**
	 * Gets customer shipping country.
	 *
	 * @return string
	 */
	public function customerGetShippingCountry(): string {
		return WC()->customer->get_shipping_country();
	}

	/**
	 * Gets customer billing country.
	 *
	 * @return string
	 */
	public function customerGetBillingCountry(): string {
		return WC()->customer->get_billing_country();
	}

}
