<?php
/**
 * Class WcSettingsConfig
 *
 * @package Packetery
 */

namespace Packetery\Module\Carrier;

/**
 * Class WcSettingsConfig
 *
 * @package Packetery
 */
class WcSettingsConfig {

	/**
	 * Tells whether is WC carrier setting active.
	 *
	 * @var bool
	 */
	private $active;

	/**
	 * WcSettingsConfig constructor.
	 *
	 * @param bool $active Active.
	 */
	public function __construct( bool $active ) {
		$this->active = $active;
	}

	/**
	 * Tells whether is the carrier setting native to WC active.
	 *
	 * @return bool
	 */
	public function isActive(): bool {
		return $this->active;
	}

}
