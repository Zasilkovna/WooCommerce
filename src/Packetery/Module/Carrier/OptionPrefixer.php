<?php
/**
 * Class OptionPrefixer
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

/**
 * Class OptionPrefixer
 *
 * @package Packetery
 */
class OptionPrefixer {

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

	/**
	 * Checks if is packetery shipping option id.
	 *
	 * @param string $optionId Option id.
	 *
	 * @return bool
	 */
	public static function isOptionId( string $optionId ): bool {
		return ( strpos( $optionId, self::CARRIER_OPTION_PREFIX ) === 0 );
	}

	/**
	 * Removes prefix from packetery shipping option id.
	 *
	 * @param string $optionId Option id.
	 *
	 * @return string
	 */
	public static function removePrefix( string $optionId ): string {
		return str_replace( self::CARRIER_OPTION_PREFIX, '', $optionId );
	}

}
