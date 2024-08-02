<?php
/**
 * Class IWebRequestClient
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Interfaces;

/**
 * Class IWebRequestClient
 *
 * @package Packetery
 */
interface IWebRequestClient {
	/**
	 * Accepts parameters in WP format.
	 *
	 * @param string               $uri     Target URI.
	 * @param array<string, mixed> $options Options.
	 */
	public function post( string $uri, array $options ): string;

	/**
	 * Accepts parameters in WP format.
	 *
	 * @param string               $url     API url.
	 * @param array<string, mixed> $options Options.
	 *
	 * @return string
	 */
	public function get( string $url, array $options ): string;
}
