<?php
/**
 * Trait HookTrait.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

/**
 * Trait HookTrait.
 *
 * @package Packetery
 */
trait HookTrait {

	/**
	 * Applies filters.
	 *
	 * @param string $hookName Hook name.
	 * @param mixed  $value Value.
	 * @param mixed  ...$args Arguments.
	 *
	 * @return mixed
	 */
	public function applyFilters( string $hookName, $value, ...$args ) {
		/**
		 * Bridged function call.
		 *
		 * @since 1.7.7
		 */
		return apply_filters( $hookName, $value, ...$args );
	}

	/**
	 * Tells if action was performed.
	 *
	 * @param string $hookName Name of hook.
	 *
	 * @return int
	 */
	public function didAction( string $hookName ): int {
		return did_action( $hookName );
	}

}
