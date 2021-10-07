<?php
/**
 * Class PacketCourierNumber.
 *
 * @package Packetery\Core\Api\Soap\Response
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Response;

/**
 * Class PacketCourierNumber.
 *
 * @package Packetery\Core\Api\Soap\Response
 */
class PacketCourierNumber {
	/**
	 * Fault string.
	 *
	 * @var string
	 */
	private $faultString;

	/**
	 * Packet carrier number.
	 *
	 * @var string
	 */
	private $number;

	/**
	 * Sets packet carrier number.
	 *
	 * @param string $number Packet carrier number.
	 */
	public function setNumber( string $number ): void {
		$this->number = $number;
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
	 * Gets packet carrier number
	 *
	 * @return string
	 */
	public function getNumber(): string {
		return $this->number;
	}

	/**
	 * Gets fault string.
	 *
	 * @return string|null
	 */
	public function getFaultString(): ?string {
		return $this->faultString;
	}

}
