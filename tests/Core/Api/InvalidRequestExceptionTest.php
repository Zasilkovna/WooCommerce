<?php

declare( strict_types=1 );

namespace Tests\Core\Api;

use Packetery\Core\Api\InvalidRequestException;
use PHPUnit\Framework\TestCase;

class InvalidRequestExceptionTest extends TestCase {

	public function testGetMessages(): void {
		$message = 'some error message';
		$invalidException = new InvalidRequestException( 'Error message', [$message] );

		self::assertIsArray( $invalidException->getMessages() );
		self::assertContains( $message, $invalidException->getMessages() );
	}

}
