<?php

declare( strict_types=1 );

namespace Tests\Packetery\Module\Returns;

use Packetery\Core\Api\Soap;
use Packetery\Core\Api\Soap\Response\CreatePacketClaimWithPassword as CreateResponse;
use Packetery\Core\CoreHelper;
use Packetery\Core\Entity\Order;
use Packetery\Core\Entity\PacketReturn;
use Packetery\Core\Log\ILogger;
use Packetery\Core\Log\Record;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Order\Repository as OrderRepository;
use Packetery\Module\Returns\Repository as ReturnRepository;
use Packetery\Module\Returns\ReturnService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class ReturnServiceTest extends TestCase {

	private Soap\Client|MockObject $soapApiClient;
	private ILogger|MockObject $logger;
	private OrderRepository|MockObject $orderRepository;
	private ReturnRepository|MockObject $returnRepository;

	private function createService(): ReturnService {
		$this->soapApiClient    = $this->createMock( Soap\Client::class );
		$this->logger           = $this->createMock( ILogger::class );
		$this->orderRepository  = $this->createMock( OrderRepository::class );
		$this->returnRepository = $this->createMock( ReturnRepository::class );

		$coreHelper = $this->createMock( CoreHelper::class );
		$coreHelper->method( 'getTrackingUrl' )->willReturn( 'https://tracking.packeta.com/Z9000000123' );

		$wpAdapter = $this->createMock( WpAdapter::class );
		$wpAdapter->method( '__' )->willReturnArgument( 0 );

		return new ReturnService(
			$this->soapApiClient,
			$this->logger,
			$this->orderRepository,
			$this->returnRepository,
			$coreHelper,
			$this->createMock( ModuleHelper::class ),
			$wpAdapter
		);
	}

	private function createOrder(): Order {
		$order = DummyFactory::createOrderCzPp();
		$order->setCurrency( 'CZK' );

		return $order;
	}

	private function createResponse( bool $hasFault ): CreateResponse|MockObject {
		$response = $this->createMock( CreateResponse::class );
		$response->method( 'hasFault' )->willReturn( $hasFault );
		$response->method( 'getFaultString' )->willReturn( 'fault' );
		$response->method( 'getValidationErrors' )->willReturn( [] );
		$response->method( 'getId' )->willReturn( '9000000123' );
		$response->method( 'getPassword' )->willReturn( 'pwd' );

		return $response;
	}

	public function testFaultDoesNotPersistAnything(): void {
		$service = $this->createService();
		$this->soapApiClient->method( 'createPacketClaimWithPassword' )->willReturn( $this->createResponse( true ) );

		$this->orderRepository->expects( self::never() )->method( 'save' );
		$this->returnRepository->expects( self::never() )->method( 'save' );
		$this->logger->expects( self::once() )->method( 'add' )
			->with( self::callback( static fn ( Record $record ): bool => $record->status === Record::STATUS_ERROR ) );

		$result = $service->createReturn( $this->createOrder(), PacketReturn::SOURCE_ADMIN );

		self::assertTrue( $result->hasFault() );
		self::assertFalse( $result->isOrderSaved() );
		self::assertNull( $result->getPacketReturn() );
	}

	public function testSuccessPersistsClaimAndReturnRow(): void {
		$service = $this->createService();
		$this->soapApiClient->method( 'createPacketClaimWithPassword' )->willReturn( $this->createResponse( false ) );
		$this->orderRepository->method( 'save' )->willReturn( 1 );
		$this->orderRepository->method( 'getWcOrderById' )->willReturn( null );

		$savedReturn = null;
		$this->returnRepository->expects( self::once() )->method( 'save' )
			->willReturnCallback(
				static function ( PacketReturn $packetReturn ) use ( &$savedReturn ): int {
					$savedReturn = $packetReturn;

					return 1;
				}
			);

		$order  = $this->createOrder();
		$result = $service->createReturn( $order, PacketReturn::SOURCE_ADMIN );

		self::assertFalse( $result->hasFault() );
		self::assertTrue( $result->isOrderSaved() );
		self::assertNotNull( $result->getPacketReturn() );

		self::assertSame( '9000000123', $order->getPacketClaimId() );
		self::assertSame( 'pwd', $order->getPacketClaimPassword() );

		self::assertInstanceOf( PacketReturn::class, $savedReturn );
		self::assertSame( PacketReturn::STATUS_CREATED, $savedReturn->getStatus() );
		self::assertSame( PacketReturn::SOURCE_ADMIN, $savedReturn->getSource() );
		self::assertSame( '9000000123', $savedReturn->getPacketClaimId() );
		self::assertSame( 'pwd', $savedReturn->getPacketClaimPassword() );
		self::assertSame( $order->getEmail(), $savedReturn->getEmail() );
		self::assertSame( $order->getPhone(), $savedReturn->getPhone() );
		self::assertSame( (string) $order->getNumber(), $savedReturn->getOrderId() );
	}

	public function testSuccessWithOrderSaveFailureStillCreatesReturn(): void {
		$service = $this->createService();
		$this->soapApiClient->method( 'createPacketClaimWithPassword' )->willReturn( $this->createResponse( false ) );
		$this->orderRepository->method( 'save' )->willReturn( false );
		$this->orderRepository->method( 'getWcOrderById' )->willReturn( null );
		$this->returnRepository->expects( self::once() )->method( 'save' )->willReturn( 1 );

		$result = $service->createReturn( $this->createOrder(), PacketReturn::SOURCE_ADMIN );

		self::assertFalse( $result->hasFault() );
		self::assertFalse( $result->isOrderSaved() );
		self::assertNotNull( $result->getPacketReturn() );
	}
}
