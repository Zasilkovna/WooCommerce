<?php
/**
 * Class WcAdapter.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

use WC_Logger;
use WC_Product;
use WC_Shipping_Zone;
use WC_Shipping_Zones;

/**
 * Class WcAdapter.
 *
 * @package Packetery
 */
class WcAdapter {
	use ActionSchedulerTrait;
	use WcCustomerTrait;
	use WcCartTrait;
	use WcTaxTrait;
	use WcPageTrait;

	/**
	 * Converts weight from global unit to kg.
	 *
	 * @param int|float $weight Weight.
	 * @param string    $toUnit To unit.
	 *
	 * @return float
	 */
	public function getWeight( $weight, string $toUnit ): float {
		return (float) wc_get_weight( $weight, $toUnit );
	}

	/**
	 * Gets WC product by id.
	 *
	 * @param mixed $product_id Product ID.
	 *
	 * @return WC_Product|null
	 */
	public function productFactoryGetProduct( $product_id ): ?WC_Product {
		$product = WC()->product_factory->get_product( $product_id );
		if ( $product instanceof WC_Product ) {
			return $product;
		}

		return null;
	}

	/**
	 * Gets product by post or post id.
	 *
	 * @param mixed $theProduct Post id or object.
	 *
	 * @return false|WC_Product|null
	 */
	public function getProduct( $theProduct ) {
		return wc_get_product( $theProduct );
	}

	/**
	 * Creates new logger instance.
	 *
	 * @return WC_Logger
	 */
	public function createLogger(): WC_Logger {
		return new WC_Logger();
	}

	/**
	 * Gets continent list.
	 *
	 * @return array
	 */
	public function countriesGetContinents(): array {
		return WC()->countries->get_continents();
	}

	/**
	 * Gets shipping zone matching package.
	 *
	 * @param array $package Package.
	 *
	 * @return WC_Shipping_Zone
	 */
	public function shippingZonesGetZoneMatchingPackage( array $package ): WC_Shipping_Zone {
		return WC_Shipping_Zones::get_zone_matching_package( $package );
	}

}
