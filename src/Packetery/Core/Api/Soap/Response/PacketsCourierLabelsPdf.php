<?php
/**
 * Class PacketsCourierLabelsPdf.
 *
 * @package Packetery\Api\Soap\Response
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Response;

/**
 * Class PacketsCourierLabelsPdf.
 *
 * @package Packetery\Api\Soap\Response
 */
class PacketsCourierLabelsPdf extends BaseResponse {

	/**
	 * Pdf contents.
	 *
	 * @var string
	 */
	private $pdfContents;

	/**
	 * Invalid packet IDs
	 *
	 * @var array
	 */
	private $invalidPacketIds = [];

	/**
	 * Invalid courier numbers.
	 *
	 * @var array
	 */
	private $invalidCourierNumbers = [];

	/**
	 * Sets pdf contents.
	 *
	 * @param string $pdfContents Pdf contents.
	 */
	public function setPdfContents( string $pdfContents ): void {
		$this->pdfContents = $pdfContents;
	}

	/**
	 * Gets pdf contents.
	 *
	 * @return string
	 */
	public function getPdfContents(): string {
		return $this->pdfContents;
	}

	/**
	 * Sets invalid packet IDs.
	 *
	 * @param array $invalidPacketIds Invalid packet IDs.
	 *
	 * @return void
	 */
	public function setInvalidPacketIds( array $invalidPacketIds ): void {
		$this->invalidPacketIds = $invalidPacketIds;
	}

	/**
	 * Gets invalid packet IDs.
	 *
	 * @return array
	 */
	public function getInvalidPacketIds(): array {
		return $this->invalidPacketIds;
	}

	/**
	 * Sets invalid courier numbers.
	 *
	 * @param array $invalidCourierNumbers Invalid courier numbers.
	 *
	 * @return void
	 */
	public function setInvalidCourierNumbers( array $invalidCourierNumbers ): void {
		$this->invalidCourierNumbers = $invalidCourierNumbers;
	}

	/**
	 * Gets invalid courier numbers.
	 *
	 * @return array
	 */
	public function getInvalidCourierNumbers(): array {
		return $this->invalidCourierNumbers;
	}

	/**
	 * Tells if response has invalid packet ID.
	 *
	 * @param string $packetId Packet ID.
	 *
	 * @return bool|null
	 */
	public function hasInvalidPacketId( string $packetId ): ?bool {
		if ( in_array( $packetId, $this->invalidPacketIds, true ) ) {
			return true;
		}

		return null;
	}
	/**
	 * Tells if response has invalid courier number.
	 *
	 * @param string $courierNumber Courier number.
	 *
	 * @return bool|null
	 */
	public function hasInvalidCourierNumber( string $courierNumber ): ?bool {
		if ( in_array( $courierNumber, $this->invalidCourierNumbers, true ) ) {
			return true;
		}

		return null;
	}
}
