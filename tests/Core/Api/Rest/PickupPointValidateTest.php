<?php

declare( strict_types=1 );

namespace Tests\Core\Api\Rest;

use Exception;
use Packetery\Core\Api\Rest\PickupPointValidate;
use Packetery\Core\Api\Rest\PickupPointValidateResponse;
use Packetery\Core\Api\Rest\RestException;
use Packetery\Module\WebRequestClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;
use function json_encode;

class PickupPointValidateTest extends TestCase {

	public function testValidateOk(): void {
		$webRequestClientMock = $this->getWebRequestClientMock();
		$webRequestClientMock->method( 'post' )
		               ->willReturn( json_encode( [
			               'isValid' => true,
			               'errors'  => [],
		               ] ) );
		$validator = new PickupPointValidate( $webRequestClientMock, 'dummyApiKey' );

		self::assertInstanceOf(
			PickupPointValidateResponse::class,
			$validator->validate( DummyFactory::getEmptyPickupPointValidateRequest() )
		);
	}

	public function testValidateFail(): void {
		$webRequestClientMock = $this->getWebRequestClientMock();
		$webRequestClientMock->method( 'post' )
		               ->willThrowException( new Exception( 'dummyException' ) );
		$validator = new PickupPointValidate( $webRequestClientMock, 'dummyApiKey' );

		$this->expectException( RestException::class );
		$validator->validate( DummyFactory::getEmptyPickupPointValidateRequest() );
	}

	private function getWebRequestClientMock(): MockObject|WebRequestClient {
		return $this->getMockBuilder( WebRequestClient::class )
		            ->setMockClassName( 'WebRequestClientMock' )
		            ->getMock();
	}

}
