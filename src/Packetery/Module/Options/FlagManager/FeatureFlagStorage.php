<?php
/**
 * Class FeatureFlagStorage
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Options\FlagManager;

/**
 * Class FeatureFlagStorage
 *
 * @package Packetery
 */
class FeatureFlagStorage {

	/**
	 * Static cache.
	 *
	 * @var array|null
	 */
	private static $flags;

	/**
	 * Constructor.
	 */
	public function __construct() {
		self::$flags = null;
	}

	/**
	 * Gets flags.
	 *
	 * @return array|null
	 */
	public function getFlags(): ?array {
		return self::$flags;
	}

	/**
	 * Gets flags.
	 *
	 * @param array $flags Flags.
	 */
	public function setFlags( array $flags ): void {
		self::$flags = $flags;
	}

	/**
	 * Gets flag.
	 *
	 * @param array|false|null $key Key.
	 *
	 * @return mixed|null
	 */
	public function getFlag( $key ) {
		return self::$flags[ $key ] ?? null;
	}

}
