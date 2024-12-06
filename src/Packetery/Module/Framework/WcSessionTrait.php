<?php

declare( strict_types=1 );

namespace Packetery\Module\Framework;

use WC_Session;
use WC_Session_Handler;

trait WcSessionTrait {
	public function initializeSession(): void {
		WC()->initialize_session();
	}

	/**
	 * @return WC_Session|WC_Session_Handler|null
	 */
	public function session() {
		return WC()->session;
	}

	/**
	 * Phpdoc is not reliable.
	 *
	 * @return mixed
	 */
	public function sessionGet( string $key ) {
		return WC()->session->get( $key );
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return void
	 */
	public function sessionSet( string $key, $value ): void {
		WC()->session->set( $key, $value );
	}

	/**
	 * Phpdoc is not reliable.
	 *
	 * @return int|string
	 */
	public function sessionGetCustomerId() {
		return WC()->session->get_customer_id();
	}
}
