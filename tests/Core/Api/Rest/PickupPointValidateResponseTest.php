<?php

declare( strict_types=1 );

namespace Core\Api\Rest;

use Packetery\Core\Api\Rest\PickupPointValidateResponse;
use PHPUnit\Framework\TestCase;

class PickupPointValidateResponseTest extends TestCase {

	public function testGetters() {
		$response = new PickupPointValidateResponse( false, [ 'dummyError' ] );
		$this->assertFalse( $response->isValid() );
		$this->assertCount( 1, $response->getErrors() );
	}

}
