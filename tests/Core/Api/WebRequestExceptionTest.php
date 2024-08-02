<?php

declare( strict_types=1 );

namespace Tests\Core\Api;

use Packetery\Core\Api\WebRequestException;
use PHPUnit\Framework\TestCase;

class WebRequestExceptionTest extends TestCase {

	public function testGetMessage(): void {
		$invalidException = new WebRequestException( 'Error message' );

		self::assertIsString( $invalidException->getMessage() );
	}

}
