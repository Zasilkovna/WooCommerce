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
	 * Gets transient value.
	 *
	 * @param string $transientName Transient name.
	 *
	 * @return mixed
	 */
	public function getTransient( string $transientName ) {
		return get_transient( $transientName );
	}

	/**
	 * Sets transient.
	 *
	 * @param string $transientName Transient name.
	 * @param mixed  $transientValue Transient value.
	 * @param int    $expiration Expiration in seconds.
	 *
	 * @return bool
	 */
	public function setTransient( string $transientName, $transientValue, int $expiration = 0 ): bool {
		return set_transient( $transientName, $transientValue, $expiration );
	}

}
