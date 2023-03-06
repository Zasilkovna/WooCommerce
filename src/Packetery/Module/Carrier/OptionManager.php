<?php
/**
 * Class OptionManager
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

/**
 * Class OptionManager
 *
 * @package Packetery
 */
class OptionManager {

	public const CARRIER_OPTION_PREFIX = 'packetery_carrier_';

	/**
	 * Prepares carrier option id.
	 *
	 * @param string $carrierId Carrier id.
	 *
	 * @return string
	 */
	public static function getOptionId( string $carrierId ): string {
		return self::CARRIER_OPTION_PREFIX . $carrierId;
	}

}
