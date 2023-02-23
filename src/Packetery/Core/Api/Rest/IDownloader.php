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
	 * Accepts parameters in Guzzle format.
	 *
	 * @param string $uri Target URI.
	 * @param array  $options Options.
	 *
	 * @return string
	 * @throws \Exception Thrown on failure.
	 */
	public function post( string $uri, array $options ): string;

}
