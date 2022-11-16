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
class CreatePacket extends BaseResponse {

	/**
	 * Barcode without leading Z.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Barcode with leading Z.
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
	 * Sets id.
	 *
	 * @param int $id Id.
	 */
	public function setId( int $id ): void {
		$this->id = $id;
	}

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
	public function setValidationErrors( array $errors ): void {
		$this->validationErrors = $errors;
	}

	/**
	 * Gets id.
	 *
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
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
	 * @return array|null
	 */
	public function getValidationErrors(): ?array {
		return $this->validationErrors;
	}

	/**
	 * Gets all errors as string.
	 *
	 * @param bool $prependFaultString Prepend fault string.
	 *
	 * @return string
	 */
	public function getErrorsAsString( bool $prependFaultString = true ): string {
		$allErrors = $this->validationErrors;
		if ( $prependFaultString ) {
			array_unshift( $allErrors, $this->getFaultString() );
		}

		return implode( ', ', $allErrors );
	}

	/**
	 * Returns error message, which is made of validation errors or fault string, otherwise null
	 *
	 * @return string|null
	 */
	public function getErrorMessage(): ?string {
		if ( ! $this->hasFault() ) {
			return null;
		}
		$prependFaultString = ! $this->validationErrors;

		return $this->getErrorsAsString( $prependFaultString );
	}

}
