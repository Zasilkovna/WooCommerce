<?php

namespace Packetery\Module\Carrier;

use Packetery\Module\Carrier;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Shipping\BaseShippingMethod;
use WC_Shipping_Zones;

class CarrierActivityBridge {

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	public function __construct( OptionsProvider $optionsProvider ) {
		$this->optionsProvider = $optionsProvider;
	}

	private function getActiveCarrierIds(): array {
		static $activeMethods;

		if ( isset( $activeMethods ) ) {
			return $activeMethods;
		}

		$activeMethods = [];
		$shippingZones = WC_Shipping_Zones::get_zones();

		foreach ( $shippingZones as $shippingZone ) {
			$shippingMethods = $shippingZone['shipping_methods'];

			foreach ( $shippingMethods as $shippingMethod ) {
				if ( $shippingMethod instanceof BaseShippingMethod && $shippingMethod->enabled === 'yes' ) {
					$activeMethods[] = $shippingMethod::CARRIER_ID;
				}
			}
		}

		return $activeMethods;
	}

	public function isActive( string $carrierId, Carrier\Options $carrierOptions ): bool {
		if ( $this->optionsProvider->isWcCarrierConfigEnabled() ) {
			return in_array( $carrierId, $this->getActiveCarrierIds(), true );
		}

		return $carrierOptions->isActive();
	}
}
