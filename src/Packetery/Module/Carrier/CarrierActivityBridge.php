<?php

namespace Packetery\Module\Carrier;

use Packetery\Core\Entity;
use Packetery\Module\Carrier;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Shipping\BaseShippingMethod;
use WC_Shipping_Zone;
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

		$activeMethods       = [];
		$shippingZones       = WC_Shipping_Zones::get_zones();
		$defaultShippingZone = WC_Shipping_Zones::get_zone_by();
		if ( $defaultShippingZone instanceof WC_Shipping_Zone ) {
			$shippingZones[] = [
				'shipping_methods' => $defaultShippingZone->get_shipping_methods(),
			];
		}

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

	public function isActive( Entity\Carrier $carrier, Carrier\Options $carrierOptions ): bool {
		if ( $this->optionsProvider->isWcCarrierConfigEnabled() ) {
			return $carrier->isAvailable() && in_array( $carrier->getId(), $this->getActiveCarrierIds(), true );
		}

		return $carrier->isAvailable() && $carrierOptions->isActive();
	}
}
