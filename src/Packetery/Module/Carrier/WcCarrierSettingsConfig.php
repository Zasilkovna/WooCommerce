<?php
/**
 * Class WcCarrierSettingsConfig
 *
 * @package Packetery
 */

namespace Packetery\Module\Carrier;

/**
 * Class WcCarrierSettingsConfig
 *
 * @package Packetery
 */
class WcCarrierSettingsConfig {

	/**
	 * Tells whether is WC carrier setting active.
	 *
	 * @var bool
	 */
	private $active;

	/**
	 * WcCarrierSettingsConfig constructor.
	 *
	 * @param bool $active Active.
	 */
	public function __construct( bool $active ) {
		$this->active = $active;
	}

	/**
	 * Tells whether is WC carrier setting active.
	 *
	 * @return bool
	 */
	public function isActive(): bool {
		return $this->active;
	}

}
