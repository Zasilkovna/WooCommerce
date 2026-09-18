<?php
/**
 * Class WcAdapter.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Utilities\LoggingUtil;
use stdClass;
use WC_Admin_Status;
use WC_Blocks_Utils;
use WC_Cache_Helper;
use WC_Data_Store;
use WC_Logger;
use WC_Logger_Interface;
use WC_Product;
use WC_Shipping_Rate;
use WC_Shipping_Zone;
use WC_Shipping_Zone_Data_Store_Interface;
use WC_Shipping_Zones;

/**
 * Class WcAdapter.
 *
 * @package Packetery
 */
class WcAdapter {
	use ActionSchedulerTrait;
	use WcCartTrait;
	use WcCustomerTrait;
	use WcPageTrait;
	use WcSessionTrait;
	use WcTaxTrait;

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
	 * @param mixed $productId Product ID.
	 *
	 * @return WC_Product|null
	 */
	public function productFactoryGetProduct( $productId ): ?\WC_Product {
		$product = WC()->product_factory->get_product( $productId );
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

	public function isCheckout(): bool {
		return is_checkout();
	}

	public function featuresUtilDeclareCompatibility( string $featureId, string $pluginFile, bool $positiveCompatibility = true ): void {
		FeaturesUtil::declare_compatibility( $featureId, $pluginFile, $positiveCompatibility );
	}

	public function storeApiRegisterUpdateCallback( array $args ): void {
		woocommerce_store_api_register_update_callback( $args );
	}

	public function shipToBillingAddressOnly(): bool {
		return wc_ship_to_billing_address_only();
	}

	public function shippingGetPackages(): array {
		return WC()->shipping()->get_packages();
	}

	public function addNotice( string $message, string $noticeType ): void {
		wc_add_notice( $message, $noticeType );
	}

	/**
	 * @return WC_Logger|WC_Logger_Interface
	 */
	public function getLogger() {
		return wc_get_logger();
	}

	/**
	 * @return array<string, array{
	 *      name: string,
	 *      countries: array<int, string>
	 *  }>
	 */
	public function countriesGetContinents(): array {
		return WC()->countries->get_continents();
	}

	/**
	 * Tells WooCommerce the shipping configuration changed, so the rates it cached per customer
	 * session are dropped. Core calls it exactly like this when zones or shipping settings are saved.
	 */
	public function refreshShippingCacheVersion(): void {
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}

	/**
	 * Rates WooCommerce ended up with, after the woocommerce_package_rates filter every third party
	 * can remove or reprice rates through - not the ones our shipping method returned. A rate id
	 * repeated across packages keeps the last package, which is what the checkout charges last.
	 *
	 * @return array<string, WC_Shipping_Rate>
	 */
	public function getFinalShippingRates(): array {
		$rates = [];
		foreach ( WC()->shipping()->get_packages() as $package ) {
			foreach ( $package['rates'] ?? [] as $rateId => $rate ) {
				$rates[ (string) $rateId ] = $rate;
			}
		}

		return $rates;
	}

	/**
	 * WooCommerce drops every other rate of the package when the cart qualifies for free shipping and
	 * the shop asks for it (class-wc-shipping.php), which happens before the woocommerce_package_rates
	 * filter and is the only removal we can attribute to anyone.
	 *
	 * It decides per package, while the rates we compare against are merged across all of them, so a
	 * cart split into several packages cannot tell which package the free shipping came from - and
	 * blaming the shop setting for a rate dropped elsewhere would be as wrong as blaming a plugin.
	 */
	public function areOtherRatesHiddenByFreeShipping(): bool {
		if ( get_option( 'woocommerce_shipping_hide_rates_when_free' ) !== 'yes' ) {
			return false;
		}

		$packages = WC()->shipping()->get_packages();
		if ( count( $packages ) !== 1 ) {
			return false;
		}

		foreach ( reset( $packages )['rates'] ?? [] as $rate ) {
			if ( $rate->get_method_id() === 'free_shipping' ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether the cart shows shipping with tax included, which is what the client compares the
	 * diagnostics against. Honours customer tax exemption, unlike the raw woocommerce_tax_display_cart.
	 */
	public function cartDisplayPricesIncludingTax(): bool {
		return WC()->cart->display_prices_including_tax();
	}

	/**
	 * @param float $price Price.
	 *
	 * @return string Formatted price including the shop currency, as HTML.
	 */
	public function price( float $price ): string {
		return wc_price( $price );
	}

	/**
	 * @return array<string, string>
	 */
	public function countriesGetShippingCountries(): array {
		return WC()->countries->get_shipping_countries();
	}

	/**
	 * @param array<string, mixed> $package
	 *
	 * @return WC_Shipping_Zone
	 */
	public function shippingZonesGetZoneMatchingPackage( array $package ): WC_Shipping_Zone {
		return WC_Shipping_Zones::get_zone_matching_package( $package );
	}

	/**
	 * Methods the zone has in the database, including those whose class is not registered - a carrier
	 * turned off in Packeta settings never registers its class, yet its row in the zone stays, and
	 * that row is the only record of the client having wanted the carrier in the checkout.
	 *
	 * @return array<string, bool> Shipping method id => enabled in the zone.
	 */
	public function shippingZoneGetMethodStates( WC_Shipping_Zone $zone ): array {
		/** @var WC_Shipping_Zone_Data_Store_Interface $dataStore */
		$dataStore = $zone->get_data_store();

		$states = [];
		foreach ( $dataStore->get_methods( $zone->get_id(), false ) as $method ) {
			$row       = (array) $method;
			$methodId  = (string) $row['method_id'];
			$isEnabled = (bool) (int) $row['is_enabled'];

			$states[ $methodId ] = ( $states[ $methodId ] ?? false ) || $isEnabled;
		}

		return $states;
	}

	public function hasBlockInPage( int $page, string $blockName ): bool {
		return WC_Blocks_Utils::has_block_in_page( $page, $blockName );
	}

	/**
	 * @param string $objectType
	 *
	 * @return WC_Data_Store|WC_Shipping_Zone_Data_Store_Interface
	 */
	public function dataStoreLoad( string $objectType ): object {
		return WC_Data_Store::load( $objectType );
	}

	/**
	 * Returns WC_Abstract_Order[]
	 *
	 * @param array<string, mixed> $args
	 *
	 * @return array<mixed, mixed>
	 */
	public function getOrdersWithoutPagination( array $args ): array {
		$results = wc_get_orders( $args );

		if ( is_array( $results ) ) {
			return $results;
		}

		if ( $results instanceof stdClass && isset( $results->orders ) && is_array( $results->orders ) ) {
			return $results->orders;
		}

		return [];
	}

	public function adminStatusStatusReport(): void {
		WC_Admin_Status::status_report();
	}

	public function loggingUtilGetLogDirectory( bool $createDir = true ): ?string {
		if ( class_exists( LoggingUtil::class ) === false ) {
			return null;
		}

		return LoggingUtil::get_log_directory( $createDir );
	}
}
