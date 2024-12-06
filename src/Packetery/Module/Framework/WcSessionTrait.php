<?php

declare( strict_types=1 );

namespace Packetery\Module\Framework;

trait WcSessionTrait {
	/**
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return void
	 */
	public function sessionSet( string $key, $value ): void {
		WC()->session->set( $key, $value );
	}
}
