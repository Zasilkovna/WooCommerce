<?php
/**
 * Class InvalidPacketIds
 *
 * @package Packetery\Api\Soap\ResponseTrait
 */

declare( strict_types=1 );


namespace Packetery\Core\Api\Soap\ResponseTrait;

/**
 * Class InvalidPacketIds
 *
 * @package Packetery\Api\Soap\ResponseTrait
 */
trait InvalidPacketIds {

	/**
	 * Invalid packet IDs
	 *
	 * @var string[]
	 */
	private $invalidPacketIds = [];

	/**
	 * Sets invalid packet IDs.
	 *
	 * @param string[] $invalidPacketIds Invalid packet IDs.
	 *
	 * @return void
	 */
	public function setInvalidPacketIds( array $invalidPacketIds ): void {
		$this->invalidPacketIds = $invalidPacketIds;
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
}
