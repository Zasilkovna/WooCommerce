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
