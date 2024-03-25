<?php

namespace Packetery\Module\CarrierShippingMethod;

final class CarrierZpointcz extends SingleCarrierShippingMethod {

	/**
	 * @var string
	 */
	public const CARRIER_ID = 'zpointcz';

	public function is_available( $package ): bool {
		return ( $package['destination']['country'] === 'CZ' );
	}

}
