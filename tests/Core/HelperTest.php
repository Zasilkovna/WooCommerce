<?php

declare(strict_types=1);

namespace Tests\Core;

use Packetery\Core\Helper;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase {

	public function testGetTrackingUrl(): void {
		$helper = new Helper();

		$this->assertIsString( $helper->get_tracking_url( 'dummyPacketId' ) );
	}

}
