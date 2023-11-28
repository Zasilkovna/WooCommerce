<?php

declare( strict_types=1 );

namespace Tests\Core\PickupPointProvider;

use Packetery\Core\PickupPointProvider\CompoundProvider;
use PHPUnit\Framework\TestCase;

class CompoundProviderTest extends TestCase {

	public function testGetVendorCodes() {
		$vendor = new CompoundProvider(
			'zpointcz',
			'cz',
			true,
			true,
			'CZK',
			true,
			[
				'czzpoint',
				'czzbox',
				'czalzabox',
			],
		);
		$this->assertCount( 3, $vendor->getVendorCodes() );
	}

}
