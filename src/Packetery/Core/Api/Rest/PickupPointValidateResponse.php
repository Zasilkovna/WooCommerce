<?php
/**
 * Class PickupPointValidateResponse
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Rest;

/**
 * Class PickupPointValidateResponse
 *
 * @package Packetery
 */
class PickupPointValidateResponse {

	/**
	 * Validity flag.
	 *
	 * @var bool
	 */
	private $isValid;

	/**
	 * Possible errors.
	 *
	 * @var string[]
	 */
	private $errors;

	/**
	 * PickupPointValidateResponse constructor.
	 *
	 * @param bool     $isValid Validity flag.
	 * @param string[] $errors  Possible errors.
	 */
	public function __construct( bool $isValid, array $errors ) {
		$this->isValid = $isValid;
		$this->errors  = $errors;
	}

	/**
	 * Validity flag.
	 *
	 * @return bool
	 */
	public function isValid(): bool {
		return $this->isValid;
	}

	/**
	 * Get array of errors.
	 *
	 * @return string[]
	 */
	public function getErrors(): array {
		return $this->errors;
	}

}
