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
use Brain\Monkey;
use Brain\Monkey\Functions;

class PickupPointValidateTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function testValidateOk(): void {
		Functions\when('wp_json_encode')->alias('json_encode');
		$webRequestClientMock = $this->getWebRequestClientMock();
		$expectedResponse = wp_json_encode([
			'isValid' => true,
			'errors'  => [],
		]);
		$webRequestClientMock->method( 'post' )
			->willReturn($expectedResponse);
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
		return $this->createMock( WebRequestClient::class );
	}
}
