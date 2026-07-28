<?php

declare( strict_types=1 );

namespace Tests\Module\Order;

use Packetery\Core\Api\Soap\Client;
use Packetery\Core\Api\Soap\Response\PacketInfo;
use Packetery\Core\Entity\Order;
use Packetery\Core\Log\ILogger;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\ConsignPasswordSynchronizer;
use Packetery\Module\Order\Repository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConsignPasswordSynchronizerTest extends TestCase {

	private ConsignPasswordSynchronizer $synchronizer;
	private Client|MockObject $client;
	private ILogger|MockObject $logger;
	private Repository|MockObject $orderRepository;
	private OptionsProvider|MockObject $optionsProvider;
	private WcAdapter|MockObject $wcAdapter;

	private function createSynchronizer(): void {
		$this->client          = $this->createMock( Client::class );
		$this->logger          = $this->createMock( ILogger::class );
		$this->orderRepository = $this->createMock( Repository::class );
		$this->optionsProvider = $this->createMock( OptionsProvider::class );
		$this->wcAdapter       = $this->createMock( WcAdapter::class );

		$this->synchronizer = new ConsignPasswordSynchronizer(
			$this->client,
			$this->logger,
			$this->orderRepository,
			$this->optionsProvider,
			$this->wcAdapter
		);
	}

	public function testProcessByIdStoresFetchedPassword(): void {
		$this->createSynchronizer();
		$this->optionsProvider->method( 'isShowConsignPasswordForZBoxEnabled' )->willReturn( true );

		$order = $this->createMock( Order::class );
		$order->method( 'getPacketId' )->willReturn( 'packet-id' );
		$order->expects( $this->once() )->method( 'setConsignPassword' )->with( '123456789' );
		$this->orderRepository->method( 'getByIdWithValidCarrier' )->willReturn( $order );
		$this->orderRepository->expects( $this->once() )->method( 'save' )->with( $order );

		$response = $this->createMock( PacketInfo::class );
		$response->method( 'hasFault' )->willReturn( false );
		$response->method( 'getConsignPassword' )->willReturn( '123456789' );
		$this->client->method( 'packetInfo' )->willReturn( $response );

		$this->synchronizer->processById( '1' );
	}

	public function testProcessByIdLogsFaultAndDoesNotSave(): void {
		$this->createSynchronizer();
		$this->optionsProvider->method( 'isShowConsignPasswordForZBoxEnabled' )->willReturn( true );

		$order = $this->createMock( Order::class );
		$order->method( 'getPacketId' )->willReturn( 'packet-id' );
		$order->expects( $this->never() )->method( 'setConsignPassword' );
		$this->orderRepository->method( 'getByIdWithValidCarrier' )->willReturn( $order );
		$this->orderRepository->expects( $this->never() )->method( 'save' );

		$response = $this->createMock( PacketInfo::class );
		$response->method( 'hasFault' )->willReturn( true );
		$response->method( 'getFaultString' )->willReturn( 'Error message' );
		$response->expects( $this->never() )->method( 'getConsignPassword' );
		$this->client->method( 'packetInfo' )->willReturn( $response );

		$this->logger->expects( $this->once() )->method( 'add' );

		$this->synchronizer->processById( '1' );
	}

	public function testProcessByIdDoesNotSaveEmptyPassword(): void {
		$this->createSynchronizer();
		$this->optionsProvider->method( 'isShowConsignPasswordForZBoxEnabled' )->willReturn( true );

		$order = $this->createMock( Order::class );
		$order->method( 'getPacketId' )->willReturn( 'packet-id' );
		$order->expects( $this->never() )->method( 'setConsignPassword' );
		$this->orderRepository->method( 'getByIdWithValidCarrier' )->willReturn( $order );
		$this->orderRepository->expects( $this->never() )->method( 'save' );

		$response = $this->createMock( PacketInfo::class );
		$response->method( 'hasFault' )->willReturn( false );
		$response->method( 'getConsignPassword' )->willReturn( null );
		$this->client->method( 'packetInfo' )->willReturn( $response );

		$this->synchronizer->processById( '1' );
	}

	public function testProcessByIdSkippedWhenFeatureDisabled(): void {
		$this->createSynchronizer();
		$this->optionsProvider->method( 'isShowConsignPasswordForZBoxEnabled' )->willReturn( false );

		$this->orderRepository->expects( $this->never() )->method( 'getByIdWithValidCarrier' );
		$this->client->expects( $this->never() )->method( 'packetInfo' );

		$this->synchronizer->processById( '1' );
	}

	public function testScheduleSkippedWhenFeatureDisabled(): void {
		$this->createSynchronizer();
		$this->optionsProvider->method( 'isShowConsignPasswordForZBoxEnabled' )->willReturn( false );

		$order = $this->createMock( Order::class );
		$order->expects( $this->never() )->method( 'getPacketId' );
		$this->client->expects( $this->never() )->method( 'packetInfo' );
		$this->wcAdapter->expects( $this->never() )->method( 'asEnqueueAsyncAction' );

		$this->synchronizer->schedule( $order, true );
	}

	public function testScheduleImmediateDelegatesToProcessing(): void {
		$this->createSynchronizer();
		$this->optionsProvider->method( 'isShowConsignPasswordForZBoxEnabled' )->willReturn( true );

		$order = $this->createMock( Order::class );
		$order->method( 'getPacketId' )->willReturn( 'packet-id' );
		$order->method( 'getNumber' )->willReturn( '1' );
		$order->expects( $this->once() )->method( 'setConsignPassword' )->with( '123456789' );
		$this->orderRepository->method( 'getByIdWithValidCarrier' )->willReturn( $order );
		$this->orderRepository->expects( $this->once() )->method( 'save' )->with( $order );

		$response = $this->createMock( PacketInfo::class );
		$response->method( 'hasFault' )->willReturn( false );
		$response->method( 'getConsignPassword' )->willReturn( '123456789' );
		$this->client->expects( $this->once() )->method( 'packetInfo' )->willReturn( $response );
		$this->wcAdapter->expects( $this->never() )->method( 'asEnqueueAsyncAction' );

		$this->synchronizer->schedule( $order, true );
	}

	public function testScheduleEnqueuesAsyncActionWhenNotImmediate(): void {
		$this->createSynchronizer();
		$this->optionsProvider->method( 'isShowConsignPasswordForZBoxEnabled' )->willReturn( true );
		$this->wcAdapter->method( 'isActionSchedulerEnqueueAvailable' )->willReturn( true );

		$order = $this->createMock( Order::class );
		$order->method( 'getPacketId' )->willReturn( 'packet-id' );
		$order->method( 'getNumber' )->willReturn( '1' );

		$this->wcAdapter->expects( $this->once() )
			->method( 'asEnqueueAsyncAction' )
			->with( 'packetery_consign_password_fetch_hook', [ '1' ] );
		$this->client->expects( $this->never() )->method( 'packetInfo' );
		$this->orderRepository->expects( $this->never() )->method( 'getByIdWithValidCarrier' );

		$this->synchronizer->schedule( $order, false );
	}

	public function testScheduleFallsBackToImmediateWhenSchedulerUnavailable(): void {
		$this->createSynchronizer();
		$this->optionsProvider->method( 'isShowConsignPasswordForZBoxEnabled' )->willReturn( true );
		$this->wcAdapter->method( 'isActionSchedulerEnqueueAvailable' )->willReturn( false );

		$order = $this->createMock( Order::class );
		$order->method( 'getPacketId' )->willReturn( 'packet-id' );
		$order->method( 'getNumber' )->willReturn( '1' );
		$order->expects( $this->once() )->method( 'setConsignPassword' )->with( '123456789' );
		$this->orderRepository->method( 'getByIdWithValidCarrier' )->willReturn( $order );
		$this->orderRepository->expects( $this->once() )->method( 'save' )->with( $order );

		$response = $this->createMock( PacketInfo::class );
		$response->method( 'hasFault' )->willReturn( false );
		$response->method( 'getConsignPassword' )->willReturn( '123456789' );
		$this->client->expects( $this->once() )->method( 'packetInfo' )->willReturn( $response );
		$this->wcAdapter->expects( $this->never() )->method( 'asEnqueueAsyncAction' );

		$this->synchronizer->schedule( $order, false );
	}
}
