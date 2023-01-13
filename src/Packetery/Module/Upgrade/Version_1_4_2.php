<?php
/**
 * Class Version_1_4_2
 *
 * @package Packetery\Upgrade
 */

declare( strict_types=1 );

namespace Packetery\Module\Upgrade;

use Packetery\Module\Checkout;
use Packetery\Module\Product;

/**
 * Class Version_1_4_2
 *
 * @package Packetery\Upgrade
 */
class Version_1_4_2 {

	/**
	 * WPDB.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Version_1_4_2 constructor.
	 *
	 * @param \wpdb $wpdb WPDB.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}


	/**
	 * Run migration.
	 *
	 * @return void
	 */
	public function run() {
		$this->deduplicateCarrierPrefix();
	}

	/**
	 * In version 1.4.1 have been bug, which stored names of disabled carriers witch duplicated prefix.
	 * This migration will replace carrier name prefix to right format.
	 *
	 * @return void
	 */
	private function deduplicateCarrierPrefix(): void {
		$duplicatedCarrierPrefix = Checkout::CARRIER_PREFIX . Checkout::CARRIER_PREFIX;
		$productIds              = $this->getDisallowedCarriersProductsIds( $duplicatedCarrierPrefix );
		foreach ( $productIds as $productId ) {
			$oldMeta = get_post_meta( $productId, Product\Entity::META_DISALLOWED_SHIPPING_RATES, true );
			if ( ! is_array( $oldMeta ) || empty( $oldMeta ) ) {
				continue;
			}

			$newMeta = [];
			foreach ( $oldMeta as $optionId => $isDisallowed ) {
				$optionId             = str_replace( $duplicatedCarrierPrefix, Checkout::CARRIER_PREFIX, $optionId );
				$newMeta[ $optionId ] = $isDisallowed;
			}

			update_post_meta( $productId, Product\Entity::META_DISALLOWED_SHIPPING_RATES, $newMeta );
		}
	}

	/**
	 * Get IDs of products, which have duplicate carrier prefix caused by bug in version 1.4.1.
	 *
	 * @param string $prefix Duplicated prefix.
	 *
	 * @return int[] Array of post IDs.
	 */
	private function getDisallowedCarriersProductsIds( string $prefix ): array {
		$wpdb       = $this->wpdb;
		$productIds = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT `post_id` 
					   FROM $wpdb->postmeta 
					   WHERE `meta_key` = %s 
					   AND `meta_value` LIKE %s",
				Product\Entity::META_DISALLOWED_SHIPPING_RATES,
				'%' . $prefix . '%'
			)
		);

		return array_map( 'intval', $productIds );
	}


}
