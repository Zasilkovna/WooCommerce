<?php

namespace Tests\Module\Order;

use Packetery\Core\Api\Soap\Client;
use Packetery\Core\Api\Soap\Response\PacketsCourierLabelsPdf;
use Packetery\Core\Api\Soap\Response\PacketsLabelsPdf;
use Packetery\Core\Log\ILogger;
use Packetery\Latte\Engine;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Labels\CarrierLabelService;
use Packetery\Module\Labels\LabelPrintParametersService;
use Packetery\Module\MessageManager;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\LabelPrint;
use Packetery\Module\Order\PacketActionsCommonLogic;
use Packetery\Module\Order\Repository;
use Packetery\Nette\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class LabelPrintTest extends TestCase {
	private Client&MockObject $soapApiClientMock;
	private MessageManager&MockObject $messageManagerMock;
	private CarrierLabelService&MockObject $carrierLabelServiceMock;

	protected function createLabelPrintMock(): LabelPrint {
		$latteEngineMock                 = $this->createMock( Engine::class );
		$optionsProviderMock             = $this->createMock( OptionsProvider::class );
		$httpRequestMock                 = $this->createMock( Request::class );
		$this->soapApiClientMock         = $this->createMock( Client::class );
		$this->messageManagerMock        = $this->createMock( MessageManager::class );
		$loggerMock                      = $this->createMock( ILogger::class );
		$orderRepositoryMock             = $this->createMock( Repository::class );
		$packetActionsCommonLogicMock    = $this->createMock( PacketActionsCommonLogic::class );
		$moduleHelperMock                = $this->createMock( ModuleHelper::class );
		$wpAdapterMock                   = $this->createMock( WpAdapter::class );
		$this->carrierLabelServiceMock   = $this->createMock( CarrierLabelService::class );
		$labelPrintParametersServiceMock = $this->createMock( LabelPrintParametersService::class );

		$wpAdapterMock
			->method( '__' )
			->willReturnCallback(
				function ( string $msg ) {
					return $msg;
				}
			);

		return new LabelPrint(
			$latteEngineMock,
			$optionsProviderMock,
			$httpRequestMock,
			$this->soapApiClientMock,
			$this->messageManagerMock,
			$loggerMock,
			$orderRepositoryMock,
			$packetActionsCommonLogicMock,
			$moduleHelperMock,
			$wpAdapterMock,
			$this->carrierLabelServiceMock,
			$labelPrintParametersServiceMock,
		);
	}

	public static function getResponseTestProvider(): array {
		return [
			'packeta labels'                     => [
				'isCarrierLabels'        => false,
				'packetIds'              => [ '100' => 'PACKET_1' ],
				'fallbackToPacketaLabel' => false,
				'idParam'                => null,
				'offset'                 => 0,
				'expect'                 => [
					'packetaLabelsCalled' => 1,
					'carrierLabelsCalled' => 0,
					'flashMessageCount'   => 0,
					'resultClass'         => PacketsLabelsPdf::class,
				],
			],
			'carrier empty no fallback'          => [
				'isCarrierLabels'        => true,
				'packetIds'              => [ '100' => 'PACKET_1' ],
				'fallbackToPacketaLabel' => false,
				'idParam'                => 123,
				'offset'                 => 2,
				'expect'                 => [
					'packetaLabelsCalled' => 0,
					'carrierLabelsCalled' => 0,
					'flashMessageCount'   => 1,
					'resultClass'         => null,
					'carrier_numbers'     => [],
				],
			],
			'carrier empty with fallback'        => [
				'isCarrierLabels'        => true,
				'packetIds'              => [ '100' => 'PACKET_1' ],
				'fallbackToPacketaLabel' => true,
				'idParam'                => null,
				'offset'                 => 1,
				'expect'                 => [
					'packetaLabelsCalled' => 1,
					'carrierLabelsCalled' => 1,
					'flashMessageCount'   => 0,
					'resultClass'         => PacketsLabelsPdf::class,
					'carrier_numbers'     => [],
					'carrierFault'        => true,
				],
			],
			'carrier success no fallback needed' => [
				'isCarrierLabels'        => true,
				'packetIds'              => [ '100' => 'PACKET_1' ],
				'fallbackToPacketaLabel' => true,
				'idParam'                => null,
				'offset'                 => 5,
				'expect'                 => [
					'packetaLabelsCalled' => 0,
					'carrierLabelsCalled' => 1,
					'flashMessageCount'   => 0,
					'resultClass'         => PacketsCourierLabelsPdf::class,
					'carrier_numbers'     => [ '100' => 'COURIER_100' ],
					'carrierFault'        => false,
				],
			],
		];
	}

	/**
	 * @dataProvider getResponseTestProvider
	 */
	public function testGetResponse(
		bool $isCarrierLabels,
		array $packetIds,
		bool $fallbackToPacketaLabel,
		?int $idParam,
		int $offset,
		array $expect
	): void {
		$labelPrint = $this->createLabelPrintMock();

		if ( array_key_exists( 'carrier_numbers', $expect ) ) {
			$this->carrierLabelServiceMock->method( 'getPacketIdsWithCourierNumbers' )
				->with( $packetIds )
				->willReturn( $expect['carrier_numbers'] );
		} else {
			$this->carrierLabelServiceMock->method( 'getPacketIdsWithCourierNumbers' )
				->with( $packetIds )
				->willReturn( [ '100' => 'COURIER_100' ] );
		}

		if ( ( $expect['flashMessageCount'] ?? 0 ) > 0 ) {
			$this->messageManagerMock
				->expects( $this->once() )
				->method( 'flash_message' );
		} else {
			$this->messageManagerMock
				->expects( $this->never() )
				->method( 'flash_message' );
		}

		$carrierCalledTimes = $expect['carrierLabelsCalled'] ?? 0;
		if ( $carrierCalledTimes > 0 ) {
			$carrierResponse = new PacketsCourierLabelsPdf();
			if ( ( $expect['carrierFault'] ?? false ) === true ) {
				$carrierResponse->setFault( 'SomeFault' );
			}
			$this->soapApiClientMock->expects( $this->once() )
				->method( 'packetsCarrierLabelsPdf' )
				->with( $this->isInstanceOf( \Packetery\Core\Api\Soap\Request\PacketsCourierLabelsPdf::class ) )
				->willReturn( $carrierResponse );
		} else {
			$this->soapApiClientMock->expects( $this->never() )
				->method( 'packetsCarrierLabelsPdf' );
		}

		$packetaCalledTimes = $expect['packetaLabelsCalled'] ?? 0;
		if ( $packetaCalledTimes > 0 ) {
			$packetaResponse = new PacketsLabelsPdf();
			$this->soapApiClientMock->expects( $this->once() )
				->method( 'packetsLabelsPdf' )
				->with( $this->isInstanceOf( \Packetery\Core\Api\Soap\Request\PacketsLabelsPdf::class ) )
				->willReturn( $packetaResponse );
		} else {
			$this->soapApiClientMock->expects( $this->never() )
				->method( 'packetsLabelsPdf' );
		}

		$reflection  = new ReflectionClass( LabelPrint::class );
		$getResponse = $reflection->getMethod( 'getResponse' );
		$getResponse->setAccessible( true );
		$result = $getResponse->invoke( $labelPrint, $isCarrierLabels, $packetIds, $fallbackToPacketaLabel, $idParam, $offset );

		if ( $expect['resultClass'] === null ) {
			$this->assertNull( $result );
		} else {
			$this->assertInstanceOf( $expect['resultClass'], $result );
		}
	}
}
