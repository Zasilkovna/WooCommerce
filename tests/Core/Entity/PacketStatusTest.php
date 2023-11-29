<?php

declare( strict_types=1 );

namespace Core\Entity;

use Packetery\Core\Entity\PacketStatus;
use PHPUnit\Framework\TestCase;

class PacketStatusTest extends TestCase {

	public function testGetters() {
		$dummyName           = 'Dummy name';
		$dummyTranslatedName = 'Dummy translated name';
		$packetStatus        = new PacketStatus(
			$dummyName,
			$dummyTranslatedName,
			false,
		);
		$this->assertSame( $dummyName, $packetStatus->getName() );
		$this->assertSame( $dummyTranslatedName, $packetStatus->getTranslatedName() );
		$this->assertFalse( $packetStatus->hasDefaultSynchronization() );
	}

}
