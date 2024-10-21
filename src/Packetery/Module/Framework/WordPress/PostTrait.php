<?php
/**
 * Trait PostTrait.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

use WP_Post;
/**
 * Trait PostTrait.
 *
 * @package Packetery
 */
trait PostTrait {

	/**
	 * Retrieves data from a post field based on Post ID.
	 *
	 * @param string      $field Post field name.
	 * @param int|WP_Post $post Optional. Post ID or post object. Defaults to global $post.
	 *
	 * @return string
	 */
	public function getPostField( string $field, $post ): string {
		return get_post_field( $field, $post );
	}

}
