<?php
/**
 * Class WcAdapter.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WC_Logger;

/**
 * Class WcAdapter.
 *
 * @package Packetery
 */
class WcAdapter {
	use ActionSchedulerTrait;
	use WcCustomerTrait;
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
	 * @return \WC_Product|null
	 */
	public function productFactoryGetProduct( $productId ): ?\WC_Product {
		$product = WC()->product_factory->get_product( $productId );
		if ( $product instanceof \WC_Product ) {
			return $product;
		}

		return null;
	}

	/**
	 * Gets product by post or post id.
	 *
	 * @param mixed $theProduct Post id or object.
	 *
	 * @return false|\WC_Product|null
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
}
