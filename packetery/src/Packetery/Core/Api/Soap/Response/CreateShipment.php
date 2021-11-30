<?php

declare( strict_types=1 );


namespace Packetery\Core\Api\Soap\Response;

class CreateShipment extends BaseResponse {

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var string
	 */
	private $checksum;

	/**
	 * @var string
	 */
	private $barcode;

	/**
	 * @var string
	 */
	private $barcodeText;

	/**
	 * Gets ID.
	 *
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * Sets ID.
	 *
	 * @param int $id
	 */
	public function setId( int $id ): void {
		$this->id = $id;
	}

	/**
	 * Gets checksum.
	 *
	 * @return string
	 */
	public function getChecksum(): string {
		return $this->checksum;
	}

	/**
	 * Checksum.
	 *
	 * @param string $checksum Checksum.
	 */
	public function setChecksum( string $checksum ): void {
		$this->checksum = $checksum;
	}

	/**
	 * Barcode.
	 *
	 * @return string
	 */
	public function getBarcode(): string {
		return $this->barcode;
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
	 * Barcode text.
	 *
	 * @return string
	 */
	public function getBarcodeText(): string {
		return $this->barcodeText;
	}

	/**
	 * Barcode text.
	 *
	 * @param string $barcodeText Barcode text.
	 */
	public function setBarcodeText( string $barcodeText ): void {
		$this->barcodeText = $barcodeText;
	}
}
