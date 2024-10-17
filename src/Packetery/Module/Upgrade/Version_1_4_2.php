<?php
/**
 * Class Version_1_4_2
 *
 * @package Packetery\Upgrade
 */

declare( strict_types=1 );

namespace Packetery\Module\Upgrade;

use Packetery\Module\Carrier\OptionPrefixer;
use Packetery\Module\Product;
use Packetery\Module\WpdbAdapter;

/**
 * Class Version_1_4_2
 *
 * @package Packetery\Upgrade
 */
class Version_1_4_2 {

	/**
	 * WPDB.
	 *
	 * @var WpdbAdapter
	 */
	private $wpdbAdapter;

	/**
	 * Version_1_4_2 constructor.
	 *
	 * @param WpdbAdapter $wpdbAdapter WPDB.
	 */
	public function __construct( WpdbAdapter $wpdbAdapter ) {
		$this->wpdbAdapter = $wpdbAdapter;
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
	 * In version 1.4.1 has been bug, which stored names of disabled carriers witch duplicated prefix.
	 * This migration will replace carrier name prefix with correct format.
	 *
	 * @return void
	 */
	private function deduplicateCarrierPrefix(): void {
		$duplicatedCarrierPrefix = OptionPrefixer::CARRIER_OPTION_PREFIX . OptionPrefixer::CARRIER_OPTION_PREFIX;
		$productIds              = $this->getDisallowedCarriersProductsIds( $duplicatedCarrierPrefix );
		foreach ( $productIds as $productId ) {
			$oldMeta = get_post_meta( $productId, Product\Entity::META_DISALLOWED_SHIPPING_RATES, true );
			if ( ! is_array( $oldMeta ) || empty( $oldMeta ) ) {
				continue;
			}

			$newMeta = [];
			foreach ( $oldMeta as $optionId => $isDisallowed ) {
				$optionId             = str_replace( $duplicatedCarrierPrefix, OptionPrefixer::CARRIER_OPTION_PREFIX, $optionId );
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
		$productIds = $this->wpdbAdapter->get_col(
			$this->wpdbAdapter->prepare(
				'SELECT `post_id` 
					FROM `' . $this->wpdbAdapter->postmeta . '` 
					WHERE `meta_key` = %s 
					AND `meta_value` LIKE %s',
				Product\Entity::META_DISALLOWED_SHIPPING_RATES,
				'%' . $prefix . '%'
			)
		);

		return array_map( 'intval', $productIds );
	}

}
