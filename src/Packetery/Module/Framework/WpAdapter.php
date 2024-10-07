<?php
/**
 * Class WpAdapter.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

use WP_Error;
use WP_Post;
use WP_Term;

/**
 * Class WpAdapter.
 *
 * @package Packetery
 */
class WpAdapter {
	use HookTrait;
	use HttpTrait;
	use OptionTrait;
	use TransientTrait;
	use PostTrait;

	/**
	 * Retrieves a modified URL query string.
	 *
	 * @param mixed ...$args Arguments.
	 *
	 * @return string New URL query string (unescaped).
	 */
	public function addQueryArg( ...$args ): string {
		return add_query_arg( ...$args );
	}

	/**
	 * Gets WP term.
	 *
	 * @param int $termId Term id.
	 *
	 * @return WP_Term|WP_Error|null
	 */
	public function getTerm( int $termId ) {
		return get_term( $termId );
	}

	/**
	 * Checks whether the given variable is an instance of the `WP_Error` class.
	 *
	 * @param mixed $thing Variable to check.
	 *
	 * @return bool
	 */
	public function isWpError( $thing ): bool {
		return is_wp_error( $thing );
	}

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
