<?php

declare( strict_types=1 );

namespace Packetery\Module\Framework;

use WC_Customer;

trait WcCustomerTrait {
	public function customerGetShippingCountry(): ?string {
		$customer = WC()->customer;
		if ( ! $customer instanceof WC_Customer ) {
			return null;
		}

		return $customer->get_shipping_country();
	}

	public function customerGetBillingCountry(): ?string {
		$customer = WC()->customer;
		if ( ! $customer instanceof WC_Customer ) {
			return null;
		}

		return $customer->get_billing_country();
	}
}
