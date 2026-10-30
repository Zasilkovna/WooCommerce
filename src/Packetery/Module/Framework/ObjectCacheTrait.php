<?php
/**
 * Trait ObjectCacheTrait.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

/**
 * Trait ObjectCacheTrait.
 *
 * @package Packetery
 */
trait ObjectCacheTrait {
	/**
	 * @param string $key   Cache key.
	 * @param string $group Cache group.
	 *
	 * @return mixed|false False when not set.
	 */
	public function cacheGet( string $key, string $group ) {
		return wp_cache_get( $key, $group );
	}

	/**
	 * @param string[] $keys  Cache keys.
	 * @param string   $group Cache group.
	 *
	 * @return array<string, mixed> False for a key that is not set.
	 */
	public function cacheGetMultiple( array $keys, string $group ): array {
		return wp_cache_get_multiple( $keys, $group );
	}

	/**
	 * @param string $key   Cache key.
	 * @param mixed  $data  Data to cache.
	 * @param string $group Cache group.
	 */
	public function cacheAdd( string $key, $data, string $group ): bool {
		return wp_cache_add( $key, $data, $group );
	}

	/**
	 * @param string $key   Cache key.
	 * @param mixed  $data  Data to cache.
	 * @param string $group Cache group.
	 */
	public function cacheSet( string $key, $data, string $group ): bool {
		return wp_cache_set( $key, $data, $group );
	}
}
