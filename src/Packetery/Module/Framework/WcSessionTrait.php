<?php

declare( strict_types=1 );

namespace Packetery\Module\Framework;

use WC_Session;
use WC_Session_Handler;

trait WcSessionTrait {
	/**
	 * @return WC_Session|WC_Session_Handler|null
	 */
	public function session() {
		return WC()->session;
	}

	/**
	 * @return array|string
	 */
	public function sessionGet( string $key ) {
		return WC()->session->get( $key );
	}

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
