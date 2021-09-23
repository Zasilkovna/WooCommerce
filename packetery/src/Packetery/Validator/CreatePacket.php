<?php
/**
 * Class CreatePacket
 *
 * @package Packetery\Validator
 */

declare( strict_types=1 );

namespace Packetery\Validator;

/**
 * Class CreatePacket
 *
 * @package Packetery\Validator
 */
class CreatePacket {

	/**
	 * Validates data needed to instantiate.
	 *
	 * @param string|null $number Order id.
	 * @param string|null $name Customer name.
	 * @param string|null $surname Customer surname.
	 * @param float|null  $value Order value.
	 * @param float|null  $weight Packet weight.
	 * @param int|null    $addressId Carrier or pickup point id.
	 * @param string|null $eshop Sender label.
	 *
	 * @return bool
	 */
	public function validate(
		?string $number,
		?string $name,
		?string $surname,
		?float $value,
		?float $weight,
		?int $addressId,
		?string $eshop
	): bool {
		return ( $number && $name && $surname && $value && $weight && $addressId && $eshop );
	}

}
