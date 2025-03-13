<?php

declare( strict_types=1 );

namespace Packetery\Module\Dashboard;

use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Shipping\ShippingProvider;
use WC_Shipping_Zone;
use WC_Shipping_Zone_Data_Store;

class DashboardHelper {

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	public function __construct(
		WcAdapter $wcAdapter
	) {
		$this->wcAdapter = $wcAdapter;
	}

	/**
	 * Tells if there is Packeta shipping method configured and active.
	 *
	 * @return bool
	 */
	public function isPacketaShippingMethodActive(): bool {
		/** @var WC_Shipping_Zone_Data_Store $shippingDataStore */
		$shippingDataStore = $this->wcAdapter->dataStoreLoad( 'shipping-zone' );

		$shippingZones = $shippingDataStore->get_zones();

		foreach ( $shippingZones as $shippingZoneId ) {
			$shippingZone        = new WC_Shipping_Zone( $shippingZoneId );
			$shippingZoneMethods = $shippingZone->get_shipping_methods( true );
			foreach ( $shippingZoneMethods as $method ) {
				if ( ShippingProvider::isPacketaMethod( $method->id ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
