<?php
/**
 * Shipping zone repository.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use stdClass;
use WC_Data_Store;
use WC_Shipping_Zone;
use WC_Shipping_Zone_Data_Store_Interface;

/**
 * Shipping zone repository.
 */
class ShippingZoneRepository {

	/**
	 * Shipping zone data store.
	 *
	 * @var WC_Shipping_Zone_Data_Store_Interface
	 */
	private $dataStore;

	/**
	 * ShippingZoneRepository constructor.
	 */
	public function __construct() {
		$this->dataStore = WC_Data_Store::load( 'shipping-zone' );
	}

	/**
	 * Gets all zones.
	 *
	 * @return array
	 */
	private function getAllShippingZones(): array {
		$rawZones = $this->dataStore->get_zones();
		foreach ( $rawZones as $rawZone ) {
			$zones[] = new WC_Shipping_Zone( $rawZone );
		}
		// Add zone '0' manually.
		$zones[] = new WC_Shipping_Zone( 0 );

		return $zones;
	}

	/**
	 * Gets locations for shipping rate.
	 *
	 * @param string $methodRateId Method rate id.
	 *
	 * @return array|null
	 */
	private function getLocationsForShippingRate( string $methodRateId ): ?array {
		/**
		 * Zone.
		 *
		 * @var WC_Shipping_Zone $zone
		 */
		foreach ( $this->getAllShippingZones() as $zone ) {
			$enabledOnly = true;
			// Can't use get_shipping_methods because of infinite recursion.
			$rawMethods = $this->dataStore->get_methods( $zone->get_id(), $enabledOnly );

			/**
			 * Raw method.
			 *
			 * @var stdClass $rawMethod
			 */
			foreach ( $rawMethods as $rawMethod ) {
				$rawMethodId = sprintf( '%s:%s', $rawMethod->method_id, $rawMethod->instance_id );
				if ( $methodRateId === $rawMethodId ) {
					return $zone->get_zone_locations();
				}
			}
		}

		return null;
	}

	/**
	 * Gets country codes for shipping rate.
	 *
	 * @param string $rateId Rate id.
	 *
	 * @return array
	 */
	public function getCountryCodesForShippingRate( string $rateId ): array {
		$countries     = [];
		$zoneLocations = $this->getLocationsForShippingRate( $rateId );
		if ( ! empty( $zoneLocations ) ) {
			/**
			 * Zone location.
			 *
			 * @var stdClass $zoneLocation
			 */
			foreach ( $zoneLocations as $zoneLocation ) {
				if ( 'country' === $zoneLocation->type ) {
					$countries[] = strtolower( $zoneLocation->code );
				}
			}
		}

		return $countries;
	}

}
