<?php
/**
 * Class IDownloader
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Rest;

/**
 * Class IDownloader
 *
 * @package Packetery
 */
interface IDownloader {

	/**
	 * Accepts parameters in WP format.
	 *
	 * @param string               $uri     Target URI.
	 * @param array<string, mixed> $options Options.
	 */
	public function post( string $uri, array $options ): string;

}
