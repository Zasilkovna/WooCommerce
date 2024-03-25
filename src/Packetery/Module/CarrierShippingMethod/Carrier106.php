<?php

namespace Packetery\Module\CarrierShippingMethod;

final class Carrier106 extends SingleCarrierShippingMethod {

	/**
	 * @var string
	 */
	public const CARRIER_ID = '106';

	public function is_available( $package ): bool {
		return ( $package['destination']['country'] === 'CZ' );
	}

}
