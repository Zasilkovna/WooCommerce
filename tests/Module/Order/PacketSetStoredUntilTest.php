<?php

declare( strict_types=1 );

namespace Tests\Module\Order;

use Packetery\Core\Api\Soap\Client;
use Packetery\Core\Log\ILogger;
use Packetery\Core\Api\Soap\Response\PacketSetStoredUntil as ResponsePacketSetStoredUntil;
use Packetery\Module\Helper;
use Packetery\Module\MessageManager;
use Packetery\Module\Order\PacketSetStoredUntil;
use Packetery\Module\Order\Repository;
use Packetery\Nette\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class PacketSetStoredUntilTest extends TestCase {

	private Client|MockObject $client;
	private ILogger|MockObject $loggerMock;
	private Repository|MockObject $orderRepositoryMock;
	private Request|MockObject $requestMock;
	private MessageManager|MockObject $messageManagerMock;
	private Helper|MockObject $helper;

	public function setUp(): void {
		$this->client                       = $this->createMock( Client::class );
		$this->loggerMock                   = $this->createMock( ILogger::class );
		$this->orderRepositoryMock          = $this->createMock( Repository::class );
		$this->requestMock                  = $this->createMock( Request::class );
		$this->messageManagerMock           = $this->createMock( MessageManager::class );
		$this->helper                       = $this->createMock( Helper::class );
		$this->responsePacketSetStoredUntil = $this->createMock( ResponsePacketSetStoredUntil::class );

		$this->packetSetStoredUntil = new PacketSetStoredUntil(
			$this->client,
			$this->loggerMock,
			$this->orderRepositoryMock,
			$this->requestMock,
			$this->messageManagerMock,
			$this->helper,
		);
	}

	public function testSetStoredUntilWithoutFault(): void {
		$this->responsePacketSetStoredUntil->method( 'hasFault' )->willReturn( false );
		$this->loggerMock->expects( $this->once() )->method( 'add' );

		$this->client->expects( $this->once() )->method( 'packetSetStoredUntil' )->willReturn( $this->responsePacketSetStoredUntil );

		$result = $this->packetSetStoredUntil->setStoredUntil( DummyFactory::createOrderCzPp(), 'test', \DateTimeImmutable::createFromFormat( 'Y-m-d', '2024-12-24' ) );

		$this->assertNull( $result );
	}

	public function testSetStoredUntilWithFault(): void {
		$errorMessage = 'some nasty fault';

		$this->responsePacketSetStoredUntil->method( 'hasFault' )->willReturn( true );
		$this->responsePacketSetStoredUntil->method( 'getFaultString' )->willReturn( $errorMessage );
		$this->loggerMock->expects( $this->once() )->method( 'add' );

		$this->client->expects( $this->once() )->method( 'packetSetStoredUntil' )->willReturn( $this->responsePacketSetStoredUntil );

		$result = $this->packetSetStoredUntil->setStoredUntil( DummyFactory::createOrderCzPp(), 'test', \DateTimeImmutable::createFromFormat( 'Y-m-d', '2024-12-24' ) );

		$this->assertSame( $errorMessage, $result );
	}
}
