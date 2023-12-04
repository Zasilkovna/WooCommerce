<?php

declare( strict_types=1 );

namespace Tests\Core\PickupPointProvider;

use Packetery\Core\PickupPointProvider\CompoundCarrierCollectionFactory;
use PHPUnit\Framework\TestCase;

class CompoundCarrierCollectionFactoryTest extends TestCase {

	public function testCreate(): void {
		$factory = new CompoundCarrierCollectionFactory();
		self::assertCount( 4, $factory->create() );
	}

}
