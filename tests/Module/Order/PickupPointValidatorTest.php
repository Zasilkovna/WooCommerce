<?php

declare(strict_types=1);

namespace Tests\Module\Order;

use Packetery\Core\Api\Rest\PickupPointValidateRequest;
use Packetery\Core\Api\Rest\PickupPointValidateResponse;
use Packetery\Core\Log\ILogger;
use Packetery\Core\Log\Record;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\PickupPointValidator;
use Packetery\Module\WebRequestClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PickupPointValidatorTest extends TestCase {

	private OptionsProvider&MockObject $optionsProviderMock;
	private ILogger&MockObject $loggerMock;
	private WebRequestClient&MockObject $webRequestClientMock;
	private WpAdapter&MockObject $wpAdapter;
	private WcAdapter&MockObject $wcAdapter;

	private function createPickupPointValidator(): PickupPointValidator {
		$this->optionsProviderMock  = $this->createMock( OptionsProvider::class );
		$this->loggerMock           = $this->createMock( ILogger::class );
		$this->webRequestClientMock = $this->createMock( WebRequestClient::class );
		$this->wpAdapter            = $this->createMock( WpAdapter::class );
		$this->wcAdapter            = $this->createMock( WcAdapter::class );

		return new PickupPointValidator(
			$this->optionsProviderMock,
			$this->loggerMock,
			$this->webRequestClientMock,
			$this->wpAdapter,
			$this->wcAdapter,
		);
	}

	public function testValidateReturnsErrorResponseWhenApiKeyIsInvalid(): void {
		$validator = $this->createPickupPointValidator();

		$this->optionsProviderMock
			->method( 'get_api_key' )
			->willReturn( null );

		$this->wpAdapter->method( '__' )->willReturn( 'dummyErrorTitle' );

		$requestMock = $this->createMock( PickupPointValidateRequest::class );

		$this->loggerMock
			->expects( $this->once() )
			->method( 'add' )
			->with(
				$this->callback(
					function ( Record $record ) {
						return $record->status === Record::STATUS_ERROR
							&& $record->action === Record::ACTION_PICKUP_POINT_VALIDATE;
					}
				)
			);

		$response = $validator->validate( $requestMock );

		$this->assertInstanceOf( PickupPointValidateResponse::class, $response );
		$this->assertEmpty( $response->getErrors() );
	}

	public function testValidateCallsValidateWhenApiKeyIsValid(): void {
		$validator = $this->createPickupPointValidator();

		$this->optionsProviderMock
			->method( 'get_api_key' )
			->willReturn( 'dummyApiKey' );

		$this->wpAdapter->method( '__' )->willReturn( 'dummyErrorTitle' );

		$this->webRequestClientMock
			->expects( $this->once() )
			->method( 'post' )
			->with(
				$this->callback(
					function ( string $url ) {
						return str_contains( $url, 'validate' );
					}
				),
				$this->anything()
			)
			->willReturn(
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
				json_encode(
					[
						'isValid' => true,
						'errors'  => [],
						'status'  => 200,
					]
				)
			);

		$requestMock = $this->createMock( PickupPointValidateRequest::class );

		$response = $validator->validate( $requestMock );

		$this->assertInstanceOf( PickupPointValidateResponse::class, $response );
		$this->assertEmpty( $response->getErrors() );
	}
}
