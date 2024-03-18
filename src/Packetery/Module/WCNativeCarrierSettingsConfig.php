<?php
/**
 * Class WCNativeCarrierSettingsConfig
 *
 * @package Packetery
 */

namespace Packetery\Module;

/**
 * Class WCNativeCarrierSettingsConfig
 *
 * @property bool $active
 * @package Packetery
 */
class WCNativeCarrierSettingsConfig {

	/**
	 * WCNativeCarrierSettingsConfig constructor.
	 *
	 * @param bool $active Active.
	 */
	public function __construct( bool $active ) {
		$this->active = $active;
	}

	/**
	 * Tells whether the native Packeta carrier settings is active.
	 *
	 * @return bool
	 */
	public function isSettingsActive(): bool {
		return $this->active;
	}

}
