<?php

declare( strict_types=1 );

namespace Tests\Core\Api\Rest;

use Exception;
use Packetery\Core\Api\Rest\Exception\InvalidApiKeyException;
use Packetery\Core\Api\Rest\PickupPointValidate;
use Packetery\Core\Api\Rest\PickupPointValidateResponse;
use Packetery\Core\Api\Rest\RestException;
use Packetery\Core\Interfaces\IWebRequestClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class PickupPointValidateTest extends TestCase {
	public function testConstructWithValidApiKey(): void {
		$webRequestClientMock = $this->getWebRequestClientMock();
		$validator            = PickupPointValidate::createWithValidApiKey( $webRequestClientMock, 'dummyApiKey' );

		self::assertNotNull( $validator );
	}

	public function testConstructWithNullApiKey(): void {
		$webRequestClientMock = $this->getWebRequestClientMock();

		$this->expectException( InvalidApiKeyException::class );
		$this->expectExceptionMessage( 'API key is missing' );

		PickupPointValidate::createWithValidApiKey( $webRequestClientMock, null );
	}

	public function testValidateOk(): void {
		$webRequestClientMock = $this->getWebRequestClientMock();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		$expectedResponse = json_encode(
			[
				'isValid' => true,
				'errors'  => [],
			]
		);
		$webRequestClientMock->method( 'post' )
			->willReturn( $expectedResponse );
		$validator = PickupPointValidate::createWithValidApiKey( $webRequestClientMock, 'dummyApiKey' );

		self::assertInstanceOf(
			PickupPointValidateResponse::class,
			$validator->validate( DummyFactory::getEmptyPickupPointValidateRequest() )
		);
	}

	public function testValidateFail(): void {
		$webRequestClientMock = $this->getWebRequestClientMock();
		$webRequestClientMock->method( 'post' )
			->willThrowException( new Exception( 'dummyException' ) );
		$validator = PickupPointValidate::createWithValidApiKey( $webRequestClientMock, 'dummyApiKey' );

		$this->expectException( RestException::class );
		$validator->validate( DummyFactory::getEmptyPickupPointValidateRequest() );
	}

	private function getWebRequestClientMock(): MockObject|IWebRequestClient {
		return $this->createMock( IWebRequestClient::class );
	}
}
