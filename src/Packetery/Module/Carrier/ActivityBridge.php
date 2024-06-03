<?php
/**
 * ActivityBridge.
 *
 * @package Packetery
 */

namespace Packetery\Module\Carrier;

use Packetery\Module\Carrier;
use Packetery\Module\Options;
use Packetery\Module\ShippingMethod;
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
	 * @var Options\Provider
	 */
	private $optionsProvider;

	/**
	 * Constructor.
	 *
	 * @param Options\Provider $optionsProvider Options provider.
	 */
	public function __construct( Options\Provider $optionsProvider ) {
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
				if (
					$shippingMethod instanceof ShippingMethod &&
					'yes' === $shippingMethod->enabled &&
					! empty( $shippingMethod->get_option( 'carrier_id' ) )
				) {
					$activeMethods[] = $shippingMethod->get_option( 'carrier_id' );
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
