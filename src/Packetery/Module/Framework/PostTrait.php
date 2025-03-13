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

	/**
	 * @return int|false
	 */
	public function getTheId() {
		return get_the_ID();
	}

	/**
	 * @param int|WP_Post $post
	 * @param string      $context
	 *
	 * @return string|null
	 */
	public function getEditPostLink( $post, string $context ): ?string {
		return get_edit_post_link( $post, $context );
	}

	public function resetPostdata(): void {
		wp_reset_postdata();
	}
}
