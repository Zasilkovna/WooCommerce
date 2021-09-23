<?php
/**
 * Class CreatePacket
 *
 * @package Packetery\Validator
 */

declare( strict_types=1 );

namespace Packetery\Validator;

use Packetery\Api\Soap\Request;

/**
 * Class CreatePacket
 *
 * @package Packetery\Validator
 */
class CreatePacket {

	/**
	 * Validates data needed to instantiate.
	 *
	 * @param Request\CreatePacket $request CreatePacket request.
	 *
	 * @return bool
	 */
	public function validate( Request\CreatePacket $request ): bool {
		return (
			$request->getNumber() &&
			$request->getName() &&
			$request->getSurname() &&
			$request->getValue() &&
			$request->getWeight() &&
			$request->getAddressId() &&
			$request->getEshop()
		);
	}

}
