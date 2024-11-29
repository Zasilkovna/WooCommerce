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
	 * @var array<string, string>|null
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
	 * @return array<string, string>|null
	 */
	public function getFlags(): ?array {
		return self::$flags;
	}

	/**
	 * @param array $flags
	 */
	public function setFlags( array $flags ): void {
		self::$flags = $flags;
	}

	/**
	 * Gets flag.
	 *
	 * @param string $key Key.
	 *
	 * @return string|null
	 */
	public function getFlag( string $key ): ?string {
		return self::$flags[ $key ] ?? null;
	}
}
