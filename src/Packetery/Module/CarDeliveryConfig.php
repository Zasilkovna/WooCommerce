<?php
/**
 * Class CarDeliveryConfig
 *
 * @package Packetery
 */

namespace Packetery\Module;

/**
 * Class CarDeliveryConfig
 *
 * @property bool $sample
 * @property bool $enabled
 * @package Packetery
 */
class CarDeliveryConfig {

	/**
	 * CarDeliveryConfig constructor.
	 *
	 * @param bool $sample Sample.
	 * @param bool $enabled Enabled.
	 */
	public function __construct( bool $sample, bool $enabled ) {
		$this->sample  = $sample;
		$this->enabled = $enabled;
	}

	/**
	 * Tells whether the sample cars for Packeta CD are enabled, or not.
	 *
	 * @return bool
	 */
	public function isSampleEnabled(): bool {
		return $this->sample;
	}

	/**
	 * Tells whether the car delivery is enabled.
	 *
	 * @return bool
	 */
	public function isEnabled(): bool {
		return $this->enabled;
	}

}
