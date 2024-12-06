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
	public function addAction( string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1 ): ?bool {
		return add_action( $hookName, $callback, $priority, $acceptedArgs );
	}

	public function addFilter( string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1 ): void {
		add_filter( $hookName, $callback, $priority, $acceptedArgs );
	}

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

	public function registerDeactivationHook( string $file, callable $callback ): void {
		register_deactivation_hook( $file, $callback );
	}

	public function registerUninstallHook( string $file, callable $callback ): void {
		register_uninstall_hook( $file, $callback );
	}
}
