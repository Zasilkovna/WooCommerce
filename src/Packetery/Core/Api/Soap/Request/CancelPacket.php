<?php
/**
 * Class CancelPacket.
 *
 * @package Packetery\Core\Api\Soap\Request
 */

declare( strict_types=1 );


namespace Packetery\Core\Api\Soap\Request;

/**
 * Class CancelPacket.
 *
 * @package Packetery\Core\Api\Soap\Request
 */
class CancelPacket {

	/**
	 * Packet ID.
	 *
	 * @var string
	 */
	private $packetId;

	/**
	 * Constructor.
	 *
	 * @param string $packetId Packet ID.
	 */
	public function __construct( string $packetId ) {
		$this->packetId = $packetId;
	}

	/**
	 * Packet ID.
	 *
	 * @return string
	 */
	public function getPacketId(): string {
		return $this->packetId;
	}
}
