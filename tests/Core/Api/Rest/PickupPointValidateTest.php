<?php

declare( strict_types=1 );

namespace Core\Api\Rest;

use Exception;
use Packetery\Core\Api\Rest\IDownloader;
use Packetery\Core\Api\Rest\PickupPointValidate;
use Packetery\Core\Api\Rest\PickupPointValidateResponse;
use Packetery\Core\Api\Rest\RestException;
use PHPUnit\Framework\TestCase;
use Tests\DummyFactory;
use function json_encode;

class PickupPointValidateTest extends TestCase {

	public function testValidateOk() {
		$downloaderMock = $this->getDownloaderMock();
		$downloaderMock->method( 'post' )
		               ->willReturn( json_encode( [
			               'isValid' => true,
			               'errors'  => [],
		               ] ) );
		$validator = new PickupPointValidate( $downloaderMock, 'dummyApiKey' );

		$this->assertInstanceOf(
			PickupPointValidateResponse::class,
			$validator->validate( DummyFactory::getEmptyPickupPointValidateRequest() )
		);
	}

	public function testValidateFail() {
		$downloaderMock = $this->getDownloaderMock();
		$downloaderMock->method( 'post' )
		               ->willThrowException( new Exception( 'dummyException' ) );
		$validator = new PickupPointValidate( $downloaderMock, 'dummyApiKey' );

		$this->expectException( RestException::class );
		$validator->validate( DummyFactory::getEmptyPickupPointValidateRequest() );
	}

	private function getDownloaderMock() {
		return $this->getMockBuilder( IDownloader::class )
		            ->setMockClassName( 'DownloaderMock' )
		            ->getMock();
	}

}
