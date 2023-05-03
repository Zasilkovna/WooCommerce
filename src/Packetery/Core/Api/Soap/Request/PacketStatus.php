<?php
/**
 * Class PacketStatus
 *
 * @package Packetery\Api\Soap\Request
 */

declare( strict_types=1 );


namespace Packetery\Core\Api\Soap\Request;

/**
 * Class PacketStatus
 *
 * @package Packetery\Api\Soap\Request
 */
class PacketStatus {

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
	 * Gets packet ID.
	 *
	 * @return string
	 */
	public function getPacketId(): string {
		return $this->packetId;
	}
}
