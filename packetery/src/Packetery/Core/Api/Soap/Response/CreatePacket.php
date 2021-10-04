<?php
/**
 * Class CreatePacket.
 *
 * @package Packetery\Api\Soap\Response
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Response;

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
	 * Packet attributes errors.
	 *
	 * @var array
	 */
	private $validationErrors;

	/**
	 * Fault string.
	 *
	 * @var string
	 */
	private $faultString;

	/**
	 * Sets barcode.
	 *
	 * @param string $barcode Barcode.
	 */
	public function setBarcode( string $barcode ): void {
		$this->barcode = $barcode;
	}

	/**
	 * Sets fault string.
	 *
	 * @param string $faultString Fault string.
	 */
	public function setFaultString( string $faultString ): void {
		$this->faultString = $faultString;
	}

	/**
	 * Sets errors.
	 *
	 * @param array $errors Errors.
	 */
	public function setValidationErrors( array $errors ): void {
		$this->validationErrors = $errors;
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
	 * Gets fault string.
	 *
	 * @return string|null
	 */
	public function getFaultString(): ?string {
		return $this->faultString;
	}

	/**
	 * Gets errors.
	 *
	 * @return array|null
	 */
	public function getValidationErrors(): ?array {
		return $this->validationErrors;
	}

	/**
	 * Gets all errors as string.
	 *
	 * @return string
	 */
	public function getErrorsAsString(): string {
		$allErrors = $this->validationErrors;
		array_unshift( $allErrors, $this->faultString );

		return implode( ', ', $allErrors );
	}
}
