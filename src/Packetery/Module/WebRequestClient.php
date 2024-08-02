<?php
/**
 * Class WebRequestClient
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Core\Api\WebRequestException;
use Packetery\Core\Interfaces\IWebRequestClient;

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
	 * @param string               $url     Target URL.
	 * @param array<string, mixed> $options Options.
	 *
	 * @throws WebRequestException WebRequestException.
	 */
	public function post( string $url, array $options ): string {
		$resultResponse = wp_remote_post( $url, $options );
		if ( is_wp_error( $resultResponse ) ) {
			throw new WebRequestException( $resultResponse->get_error_message() );
		}

		return wp_remote_retrieve_body( $resultResponse );
	}

	/**
	 * GET.
	 *
	 * @param string               $url     Target URL.
	 * @param array<string, mixed> $options Options.
	 *
	 * @return string
	 * @throws WebRequestException WebRequestException.
	 */
	public function get( string $url, array $options = [] ): string {
		$options[]      = [
			'timeout' => self::TIMEOUT,
		];
		$resultResponse = wp_remote_get( $url, $options );
		if ( is_wp_error( $resultResponse ) ) {
			throw new WebRequestException( $resultResponse->get_error_message() );
		}

		return wp_remote_retrieve_body( $resultResponse );
	}

}
