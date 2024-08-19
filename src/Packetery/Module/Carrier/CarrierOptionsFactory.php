<?php
/**
 * CarrierOptionsFactory class.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

/**
 * CarrierOptionsFactory class.
 */
class CarrierOptionsFactory {

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
