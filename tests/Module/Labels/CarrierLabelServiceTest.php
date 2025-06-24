<?php

declare(strict_types=1);

namespace Tests\Packetery\Module\Labels;

use Packetery\Core\Api\Soap\Client;
use Packetery\Core\Api\Soap\Request;
use Packetery\Core\Api\Soap\Response;
use Packetery\Core\Log;
use Packetery\Core\Log\ILogger;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Labels\CarrierLabelService;
use Packetery\Module\MessageManager;
use Packetery\Module\Order\Repository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CarrierLabelServiceTest extends TestCase {

	private Client|MockObject $soapApiClient;
	private MessageManager|MockObject $messageManager;
	private ILogger|MockObject $logger;
	private Repository|MockObject $orderRepository;
	private CarrierLabelService $carrierLabelService;

	private function createCarrierLabelService(): void {
		$this->soapApiClient   = $this->createMock( Client::class );
		$this->messageManager  = $this->createMock( MessageManager::class );
		$this->logger          = $this->createMock( ILogger::class );
		$this->orderRepository = $this->createMock( Repository::class );
		$wpAdapterMock         = $this->createMock( WpAdapter::class );

		$wpAdapterMock->method( '__' )
			->willReturnCallback(
				static fn( string $text ): string => $text
			);

		$this->carrierLabelService = new CarrierLabelService(
			$this->soapApiClient,
			$this->messageManager,
			$this->logger,
			$this->orderRepository,
			$wpAdapterMock
		);
	}

	/**
	 * @return array<string, array{
	 *     packetIds: array<string, string>,
	 *     existingOrders: array<string, array<string, string>>,
	 *     apiResponses: array<array<string, mixed>>,
	 *     expectedResult: array<string, array{packetId: string, courierNumber: string}>
	 * }>
	 */
	public static function provideDataForGetPacketIdsWithCourierNumbers(): array {
		$carrierNumberOnSuccess = 'CN123456';
		$successResponse        = [
			'class'            => Response\PacketCourierNumber::class,
			'hasFault'         => false,
			'hasWrongPassword' => false,
			'getNumber'        => $carrierNumberOnSuccess,
		];

		$wrongPasswordResponse = [
			'class'            => Response\PacketCourierNumber::class,
			'hasFault'         => true,
			'hasWrongPassword' => true,
			'getFaultString'   => 'Wrong password',
		];

		$generalErrorResponse = [
			'class'            => Response\PacketCourierNumber::class,
			'hasFault'         => true,
			'hasWrongPassword' => false,
			'getFaultString'   => 'General error',
		];

		return [
			'existing_carrier_numbers' => [
				'packetIds'          => [
					'1' => 'P123',
					'2' => 'P456',
				],
				'existingOrders'     => [
					'1' => [ 'carrierNumber' => 'CN123' ],
					'2' => [ 'carrierNumber' => 'CN456' ],
				],
				'apiResponseConfigs' => [],
				'expectedResult'     => [
					'1' => [
						'packetId'      => 'P123',
						'courierNumber' => 'CN123',
					],
					'2' => [
						'packetId'      => 'P456',
						'courierNumber' => 'CN456',
					],
				],
			],
			'api_success'              => [
				'packetIds'          => [ '1' => 'P123' ],
				'existingOrders'     => [],
				'apiResponseConfigs' => [ $successResponse ],
				'expectedResult'     => [
					'1' => [
						'packetId'      => 'P123',
						'courierNumber' => $carrierNumberOnSuccess,
					],
				],
			],
			'wrong_password_error'     => [
				'packetIds'          => [ '1' => 'P123' ],
				'existingOrders'     => [],
				'apiResponseConfigs' => [ $wrongPasswordResponse ],
				'expectedResult'     => [],
			],
			'general_error'            => [
				'packetIds'          => [
					'1' => 'P123',
					'2' => 'P456',
				],
				'existingOrders'     => [],
				'apiResponseConfigs' => [ $generalErrorResponse, $successResponse ],
				'expectedResult'     => [
					'2' => [
						'packetId'      => 'P456',
						'courierNumber' => $carrierNumberOnSuccess,
					],
				],
			],
			'mixed_scenario'           => [
				'packetIds'          => [
					'1' => 'P123',
					'2' => 'P456',
					'3' => 'P789',
				],
				'existingOrders'     => [
					'1' => [ 'carrierNumber' => 'CN123' ],
				],
				'apiResponseConfigs' => [ $generalErrorResponse, $successResponse ],
				'expectedResult'     => [
					'1' => [
						'packetId'      => 'P123',
						'courierNumber' => 'CN123',
					],
					'3' => [
						'packetId'      => 'P789',
						'courierNumber' => $carrierNumberOnSuccess,
					],
				],
			],
		];
	}

	#[DataProvider( 'provideDataForGetPacketIdsWithCourierNumbers' )]
	public function testGetPacketIdsWithCourierNumbers(
		array $packetIds,
		array $existingOrders,
		array $apiResponseConfigs,
		array $expectedResult
	): void {
		$this->createCarrierLabelService();

		$orderRepositoryMap = [];
		foreach ( $existingOrders as $orderId => $orderData ) {
			$order = $this->createMock( \Packetery\Core\Entity\Order::class );
			$order->method( 'getCarrierNumber' )->willReturn( $orderData['carrierNumber'] ?? null );
			$orderRepositoryMap[] = [ (int) $orderId, $order ];
		}

		if ( ! empty( $orderRepositoryMap ) ) {
			$this->orderRepository->method( 'getByIdWithValidCarrier' )
				->willReturnMap( $orderRepositoryMap );
		}

		$apiResponses = [];
		foreach ( $apiResponseConfigs as $config ) {
			$response = $this->createMock( $config['class'] );
			foreach ( $config as $method => $returnValue ) {
				if ( $method !== 'class' ) {
					$response->method( $method )->willReturn( $returnValue );
				}
			}
			$apiResponses[] = $response;
		}

		$packetIdToResponseMap = [];
		$apiCallIndex          = 0;
		foreach ( $packetIds as $orderId => $packetId ) {
			if ( isset( $existingOrders[ $orderId ]['carrierNumber'] ) ) {
				continue;
			}

			if ( isset( $apiResponses[ $apiCallIndex ] ) ) {
				$response = $apiResponses[ $apiCallIndex ];

				if ( $response->hasWrongPassword() ) {
					$this->messageManager->expects( $this->once() )
						->method( 'flash_message' )
						->with( $this->isType( 'string' ), MessageManager::TYPE_ERROR );
				}

				$packetIdToResponseMap[ $packetId ] = $response;
				$apiCallIndex++;
			}
		}

		$this->soapApiClient->method( 'packetCourierNumber' )
			->willReturnCallback(
				function ( Request\PacketCourierNumber $request ) use ( $packetIdToResponseMap ) {
					$packetId = $request->getPacketId();
					if ( isset( $packetIdToResponseMap[ $packetId ] ) ) {
						return $packetIdToResponseMap[ $packetId ];
					}
					$this->fail( "Unexpected packet ID: $packetId" );
				}
			);

		if ( ! empty( $apiResponses ) ) {
			$wcOrder = $this->createMock( \WC_Order::class );
			$wcOrder->method( 'add_order_note' )->willReturn( true );
			$wcOrder->method( 'save' )->willReturn( true );
			$this->orderRepository->method( 'getWcOrderById' )->willReturn( $wcOrder );

			$order = $this->createMock( \Packetery\Core\Entity\Order::class );
			$this->orderRepository->method( 'getByWcOrderWithValidCarrier' )->willReturn( $order );
		}

		$result = $this->carrierLabelService->getPacketIdsWithCourierNumbers( $packetIds );
		$this->assertEquals( $expectedResult, $result );
	}

	/**
	 * @return array<string, array{
	 *     hasOrder: bool,
	 *     carrierNumber: ?string,
	 *     expectedResult: ?array{packetId: string, courierNumber: string}
	 * }>
	 */
	public static function provideDataForGetExistingCarrierNumber(): array {
		return [
			'order_with_carrier_number'    => [
				'hasOrder'       => true,
				'carrierNumber'  => 'CN123',
				'expectedResult' => [
					'packetId'      => 'P123',
					'courierNumber' => 'CN123',
				],
			],
			'order_without_carrier_number' => [
				'hasOrder'       => true,
				'carrierNumber'  => null,
				'expectedResult' => null,
			],
			'no_order'                     => [
				'hasOrder'       => false,
				'carrierNumber'  => null,
				'expectedResult' => null,
			],
		];
	}

	#[DataProvider( 'provideDataForGetExistingCarrierNumber' )]
	public function testGetExistingCarrierNumber(
		bool $hasOrder,
		?string $carrierNumber,
		?array $expectedResult
	): void {
		$this->createCarrierLabelService();

		$order = null;
		if ( $hasOrder ) {
			$order = $this->createMock( \Packetery\Core\Entity\Order::class );
			$order->method( 'getCarrierNumber' )
				->willReturn( $carrierNumber );
		}

		$this->orderRepository->method( 'getByIdWithValidCarrier' )
			->willReturn( $order );

		$method = new ReflectionMethod( CarrierLabelService::class, 'getExistingCarrierNumber' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->carrierLabelService, 123, 'P123' );
		$this->assertEquals( $expectedResult, $result );
	}

	/**
	 * @return array<string, array{
	 *     hasWrongPassword: bool,
	 *     faultString: string,
	 *     hasWcOrder: bool,
	 *     hasOrder: bool,
	 *     expectedResult: bool
	 * }>
	 */
	public static function provideDataForHandleApiError(): array {
		return [
			'wrong_password'              => [
				'hasWrongPassword' => true,
				'faultString'      => 'Wrong password',
				'hasWcOrder'       => false,
				'hasOrder'         => false,
				'expectedResult'   => false,
			],
			'general_error_with_order'    => [
				'hasWrongPassword' => false,
				'faultString'      => 'General error',
				'hasWcOrder'       => true,
				'hasOrder'         => true,
				'expectedResult'   => true,
			],
			'general_error_without_order' => [
				'hasWrongPassword' => false,
				'faultString'      => 'General error',
				'hasWcOrder'       => false,
				'hasOrder'         => false,
				'expectedResult'   => true,
			],
		];
	}

	#[DataProvider( 'provideDataForHandleApiError' )]
	public function testHandleApiError(
		bool $hasWrongPassword,
		string $faultString,
		bool $hasWcOrder,
		bool $hasOrder,
		bool $expectedResult
	): void {
		$this->createCarrierLabelService();

		$request  = $this->createMock( Request\PacketCourierNumber::class );
		$response = $this->createMock( Response\PacketCourierNumber::class );

		$response->method( 'hasWrongPassword' )->willReturn( $hasWrongPassword );
		$response->method( 'getFaultString' )->willReturn( $faultString );

		$wcOrder = null;
		if ( $hasWcOrder ) {
			$wcOrder = $this->createMock( \WC_Order::class );
			$wcOrder->method( 'add_order_note' )->willReturn( true );
			$wcOrder->method( 'save' )->willReturn( true );
		}

		$order = null;
		if ( $hasOrder ) {
			$order = $this->createMock( \Packetery\Core\Entity\Order::class );
			$order->expects( $this->once() )->method( 'updateApiErrorMessage' );
		}

		$this->orderRepository->method( 'getWcOrderById' )->willReturn( $wcOrder );
		$this->orderRepository->method( 'getByWcOrderWithValidCarrier' )->willReturn( $order );

		if ( $hasWrongPassword ) {
			$this->messageManager->expects( $this->once() )
				->method( 'flash_message' )
				->with( $this->isType( 'string' ), MessageManager::TYPE_ERROR );
		} else {
			$this->messageManager->expects( $this->never() )
				->method( 'flash_message' );
		}

		$method = new ReflectionMethod( CarrierLabelService::class, 'handleApiError' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->carrierLabelService, $request, $response, 123, 'P123' );
		$this->assertEquals( $expectedResult, $result );
	}

	/**
	 * @return array<string, array{
	 *     packetId: string,
	 *     faultString: string,
	 *     orderId: int
	 * }>
	 */
	public static function provideDataForLogError(): array {
		return [
			'basic_error'     => [
				'packetId'    => 'P123',
				'faultString' => 'Error message',
				'orderId'     => 123,
			],
			'different_error' => [
				'packetId'    => 'P456',
				'faultString' => 'Another error',
				'orderId'     => 456,
			],
		];
	}

	#[DataProvider( 'provideDataForLogError' )]
	public function testLogError(
		string $packetId,
		string $faultString,
		int $orderId
	): void {
		$this->createCarrierLabelService();

		$request  = $this->createMock( Request\PacketCourierNumber::class );
		$response = $this->createMock( Response\PacketCourierNumber::class );

		$request->method( 'getPacketId' )->willReturn( $packetId );
		$response->method( 'getFaultString' )->willReturn( $faultString );

		$this->logger->expects( $this->once() )
			->method( 'add' )
			->with(
				$this->callback(
					fn( $record ) => $record instanceof Log\Record
						&& $record->action === Log\Record::ACTION_CARRIER_NUMBER_RETRIEVING
						&& $record->status === Log\Record::STATUS_ERROR
						&& $record->params['packetId'] === $packetId
						&& $record->params['errorMessage'] === $faultString
						&& $record->orderId === $orderId
				)
			);

		$method = new ReflectionMethod( CarrierLabelService::class, 'logError' );
		$method->setAccessible( true );

		$method->invoke( $this->carrierLabelService, $request, $response, $orderId );
	}

	/**
	 * @return array<string, array{
	 *     responseNumber: string,
	 *     packetId: string,
	 *     hasWcOrder: bool,
	 *     hasOrder: bool,
	 *     expectedResult: array{packetId: string, courierNumber: string}
	 * }>
	 */
	public static function provideDataForHandleApiSuccess(): array {
		return [
			'with_order'    => [
				'responseNumber' => 'CN123',
				'packetId'       => 'P123',
				'hasWcOrder'     => true,
				'hasOrder'       => true,
				'expectedResult' => [
					'packetId'      => 'P123',
					'courierNumber' => 'CN123',
				],
			],
			'without_order' => [
				'responseNumber' => 'CN456',
				'packetId'       => 'P456',
				'hasWcOrder'     => false,
				'hasOrder'       => false,
				'expectedResult' => [
					'packetId'      => 'P456',
					'courierNumber' => 'CN456',
				],
			],
		];
	}

	#[DataProvider( 'provideDataForHandleApiSuccess' )]
	public function testHandleApiSuccess(
		string $responseNumber,
		string $packetId,
		bool $hasWcOrder,
		bool $hasOrder,
		array $expectedResult
	): void {
		$this->createCarrierLabelService();

		$response = $this->createMock( Response\PacketCourierNumber::class );

		$wcOrder = null;
		if ( $hasWcOrder ) {
			$wcOrder = $this->createMock( \WC_Order::class );
			$wcOrder->expects( $this->once() )
				->method( 'add_order_note' );
			$wcOrder->expects( $this->once() )
				->method( 'save' );
		}

		$order = null;
		if ( $hasOrder ) {
			$order = $this->createMock( \Packetery\Core\Entity\Order::class );
			$order->expects( $this->once() )
				->method( 'setCarrierNumber' )
				->with( $responseNumber );

			$this->orderRepository->expects( $this->once() )
				->method( 'save' )
				->with( $order );
		}

		$response->method( 'getNumber' )->willReturn( $responseNumber );
		$this->orderRepository->method( 'getWcOrderById' )->willReturn( $wcOrder );
		$this->orderRepository->method( 'getByWcOrderWithValidCarrier' )->willReturn( $order );

		$method = new ReflectionMethod( CarrierLabelService::class, 'handleApiSuccess' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->carrierLabelService, $response, 123, $packetId );
		$this->assertEquals( $expectedResult, $result );
	}
}
