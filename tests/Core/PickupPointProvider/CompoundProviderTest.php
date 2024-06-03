<?php

declare( strict_types=1 );

namespace Tests\Core\PickupPointProvider;

use Packetery\Core\PickupPointProvider\CompoundProvider;
use PHPUnit\Framework\TestCase;

class CompoundProviderTest extends TestCase {

	public function testGetVendorCodes(): void {
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
			],
		);
		self::assertCount( 2, $vendor->getVendorCodes() );
	}

}
