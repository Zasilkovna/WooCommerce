<?php
/**
 * Class CompatibilityBridge.
 *
 * @package Packetery
 */

namespace Packetery;

use Nette\DI\Container;

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
