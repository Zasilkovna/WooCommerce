<?php

declare( strict_types=1 );

namespace Tests\Module\Order;

use Packetery\Core\Api\Soap\Client;
use Packetery\Core\Api\Soap\Response\PacketSetStoredUntil as ResponsePacketSetStoredUntil;
use Packetery\Core\CoreHelper;
use Packetery\Core\Log\ILogger;
use Packetery\Module\Order\PacketSetStoredUntil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class PacketSetStoredUntilTest extends TestCase {

	private Client|MockObject $client;
	private ILogger|MockObject $loggerMock;
	private ResponsePacketSetStoredUntil|MockObject $responsePacketSetStoredUntil;
	private PacketSetStoredUntil $packetSetStoredUntil;

	public function createPacketStoredUntil(): void {
		$this->client                       = $this->createMock( Client::class );
		$this->loggerMock                   = $this->createMock( ILogger::class );
		$this->responsePacketSetStoredUntil = $this->createMock( ResponsePacketSetStoredUntil::class );

		$this->packetSetStoredUntil = new PacketSetStoredUntil(
			$this->client,
			$this->loggerMock,
			new CoreHelper( 'dummyUrl' ),
		);
	}

	public function testSetStoredUntilWithoutFault(): void {
		$this->createPacketStoredUntil();
		$this->responsePacketSetStoredUntil->method( 'hasFault' )->willReturn( false );
		$this->loggerMock->expects( $this->once() )->method( 'add' );

		$this->client->expects( $this->once() )->method( 'packetSetStoredUntil' )->willReturn( $this->responsePacketSetStoredUntil );

		$result = $this->packetSetStoredUntil->setStoredUntil( DummyFactory::createOrderCzPp(), 'test', \DateTimeImmutable::createFromFormat( 'Y-m-d', '2024-12-24' ) );

		$this->assertNull( $result );
	}

	public function testSetStoredUntilWithFault(): void {
		$this->createPacketStoredUntil();
		$errorMessage = 'some nasty fault';

		$this->responsePacketSetStoredUntil->method( 'hasFault' )->willReturn( true );
		$this->responsePacketSetStoredUntil->method( 'getFaultString' )->willReturn( $errorMessage );
		$this->loggerMock->expects( $this->once() )->method( 'add' );

		$this->client->expects( $this->once() )->method( 'packetSetStoredUntil' )->willReturn( $this->responsePacketSetStoredUntil );

		$result = $this->packetSetStoredUntil->setStoredUntil( DummyFactory::createOrderCzPp(), 'test', \DateTimeImmutable::createFromFormat( 'Y-m-d', '2024-12-24' ) );

		$this->assertSame( $errorMessage, $result );
	}
}
