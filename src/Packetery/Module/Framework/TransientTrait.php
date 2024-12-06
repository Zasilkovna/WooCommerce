<?php
/**
 * Trait TransientTrait.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

use function get_transient;

/**
 * Trait TransientTrait.
 *
 * @package Packetery
 */
trait TransientTrait {
	/**
	 * @return mixed
	 */
	public function getTransient( string $transientName ) {
		return get_transient( $transientName );
	}

	/**
	 * @param string $transientName
	 * @param mixed  $transientValue
	 * @param int    $expiration
	 *
	 * @return bool
	 */
	public function setTransient( string $transientName, $transientValue, int $expiration = 0 ): bool {
		return set_transient( $transientName, $transientValue, $expiration );
	}

	public function deleteTransient( string $transientName ): bool {
		return delete_transient( $transientName );
	}
}
