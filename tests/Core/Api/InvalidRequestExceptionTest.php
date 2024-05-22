<?php

declare( strict_types=1 );

namespace Tests\Core\Api;

use Packetery\Core\Api\InvalidRequestException;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class InvalidRequestExceptionTest extends TestCase {

	public function testSettersAndGetters(): void {
		$invalidException = new InvalidRequestException( 'Error message', ['some error message'] );

		self::assertIsArray( $invalidException->getMessages() );
	}

}
