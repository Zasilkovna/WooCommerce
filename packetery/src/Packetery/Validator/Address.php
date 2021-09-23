<?php
/**
 * Class Address
 *
 * @package Packetery\Validator
 */

declare( strict_types=1 );

namespace Packetery\Validator;

/**
 * Class Address
 *
 * @package Packetery\Validator
 */
class Address {

	/**
	 * Validates data needed to instantiate.
	 *
	 * @param string|null $street Street.
	 * @param string|null $city City.
	 * @param string|null $zip Zip.
	 *
	 * @return bool
	 */
	public function validate( ?string $street, ?string $city, ?string $zip ): bool {
		return ( $street && $city && $zip );
	}


}
