<?php
/**
 * Class PacketStatus
 *
 * @package Packetery\Api\Soap\Request
 */

declare( strict_types=1 );


namespace Packetery\Core\Api\Soap\Request;

use Packetery\Core\Helper;

/**
 * Class PacketStatus
 *
 * @package Packetery\Api\Soap\Request
 */
class PacketSetStoredUntil {

	/**
	 * Packet ID.
	 *
	 * @var string
	 */
	private $packetId;

	/**
	 * Stored until date.
	 *
	 * @var \DateTimeImmutable
	 */
	private $storedUntil;

	/**
	 * Stored until date.
	 *
	 * @var Helper
	 */
	private $helper;

	/**
	 * Constructor.
	 *
	 * @param string             $packetId Packet ID.
	 * @param \DateTimeImmutable $storedUntil Stored until date.
	 * @param Helper             $helper Helper.
	 */
	private function __construct( string $packetId, \DateTimeImmutable $storedUntil, Helper $helper ) {
		$this->packetId    = $packetId;
		$this->storedUntil = $storedUntil;
		$this->helper      = $helper;
	}

	/**
	 * Named Constructor.
	 *
	 * @param string             $packetId Packet ID.
	 * @param \DateTimeImmutable $storedUntil Stored until date.
	 */
	public static function create( string $packetId, \DateTimeImmutable $storedUntil ): PacketSetStoredUntil {
		return new self(
			$packetId,
			$storedUntil,
			new Helper(),
		);
	}

	/**
	 * Gets packet ID.
	 *
	 * @return string
	 */
	public function getPacketId(): string {
		return $this->packetId;
	}

	/**
	 * Gets stored until date as string
	 *
	 * @return string
	 */
	public function getStoredUntil(): ?string {
		return $this->helper->getStringFromDateTime(
			$this->storedUntil,
			Helper::MYSQL_DATE_FORMAT,
		);
	}
}
