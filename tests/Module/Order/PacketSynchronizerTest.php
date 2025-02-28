<?php

declare( strict_types=1 );

namespace Tests\Module\Order;

use Packetery\Core\Api\Soap\Client;
use Packetery\Core\Api\Soap\Response\PacketStatus;
use Packetery\Core\Entity\Order;
use Packetery\Core\Log\ILogger;
use Packetery\Module\Exception\InvalidPasswordException;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\PacketSynchronizer;
use Packetery\Module\Order\Repository;
use Packetery\Module\Order\WcOrderActions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class PacketSynchronizerTest extends TestCase {

	private PacketSynchronizer $packetSynchronizer;
	private Client|MockObject $client;

	/**
	 * @var (object&MockObject)|OptionsProvider|(OptionsProvider&object&MockObject)|(OptionsProvider&MockObject)|MockObject
	 */
	private MockObject|OptionsProvider $provider;

	private function createPacketSynchronize(): void {
		$this->client    = $this->createMock( Client::class );
		$logger          = $this->createMock( ILogger::class );
		$this->provider  = $this->createMock( OptionsProvider::class );
		$orderRepository = $this->createMock( Repository::class );
		$wcOrderActions  = $this->createMock( WcOrderActions::class );
		$wcAdapter       = $this->createMock( WcAdapter::class );

		$this->packetSynchronizer = new PacketSynchronizer(
			$this->client,
			$logger,
			$this->provider,
			$orderRepository,
			$wcOrderActions,
			$wcAdapter,
		);
	}

	public function testSuccessfulSynchronization(): void {
		$this->createPacketSynchronize();
		$storedUntil  = new \DateTimeImmutable( 'now' );
		$packetStatus = 'some packet status';
		$order        = DummyFactory::createOrderCzPp();
		$order->setNumber( 'some number' );
		$order->setPacketId( 'some packet id' );

		$response = $this->createMock( PacketStatus::class );
		$response->method( 'hasFault' )->willReturn( false );
		$response->method( 'getStoredUntil' )->willReturn( $storedUntil );
		$response->method( 'getCodeText' )->willReturn( $packetStatus );

		$this->client->method( 'packetStatus' )->willReturn( $response );

		$this->packetSynchronizer->syncStatus( $order );

		$this->assertSame( $storedUntil, $order->getStoredUntil() );
		$this->assertSame( $packetStatus, $order->getPacketStatus() );
	}

	public function testSynchronizationHasFault(): void {
		$this->createPacketSynchronize();
		$order = $this->createMock( Order::class );
		$order->method( 'getPacketId' )->willReturn( '123456' );
		$order->method( 'getNumber' )->willReturn( '123456' );

		$response = $this->createMock( PacketStatus::class );
		$response->method( 'hasFault' )->willReturn( true );
		$response->method( 'getFaultString' )->willReturn( 'Error message' );
		$response->expects( $this->never() )->method( 'getStoredUntil' );
		$response->expects( $this->never() )->method( 'getCodeText' );

		$this->client->method( 'packetStatus' )->willReturn( $response );

		$this->packetSynchronizer->syncStatus( $order );

		$this->assertTrue( true ); // No exceptions thrown.
	}

	public function testSynchronizationHasWrongPassword(): void {
		$this->createPacketSynchronize();
		$order = $this->createMock( Order::class );
		$order->method( 'getPacketId' )->willReturn( '123456' );
		$order->method( 'getNumber' )->willReturn( '123456' );

		$response = $this->createMock( PacketStatus::class );
		$response->method( 'hasFault' )->willReturn( true );
		$response->method( 'hasWrongPassword' )->willReturn( true );
		$response->expects( $this->never() )->method( 'getStoredUntil' );
		$response->expects( $this->never() )->method( 'getCodeText' );

		$this->client->method( 'packetStatus' )->willReturn( $response );

		$this->expectException( InvalidPasswordException::class );
		$this->packetSynchronizer->syncStatus( $order );
	}
}
