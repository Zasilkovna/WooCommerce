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
 * @package Packetery
 */
class CarDeliveryConfig {
	/**
	 * Car Delivery sample settings.
	 *
	 * @var bool
	 */
	private $sample;

	/**
	 * CarDeliveryConfig constructor.
	 *
	 * @param bool $sample Sample.
	 */
	public function __construct( bool $sample ) {
		$this->sample = $sample;
	}

	/**
	 * Tells whether the sample cars for Packeta CD are enabled, or not.
	 *
	 * @return bool
	 */
	public function isSampleEnabled(): bool {
		return $this->sample;
	}

}
