<?php
/**
 * Class ControllerRouter
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );


namespace Packetery\Module\Order;

/**
 * Class ControllerRouter
 *
 * @package Packetery\Module\Order
 */
class ControllerRouter {
	/**
	 * @var string
	 */
	private $namespace = 'packetery/v1';

	/**
	 * @var string
	 */
	private $restBase = 'order';

	/**
	 * Gets namespace.
	 *
	 * @return string
	 */
	public function getNamespace(): string {
		return $this->namespace;
	}

	/**
	 * Gets REST base.
	 *
	 * @return string
	 */
	public function getRestBase(): string {
		return $this->restBase;
	}

	/**
	 * Gets route URL.
	 *
	 * @param string $path
	 */
	public function getRouteUrl( string $path ): string {
		return get_rest_url( null, $this->namespace . $this->getRoute( $path ) );
	}

	/**
	 * Gets route.
	 *
	 * @param string $path
	 */
	public function getRoute( string $path ): string {
		return "/{$this->restBase}{$path}";
	}

	/**
	 * Register route.
	 *
	 * @param string $path
	 * @param array  $params
	 */
	public function registerRoute( string $path, array $params ): void {
		register_rest_route( $this->namespace, $this->getRoute( $path ), $params );
	}
}
