<?php
/**
 * Class DebugModeChecker
 *
 * @package Packetery
 */

declare( strict_types=1 );

/**
 * Class DebugModeChecker
 *
 * @package Packetery
 */
namespace Packetery\Module;

/**
 * Class DebugModeChecker
 *
 * @package Packetery
 */
class DebugModeChecker {

	/**
	 * Tells if debug mode is enabled.
	 *
	 * @var bool
	 */
	private $debugMode;

	/**
	 * Constructor.
	 *
	 * @param bool $debugMode Tells if debug mode is enabled.
	 */
	public function __construct( bool $debugMode ) {
		$this->debugMode = $debugMode;
	}

	/**
	 * Tells if debug mode is enabled.
	 *
	 * @return bool
	 */
	public function isDebugMode(): bool {
		return $this->debugMode;
	}
}
