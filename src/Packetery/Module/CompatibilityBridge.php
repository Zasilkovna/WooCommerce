<?php
/**
 * Class CompatibilityBridge.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Nette\DI\Container;

/**
 * Class CompatibilityBridge.
 *
 * @package Packetery
 */
class CompatibilityBridge {
	/**
	 * Storage to use in ShippingMethod.
	 *
	 * @var Container Container;
	 */
	private static $diContainer;

	/**
	 * Container setter.
	 *
	 * @param Container $container Container.
	 */
	public static function setContainer( Container $container ): void {
		self::$diContainer = $container;
	}

	/**
	 * Container getter.
	 *
	 * @return Container
	 */
	public static function getContainer(): Container {
		return self::$diContainer;
	}

}
