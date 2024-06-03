<?php

declare( strict_types=1 );

namespace Tests\Core\PickupPointProvider;

use Packetery\Core\PickupPointProvider\VendorCollectionFactory;
use PHPUnit\Framework\TestCase;

class VendorCollectionFactoryTest extends TestCase {

	public function testCreate(): void {
		$factory = new VendorCollectionFactory();
		self::assertCount( 8, $factory->create() );
	}

}
