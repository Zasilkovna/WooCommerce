<?php

declare( strict_types=1 );

namespace Tests\Core\Entity;

use Packetery\Core\Entity\PacketStatus;
use PHPUnit\Framework\TestCase;

class PacketStatusTest extends TestCase {

	public function testGetters(): void {
		$dummyName           = 'Dummy name';
		$dummyTranslatedName = 'Dummy translated name';
		$packetStatus        = new PacketStatus(
			$dummyName,
			$dummyTranslatedName,
			false,
		);
		self::assertSame( $dummyName, $packetStatus->getName() );
		self::assertSame( $dummyTranslatedName, $packetStatus->getTranslatedName() );
		self::assertFalse( $packetStatus->hasDefaultSynchronization() );
	}

}
