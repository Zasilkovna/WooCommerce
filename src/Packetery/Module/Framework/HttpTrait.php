<?php
/**
 * Trait HttpTrait.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

use WP_Error;

/**
 * Trait HttpTrait.
 *
 * @package Packetery
 */
trait HttpTrait {

	/**
	 * Performs an HTTP request using the GET method and returns its response.
	 *
	 * @param string $url URL to retrieve.
	 * @param array  $args Optional. Request arguments. Default empty array. See WP_Http::request() for information on accepted arguments.
	 *
	 * @return array|WP_Error The response or WP_Error on failure.
	 */
	public function remoteGet( string $url, array $args = [] ) {
		return wp_remote_get( $url, $args );
	}

	/**
	 * Retrieves only the body from the raw response.
	 *
	 * @param array|WP_Error $response HTTP response.
	 * @return string The body of the response. Empty string if no body or incorrect parameter given.
	 */
	public function remoteRetrieveBody( $response ): string {
		return wp_remote_retrieve_body( $response );
	}

}
