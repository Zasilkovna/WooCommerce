<?php

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Request;

class PacketInfo {

	private string $packetId;

	public function __construct( string $packetId ) {
		$this->packetId = $packetId;
	}

	public function getPacketId(): string {
		return $this->packetId;
	}
}
