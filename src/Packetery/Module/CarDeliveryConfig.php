<?php
/**
 * Class CarDeliveryConfig
 *
 * @package Packetery
 */

namespace Packetery\Module;

/**
 * Class SurveyConfig
 *
 * @property array $carDeliveryConfig
 * @package Packetery
 */
class CarDeliveryConfig {
	/**
	 * Car Delivery config settings.
	 *
	 * @var array
	 */
	private $carDeliveryConfig;

	/**
	 * CarDeliveryConfig constructor.
	 *
	 * @param array $carDeliveryConfig Sample.
	 */
	public function __construct( array $carDeliveryConfig ) {
		$this->carDeliveryConfig = $carDeliveryConfig;
	}

	/**
	 * Tells whether the sample cars for Packeta CD are enabled, or not.
	 *
	 * @return bool
	 */
	public function isSampleEnabled(): bool {
		return $this->carDeliveryConfig['sample'];
	}

}
