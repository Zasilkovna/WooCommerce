<?php
/**
 * WordPress and WooCommerce library wrapper.
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );


namespace Packetery\Module;

use PacketeryNette\StaticClass;

/**
 * WordPress and WooCommerce library wrapper.
 *
 * @package Packetery\Module
 */
class Adapter {

	use StaticClass;

	/**
	 * Wraps get_post_meta() function. Always returns array of all unique items.
	 *
	 * @param int $postId Post ID.
	 */
	public static function getAllUniquePostMeta( int $postId ): array {
		$postMeta = get_post_meta( $postId );

		$meta = [];
		foreach ( $postMeta as $key => $item ) {
			$meta[ $key ] = array_shift( $item ); // WordPress vrací strukturu řešící situaci, kdy existuje několik hodnot pro jeden klíč.
		}

		return $meta;
	}
}
