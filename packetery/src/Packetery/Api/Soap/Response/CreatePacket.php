<?php
/**
 * Class CreatePacket.
 *
 * @package Packetery\Api\Soap\Response
 */

declare( strict_types=1 );

namespace Packetery\Api\Soap\Response;

/**
 * Class CreatePacket.
 *
 * @package Packetery\Api\Soap\Response
 */
class CreatePacket {

	/**
	 * Barcode.
	 *
	 * @var string
	 */
	private $barcode;

	/**
	 * Errors.
	 *
	 * @var array
	 */
	private $errors;

	/**
	 * Sets barcode.
	 *
	 * @param string $barcode Barcode.
	 */
	public function setBarcode( string $barcode ): void {
		$this->barcode = $barcode;
	}

	/**
	 * Sets errors.
	 *
	 * @param array $errors Errors.
	 */
	public function setErrors( array $errors ): void {
		$this->errors = $errors;
	}

	/**
	 * Gets barcode.
	 *
	 * @return string
	 */
	public function getBarcode(): string {
		return $this->barcode;
	}

	/**
	 * Gets errors.
	 *
	 * @return array
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * Gets errors as string.
	 *
	 * @return string
	 */
	public function getErrorsAsString(): string {
		return implode( ', ', $this->errors );
	}
}
