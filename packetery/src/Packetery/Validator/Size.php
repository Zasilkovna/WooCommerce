<?php
/**
 * Class Size
 *
 * @package Packetery\Validator
 */

declare( strict_types=1 );

namespace Packetery\Validator;

/**
 * Class Size
 *
 * @package Packetery\Validator
 */
class Size {

	/**
	 * Validates data needed to instantiate.
	 *
	 * @param float|null $length Length.
	 * @param float|null $width Width.
	 * @param float|null $height Height.
	 *
	 * @return bool
	 */
	public function validate( ?float $length, ?float $width, ?float $height ): bool {
		return ( $length && $width && $height );
	}

}
