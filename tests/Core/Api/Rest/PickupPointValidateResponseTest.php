<?php

declare( strict_types=1 );

namespace Tests\Core\Api\Rest;

use Packetery\Core\Api\Rest\PickupPointValidateResponse;
use PHPUnit\Framework\TestCase;

class PickupPointValidateResponseTest extends TestCase {

	public function testGetters(): void {
		$response = new PickupPointValidateResponse( false, [ 'dummyError' ] );
		self::assertFalse( $response->isValid() );
		self::assertCount( 1, $response->getErrors() );
	}

}
