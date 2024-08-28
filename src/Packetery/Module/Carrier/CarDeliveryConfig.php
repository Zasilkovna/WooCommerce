<?php
/**
 * Class CarDeliveryConfig
 *
 * @package Packetery
 */

namespace Packetery\Module\Carrier;

/**
 * Class CarDeliveryConfig
 *
 * @package Packetery
 */
class CarDeliveryConfig {

	/**
	 * True if sample mode enabled.
	 *
	 * @var bool
	 */
	private $sample;

	/**
	 * True if enabled.
	 *
	 * @var bool
	 */
	private $enabled;

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
	 * Tells whether the car delivery is disabled, or otherwise.
	 *
	 * @return bool
	 */
	public function isDisabled(): bool {
		return ! $this->enabled;
	}

}
