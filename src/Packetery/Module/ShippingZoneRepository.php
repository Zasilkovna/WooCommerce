<?php
/**
 * Shipping zone repository.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Module\Framework\WcAdapter;
use stdClass;
use WC_Data_Store;
use WC_Shipping_Zone;
use WC_Shipping_Zone_Data_Store_Interface;

/**
 * Shipping zone repository.
 */
class ShippingZoneRepository {

	/**
	 * @var WC_Shipping_Zone_Data_Store_Interface|WC_Data_Store|null
	 */
	private $dataStore = null;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	public function __construct( WcAdapter $wcAdapter ) {
		$this->wcAdapter = $wcAdapter;
	}

	/**
	 * Lazy data store getter.
	 */
	public function getDataStore(): WC_Shipping_Zone_Data_Store_Interface {
		if ( $this->dataStore === null ) {
			$this->dataStore = WC_Data_Store::load( 'shipping-zone' );
		}

		return $this->dataStore;
	}

	/**
	 * Gets all zones.
	 *
	 * @return WC_Shipping_Zone[]
	 */
	private function getAllShippingZones(): array {
		$rawZones = $this->getDataStore()->get_zones();
		$zones    = [];
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
	 * @return stdClass[]|null
	 */
	private function getLocationsForShippingRate( string $methodRateId ): ?array {
		foreach ( $this->getAllShippingZones() as $zone ) {
			$enabledOnly = true;
			// Can't use get_shipping_methods because of infinite recursion.
			$rawMethods = $this->getDataStore()->get_methods( $zone->get_id(), $enabledOnly );

			/**
			 * Raw method.
			 *
			 * @var stdClass $rawMethod
			 */
			foreach ( $rawMethods as $rawMethod ) {
				// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
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
	 * @return string[]
	 */
	public function getCountryCodesForShippingRate( string $rateId ): array {
		return $this->getCountryCodesFromZoneLocations( $this->getLocationsForShippingRate( $rateId ) );
	}

	private function getCountryCodesFromZoneLocations( ?array $zoneLocations ): array {
		if ( $zoneLocations === null ) {
			return [];
		}

		$countries  = [];
		$continents = $this->wcAdapter->countriesGetContinents();

		/**
		 * @var stdClass $zoneLocation
		 */
		foreach ( $zoneLocations as $zoneLocation ) {
			if ( $zoneLocation->type === 'country' ) {
				$countries[] = strtolower( $zoneLocation->code );
			}

			if ( $zoneLocation->type === 'continent' && isset( $continents[ $zoneLocation->code ]['countries'] ) ) {
				foreach ( $continents[ $zoneLocation->code ]['countries'] as $countryCode ) {
					$countries[] = strtolower( $countryCode );
				}
			}
		}

		return $countries;
	}

	public function getCountryCodesForShippingZone( int $zoneId ): array {
		$zone = new WC_Shipping_Zone( $zoneId );

		return $this->getCountryCodesFromZoneLocations( $zone->get_zone_locations() );
	}
}
