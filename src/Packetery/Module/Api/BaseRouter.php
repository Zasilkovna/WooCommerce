<?php
/**
 * Class BaseRouter
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Api;

/**
 * Class BaseRouter
 *
 * @package Packetery
 */
class BaseRouter {

	/**
	 * Namespace.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Rest base.
	 *
	 * @var string
	 */
	protected $restBase;

	/**
	 * Gets route URL.
	 *
	 * @param string $path Short relative URL path.
	 */
	public function getRouteUrl( string $path ): string {
		return get_rest_url( null, $this->namespace . $this->getRoute( $path ) );
	}

	/**
	 * Gets route.
	 *
	 * @param string $path Short relative URL path.
	 */
	public function getRoute( string $path ): string {
		return "/{$this->restBase}{$path}";
	}

	/**
	 * Register route.
	 *
	 * @param string $path   Short relative URL path.
	 * @param array  $params Route configuration.
	 */
	public function registerRoute( string $path, array $params ): void {
		register_rest_route( $this->namespace, $this->getRoute( $path ), $params );
	}

}
