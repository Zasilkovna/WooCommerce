<?php
/**
 * Class WebRequestClient
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Core\Interfaces\IWebRequestClient;
use Packetery\Module\Exception\WebRequestException;

/**
 * Class WebRequestClient
 *
 * @package Packetery
 */
class WebRequestClient implements IWebRequestClient {

	private const TIMEOUT = 30;

	/**
	 * POST.
	 *
	 * @param string               $uri Target URI.
	 * @param array<string, mixed> $options Options.
	 */
	public function post( string $uri, array $options ): string {
		$resultResponse = wp_remote_post( $uri, $options );

		return wp_remote_retrieve_body( $resultResponse );
	}

	/**
	 * GET.
	 *
	 * @param string $apiUrl Target URL.
	 * @param string $apiKey API key.
	 *
	 * @return string
	 * @throws WebRequestException WebRequestException.
	 */
	public function get( string $apiUrl, string $apiKey ): string {
		$url    = sprintf( $apiUrl, $apiKey );
		$result = wp_remote_get( $url, [ 'timeout' => self::TIMEOUT ] );
		if ( is_wp_error( $result ) ) {
			throw new WebRequestException( $result->get_error_message() );
		}

		return wp_remote_retrieve_body( $result );
	}
}
