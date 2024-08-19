<?php

declare( strict_types=1 );

namespace Tests\Module;

trait WithMockFactory {

	public function getPacketeryMockFactory(): MockFactory {
		static $mockFactory = null;

		if ( $mockFactory === null ) {
			$mockFactory = new MockFactory( $this );
		}

		return $mockFactory;
	}

}
