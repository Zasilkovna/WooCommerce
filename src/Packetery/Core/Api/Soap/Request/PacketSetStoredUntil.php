<?php
/**
 * Class PacketSetStoredUntil
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Core\Api\Soap\Request;

use Packetery\Core\CoreHelper;

/**
 * Class PacketSetStoredUntil
 *
 * @package Packetery
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
	 * @var CoreHelper
	 */
	private $coreHelper;

	/**
	 * Constructor.
	 *
	 * @param string             $packetId Packet ID.
	 * @param \DateTimeImmutable $storedUntil Stored until date.
	 * @param CoreHelper         $coreHelper Helper.
	 */
	private function __construct( string $packetId, \DateTimeImmutable $storedUntil, CoreHelper $coreHelper ) {
		$this->packetId    = $packetId;
		$this->storedUntil = $storedUntil;
		$this->coreHelper  = $coreHelper;
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
			new CoreHelper()
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
		return $this->coreHelper->getStringFromDateTime(
			$this->storedUntil,
			CoreHelper::MYSQL_DATE_FORMAT
		);
	}
}
