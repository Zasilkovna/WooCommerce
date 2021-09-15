<?php
/**
 * Class CreatePacket.
 *
 * @package Packetery\Api\Soap\Response
 */

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
	 * @var string
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
	 * @param string $errors Errors.
	 */
	public function setErrors( string $errors ): void {
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
	 * @return string
	 */
	public function getErrors(): string {
		return $this->errors;
	}
}
