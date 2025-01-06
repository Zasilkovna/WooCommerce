<?php
/**
 * ActivityBridge.
 *
 * @package Packetery
 */

namespace Packetery\Module\Carrier;

use Packetery\Module\Carrier;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Shipping\BaseShippingMethod;
use WC_Shipping_Zones;

/**
 * ActivityBridge.
 *
 * @package Packetery
 */
class ActivityBridge {

	/**
	 * Options provider.
	 *
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * Constructor.
	 *
	 * @param OptionsProvider $optionsProvider Options provider.
	 */
	public function __construct( OptionsProvider $optionsProvider ) {
		$this->optionsProvider = $optionsProvider;
	}

	/**
	 * Gets active carrier ids.
	 *
	 * @return array
	 */
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

	/**
	 * Tells if carrier is active.
	 *
	 * @param string                            $carrierId      Carrier id.
	 * @param \Packetery\Module\Carrier\Options $carrierOptions Carrier options.
	 *
	 * @return bool
	 */
	public function isActive( string $carrierId, Carrier\Options $carrierOptions ): bool {
		if ( $this->optionsProvider->isWcCarrierConfigEnabled() ) {
			return in_array( $carrierId, $this->getActiveCarrierIds(), true );
		}

		return $carrierOptions->isActive();
	}
}
