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
 * Trait BlocksTrait.
 *
 * @package Packetery
 */
trait BlocksTrait {

	/**
	 * Determines whether a $post or a string contains a specific block type.
	 *
	 * @param string                  $blockName Full block type to look for.
	 * @param int|string|WP_Post|null $post Optional. Post content, post ID, or post object.
	 *
	 * @return bool
	 */
	public function hasBlock( string $blockName, $post ): bool {
		return has_block( $blockName, $post );
	}

}
