<?php
/**
 * OptionsFactory class.
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module\Carrier;

/**
 * OptionsFactory class.
 */
class OptionsFactory {

	/**
	 * Creates carrier options by option id.
	 *
	 * @param string $optionId Option id.
	 *
	 * @return Options
	 */
	public function createByOptionId( string $optionId ): Options {
		return Options::createByOptionId( $optionId );
	}
}
