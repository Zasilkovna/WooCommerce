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
	 * @var string
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
	 * @var string[]
	 */
	private $validationErrors;

	/**
	 * Sets id.
	 *
	 * @param string $id Id.
	 */
	public function setId( string $id ): void {
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
	 * @param string[] $errors Errors.
	 */
	public function setValidationErrors( array $errors ): void {
		$this->validationErrors = $errors;
	}

	/**
	 * Gets id.
	 *
	 * @return string
	 */
	public function getId(): string {
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
}
