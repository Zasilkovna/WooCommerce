<?php

declare( strict_types=1 );

namespace Packetery\Module\Framework;

use WC_Session;
use WC_Session_Handler;

use function is_array;
use function is_string;

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
	private function sessionGet( string $key ) {
		return WC()->session->get( $key );
	}

	/**
	 * Minimal logic to ensure strict typing.
	 */
	public function sessionGetString( string $key ): ?string {
		$sessionValue = $this->sessionGet( $key );
		if ( is_string( $sessionValue ) ) {
			return $sessionValue;
		}

		return null;
	}

	/**
	 * Minimal logic to ensure strict typing.
	 */
	public function sessionGetArray( string $key ): ?array {
		$sessionValue = $this->sessionGet( $key );
		if ( is_array( $sessionValue ) ) {
			return $sessionValue;
		}

		return null;
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
