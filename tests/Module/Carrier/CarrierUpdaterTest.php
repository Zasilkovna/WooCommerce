<?php

declare(strict_types=1);

namespace Tests\Module\Tests\Carrier;

use DateTimeZone;
use Packetery\Module\Carrier\CarrierUpdater;
use Packetery\Module\Carrier\Downloader;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionNames;
use Packetery\Module\Transients;
use Packetery\Nette\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CarrierUpdaterTest extends TestCase {

	private Request|MockObject $httpRequestMock;
	private Downloader|MockObject $downloaderMock;
	private WpAdapter|MockObject $wpAdapterMock;

	private function createCarrierUpdater(): CarrierUpdater {
		$this->httpRequestMock = $this->createMock( Request::class );
		$this->downloaderMock  = $this->createMock( Downloader::class );
		$this->wpAdapterMock   = $this->createMock( WpAdapter::class );

		return new CarrierUpdater(
			$this->httpRequestMock,
			$this->downloaderMock,
			$this->wpAdapterMock
		);
	}

	public function testStartUpdateSetsTransientAndCallsSafeRedirect(): void {
		$redirectUrl = 'https://example.com';

		$carrierUpdater = $this->createCarrierUpdater();
		$this->httpRequestMock->method( 'getQuery' )
								->with( 'update_carriers' )
								->willReturn( '1' );

		$this->wpAdapterMock->expects( $this->once() )
							->method( 'setTransient' )
							->with( Transients::RUN_UPDATE_CARRIERS, true );

		$this->wpAdapterMock->expects( $this->once() )
							->method( 'safeRedirect' )
							->with( $redirectUrl )
							->willReturn( false );

		$carrierUpdater->startUpdate( $redirectUrl );
	}

	public function testRunUpdateShouldReturnUpdateParamsAndDeleteTransient(): void {
		$expectedResult      = [ 'some_result' ];
		$expectedResultClass = 'SuccessClass';

		$carrierUpdater = $this->createCarrierUpdater();
		$this->wpAdapterMock->method( 'getTransient' )
							->with( Transients::RUN_UPDATE_CARRIERS )
							->willReturn( true );
		$this->wpAdapterMock->expects( $this->once() )
							->method( 'deleteTransient' )
							->with( Transients::RUN_UPDATE_CARRIERS );

		$this->downloaderMock->method( 'run' )
							->willReturn( [ $expectedResult, $expectedResultClass ] );

		$result = $carrierUpdater->runUpdate();

		$this->assertEquals(
			[
				'result'      => $expectedResult,
				'resultClass' => $expectedResultClass,
			],
			$result
		);
	}

	public function testRunUpdateShouldReturnEmptyArrayWhenTransientNotSet(): void {
		$carrierUpdater = $this->createCarrierUpdater();
		$this->wpAdapterMock->method( 'getTransient' )
							->with( Transients::RUN_UPDATE_CARRIERS )
							->willReturn( false );

		$result = $carrierUpdater->runUpdate();

		$this->assertEquals( [], $result );
	}

	public function testGetLastUpdateShouldReturnFormattedDate(): void {
		$lastUpdate         = '2025-03-12T12:34:56+00:00';
		$expectedDateString = '12.03.2025 12:34';

		$carrierUpdater = $this->createCarrierUpdater();
		$this->wpAdapterMock->method( 'getOption' )
							->willReturnMap(
								[
									[ OptionNames::LAST_CARRIER_UPDATE, $lastUpdate ],
									[ 'date_format', 'd.m.Y' ],
									[ 'time_format', 'H:i' ],
								]
							);
		$timezoneMock = $this->createMock( DateTimeZone::class );
		$this->wpAdapterMock->method( 'timezone' )->willReturn( $timezoneMock );

		$result = $carrierUpdater->getLastUpdate();

		$this->assertEquals( $expectedDateString, $result );
	}

	public function testGetLastUpdateShouldReturnNullWhenNoLastUpdate(): void {
		$carrierUpdater = $this->createCarrierUpdater();
		$this->wpAdapterMock->method( 'getOption' )
							->with( OptionNames::LAST_CARRIER_UPDATE )
							->willReturn( false );

		$result = $carrierUpdater->getLastUpdate();

		$this->assertNull( $result );
	}
}
