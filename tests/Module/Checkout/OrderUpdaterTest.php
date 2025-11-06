<?php

declare(strict_types=1);

namespace Tests\Module\Checkout;

use Packetery\Core\Entity;
use Packetery\Module\Carrier;
use Packetery\Module\Checkout\CartService;
use Packetery\Module\Checkout\CheckoutService;
use Packetery\Module\Checkout\CheckoutStorage;
use Packetery\Module\Checkout\OrderUpdater;
use Packetery\Module\EntityFactory\SizeFactory;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order;
use Packetery\Module\Order\Attribute;
use Packetery\Module\Order\AttributeMapper;
use Packetery\Module\Order\PacketAutoSubmitter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;
use WC_Order;

class OrderUpdaterTest extends TestCase {
	private Order\Repository&MockObject $orderRepositoryMock;
	private CheckoutService&MockObject $checkoutServiceMock;
	private MockObject&CheckoutStorage $checkoutStorageMock;
	private OptionsProvider&MockObject $optionsProviderMock;
	private AttributeMapper&MockObject $attributeMapperMock;
	private Carrier\EntityRepository&MockObject $carrierEntityRepositoryMock;
	private MockObject&CartService $cartServiceMock;
	private SizeFactory&MockObject $sizeFactoryMock;
	private WpAdapter&MockObject $wpAdapterMock;
	private WcAdapter&MockObject $wcAdapterMock;

	private function createOrderUpdater(): OrderUpdater {
		$this->wpAdapterMock               = $this->createMock( WpAdapter::class );
		$this->wcAdapterMock               = $this->createMock( WcAdapter::class );
		$this->orderRepositoryMock         = $this->createMock( Order\Repository::class );
		$this->checkoutServiceMock         = $this->createMock( CheckoutService::class );
		$this->checkoutStorageMock         = $this->createMock( CheckoutStorage::class );
		$this->optionsProviderMock         = $this->createMock( OptionsProvider::class );
		$this->attributeMapperMock         = $this->createMock( AttributeMapper::class );
		$this->carrierEntityRepositoryMock = $this->createMock( Carrier\EntityRepository::class );
		$this->cartServiceMock             = $this->createMock( CartService::class );
		$this->sizeFactoryMock             = $this->createMock( SizeFactory::class );

		return new OrderUpdater(
			$this->wpAdapterMock,
			$this->wcAdapterMock,
			$this->orderRepositoryMock,
			$this->checkoutServiceMock,
			$this->checkoutStorageMock,
			$this->optionsProviderMock,
			$this->attributeMapperMock,
			$this->carrierEntityRepositoryMock,
			$this->cartServiceMock,
			$this->createMock( PacketAutoSubmitter::class ),
			$this->sizeFactoryMock,
		);
	}

	public static function actionUpdateOrderPickupPointProvider(): array {
		$carrierRequiresSize = DummyFactory::createCarrierCzechHdRequiresSize();

		$defaultSize = new Entity\Size( 10.0, 20.0, 30.0 );
		$pickupPoint = new Entity\PickupPoint();
		$pickupPoint->setId( 'PP1' );

		return [
			'with defaults'            => [
				'data' => [
					'resolveChosenMethod'            => 'packetery:1',
					'isPacketeryShippingMethod'      => true,
					'getPostDataIncludingStoredData' => [
						'packetery:1' => [
							Attribute::POINT_ID     => 'PP1',
							Attribute::POINT_NAME   => 'Point Name',
							Attribute::POINT_CITY   => 'City',
							Attribute::POINT_ZIP    => '10000',
							Attribute::POINT_STREET => 'Street 1',
							Attribute::POINT_PLACE  => 'Place',
							Attribute::CARRIER_ID   => '106',
						],
					],
					'getCarrierIdFromPacketeryShippingMethod' => '106',
					'isPickupPointOrder'             => true,
					'carrierEntity'                  => $carrierRequiresSize,
					'getCartWeightKg'                => 0.0,
					'isDefaultWeightEnabled'         => true,
					'getDefaultWeight'               => 1.5,
					'getPackagingWeight'             => 0.5,
					'isDefaultDimensionsEnabled'     => true,
					'defaultSize'                    => $defaultSize,
					'toOrderEntityPickupPoint'       => $pickupPoint,
					'replaceShippingAddressWithPickupPointAddress' => false,
				],
			],
			'with address replacement' => [
				'data' => [
					'resolveChosenMethod'                 => 'packetery:1',
					'isPacketeryShippingMethod'           => true,
					'getPostDataIncludingStoredData'      => [
						'packetery:1' => [
							Attribute::POINT_ID     => 'PP1',
							Attribute::POINT_NAME   => 'Point Name',
							Attribute::POINT_CITY   => 'City',
							Attribute::POINT_ZIP    => '10000',
							Attribute::POINT_STREET => 'Street 1',
							Attribute::POINT_PLACE  => 'Place',
							Attribute::CARRIER_ID   => '106',
							Attribute::POINT_URL    => 'https://example.com',
						],
					],
					'getCarrierIdFromPacketeryShippingMethod' => '106',
					'isPickupPointOrder'                  => true,
					'carrierEntity'                       => $carrierRequiresSize,
					'getCartWeightKg'                     => 0.0,
					'isDefaultWeightEnabled'              => true,
					'getDefaultWeight'                    => 1.5,
					'getPackagingWeight'                  => 0.5,
					'isDefaultDimensionsEnabled'          => true,
					'defaultSize'                         => $defaultSize,
					'toOrderEntityPickupPoint'            => $pickupPoint,
					'replaceShippingAddressWithPickupPointAddress' => true,
					// count of $checkoutData keys
					'expectToWcOrderShippingAddressCalls' => 8,
				],
			],
		];
	}

	/**
	 * @dataProvider actionUpdateOrderPickupPointProvider
	 */
	public function testActionUpdateOrderPickupPoint( array $data ): void {
		$orderUpdater = $this->createOrderUpdater();

		$this->checkoutServiceMock
			->method( 'resolveChosenMethod' )
			->willReturn( $data['resolveChosenMethod'] ?? null );
		$this->checkoutServiceMock
			->method( 'isPacketeryShippingMethod' )
			->willReturn( $data['isPacketeryShippingMethod'] ?? false );
		$this->checkoutStorageMock
			->method( 'getPostDataIncludingStoredData' )
			->willReturn( $data['getPostDataIncludingStoredData'][ $data['resolveChosenMethod'] ] ?? [] );

		$this->checkoutServiceMock
			->method( 'getCarrierIdFromPacketeryShippingMethod' )
			->willReturn( $data['getCarrierIdFromPacketeryShippingMethod'] ?? '' );
		$this->checkoutServiceMock
			->method( 'isPickupPointOrder' )
			->willReturn( $data['isPickupPointOrder'] ?? false );
		$this->carrierEntityRepositoryMock
			->method( 'getAnyById' )
			->willReturn( $data['carrierEntity'] ?? null );
		$this->cartServiceMock
			->method( 'getCartWeightKg' )
			->willReturn( $data['getCartWeightKg'] ?? 0.0 );
		$this->optionsProviderMock
			->method( 'isDefaultWeightEnabled' )
			->willReturn( $data['isDefaultWeightEnabled'] ?? false );
		$this->optionsProviderMock
			->method( 'getDefaultWeight' )
			->willReturn( $data['getDefaultWeight'] ?? 0.0 );
		$this->optionsProviderMock
			->method( 'getPackagingWeight' )
			->willReturn( $data['getPackagingWeight'] ?? 0.0 );
		$this->optionsProviderMock
			->method( 'isDefaultDimensionsEnabled' )
			->willReturn( $data['isDefaultDimensionsEnabled'] ?? false );
		$this->sizeFactoryMock
			->method( 'createDefaultSizeForNewOrder' )
			->willReturn( $data['defaultSize'] ?? new Entity\Size() );
		$this->optionsProviderMock
			->method( 'replaceShippingAddressWithPickupPointAddress' )
			->willReturn( $data['replaceShippingAddressWithPickupPointAddress'] ?? false );
		$this->attributeMapperMock
			->method( 'toOrderEntityPickupPoint' )
			->willReturn( $data['toOrderEntityPickupPoint'] ?? new Entity\PickupPoint() );

		if ( isset( $data['expectToWcOrderShippingAddressCalls'] ) ) {
			$this->attributeMapperMock
				->expects( $this->exactly( $data['expectToWcOrderShippingAddressCalls'] ) )
				->method( 'toWcOrderShippingAddress' );
		}

		$capturedOrder = null;
		$this->orderRepositoryMock->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->callback(
					function ( Entity\Order $order ) use ( &$capturedOrder ): bool {
						$capturedOrder = $order;

						return true;
					}
				)
			);

		$wcOrderMock = $this->createMock( WC_Order::class );
		$wcOrderMock->method( 'get_id' )->willReturn( 999 );
		$wcOrderMock->expects( $this->once() )->method( 'save' );

		$orderUpdater->actionUpdateOrder( $wcOrderMock );

		$this->assertInstanceOf( Entity\Order::class, $capturedOrder );
		$this->assertSame( '999', $capturedOrder->getNumber() );
		$this->assertSame( 2.0, $capturedOrder->getWeight() );
		$this->assertNotNull( $capturedOrder->getSize() );
		$this->assertSame( 10.0, $capturedOrder->getLength() );
		$this->assertSame( 106, $capturedOrder->getPickupPointOrCarrierId() );
	}

	public static function actionUpdateOrderEarlyReturnProvider(): array {
		$carrier = DummyFactory::createCarrierCzechPp();

		return [
			'no order'                  => [
				'data' => [
					'nullWcOrder' => true,
				],
			],
			'not packetery'             => [
				'data' => [
					'resolveChosenMethod'       => 'other:1',
					'isPacketeryShippingMethod' => false,
				],
			],
			'pickup point without data' => [
				'data' => [
					'resolveChosenMethod'            => 'packetery:1',
					'isPacketeryShippingMethod'      => true,
					'getPostDataIncludingStoredData' => [],
					'getCarrierIdFromPacketeryShippingMethod' => '123',
					'isPickupPointOrder'             => true,
					'carrierEntity'                  => $carrier,
				],
			],
			'carrier not found'         => [
				'data' => [
					'resolveChosenMethod'            => 'packetery:1',
					'isPacketeryShippingMethod'      => true,
					'getPostDataIncludingStoredData' => [ 'packetery:1' => [ Attribute::CARRIER_ID => '123' ] ],
					'getCarrierIdFromPacketeryShippingMethod' => '123',
					'isPickupPointOrder'             => false,
					'carrierEntity'                  => null,
				],
			],
		];
	}

	/**
	 * @dataProvider actionUpdateOrderEarlyReturnProvider
	 */
	public function testActionUpdateOrderEarlyReturn( array $data ): void {
		$orderUpdater = $this->createOrderUpdater();

		if ( isset( $data['resolveChosenMethod'] ) ) {
			$this->checkoutServiceMock
				->method( 'resolveChosenMethod' )
				->willReturn( $data['resolveChosenMethod'] );
		}
		if ( isset( $data['isPacketeryShippingMethod'] ) ) {
			$this->checkoutServiceMock
				->method( 'isPacketeryShippingMethod' )
				->willReturn( $data['isPacketeryShippingMethod'] );
		}
		if ( isset( $data['getPostDataIncludingStoredData'] ) ) {
			$this->checkoutStorageMock
				->method( 'getPostDataIncludingStoredData' )
				->willReturn( $data['getPostDataIncludingStoredData'] );
		}
		if ( isset( $data['getCarrierIdFromPacketeryShippingMethod'] ) ) {
			$this->checkoutServiceMock
				->method( 'getCarrierIdFromPacketeryShippingMethod' )
				->willReturn( $data['getCarrierIdFromPacketeryShippingMethod'] );
		}
		if ( isset( $data['isPickupPointOrder'] ) ) {
			$this->checkoutServiceMock
				->method( 'isPickupPointOrder' )
				->willReturn( $data['isPickupPointOrder'] );
		}
		if ( isset( $data['carrierEntity'] ) ) {
			$this->carrierEntityRepositoryMock
				->method( 'getAnyById' )
				->willReturn( $data['carrierEntity'] );
		}

		$this->orderRepositoryMock->expects( $this->never() )->method( 'save' );

		$orderId = 1001;
		if ( $data['nullWcOrder'] ?? false ) {
			$this->orderRepositoryMock
				->method( 'getWcOrderById' )
				->willReturn( null );
		} else {
			$wcOrderMock = $this->createMock( WC_Order::class );
			$wcOrderMock->method( 'get_id' )->willReturn( $orderId );
			$wcOrderMock->expects( $this->never() )->method( 'save' );

			$this->orderRepositoryMock
				->method( 'getWcOrderById' )
				->willReturn( $wcOrderMock );
		}

		$orderUpdater->actionUpdateOrderById( $orderId );
	}

	public static function actionUpdateOrderNonPickupProvider(): array {
		$carrierHd  = DummyFactory::createCarrierCzechHdRequiresSize();
		$carrierCar = DummyFactory::createCarDeliveryCarrier();

		return [
			'home delivery validated'        => [
				'data' => [
					'resolveChosenMethod'            => 'packetery:hd1',
					'isPacketeryShippingMethod'      => true,
					'getPostDataIncludingStoredData' => [
						'packetery:hd1' => [
							Attribute::ADDRESS_IS_VALIDATED => '1',
							Attribute::ADDRESS_HOUSE_NUMBER => '10',
							Attribute::ADDRESS_STREET    => 'Home Street',
							Attribute::ADDRESS_CITY      => 'Praha',
							Attribute::ADDRESS_POST_CODE => '19000',
							Attribute::ADDRESS_COUNTY    => 'Praha',
							Attribute::ADDRESS_COUNTRY   => 'CZ',
							Attribute::ADDRESS_LATITUDE  => '50.087',
							Attribute::ADDRESS_LONGITUDE => '14.421',
						],
					],
					'getCarrierIdFromPacketeryShippingMethod' => '999',
					'isPickupPointOrder'             => false,
					'isHomeDeliveryOrder'            => true,
					'areBlocksUsedInCheckout'        => false,
					'carrierEntity'                  => $carrierHd,
				],
			],
			'home delivery validated blocks' => [
				'data' => [
					'resolveChosenMethod'            => 'packetery:hd1',
					'isPacketeryShippingMethod'      => true,
					'getPostDataIncludingStoredData' => [
						'packetery:hd1' => [
							Attribute::ADDRESS_IS_VALIDATED => '1',
							Attribute::ADDRESS_HOUSE_NUMBER => '10',
							Attribute::ADDRESS_STREET    => 'Home Street',
							Attribute::ADDRESS_CITY      => 'Praha',
							Attribute::ADDRESS_POST_CODE => '19000',
							Attribute::ADDRESS_COUNTY    => 'Praha',
							Attribute::ADDRESS_COUNTRY   => 'CZ',
							Attribute::ADDRESS_LATITUDE  => '50.087',
							Attribute::ADDRESS_LONGITUDE => '14.421',
						],
					],
					'getCarrierIdFromPacketeryShippingMethod' => '999',
					'isPickupPointOrder'             => false,
					'isHomeDeliveryOrder'            => true,
					'areBlocksUsedInCheckout'        => true,
					'carrierEntity'                  => $carrierHd,
				],
			],
			'car delivery'                   => [
				'data' => [
					'resolveChosenMethod'            => 'packetery:car1',
					'isPacketeryShippingMethod'      => true,
					'getPostDataIncludingStoredData' => [
						'packetery:car1' => [
							Attribute::CAR_DELIVERY_ID   => 'cd_123',
							Attribute::ADDRESS_STREET    => 'Car Street',
							Attribute::ADDRESS_HOUSE_NUMBER => '1',
							Attribute::ADDRESS_CITY      => 'City',
							Attribute::ADDRESS_POST_CODE => '19000',
							Attribute::ADDRESS_COUNTRY   => 'CZ',
							Attribute::EXPECTED_DELIVERY_FROM => '2025-01-01',
							Attribute::EXPECTED_DELIVERY_TO => '2025-01-02',
						],
					],
					'getCarrierIdFromPacketeryShippingMethod' => '25061',
					'isPickupPointOrder'             => false,
					'isCarDeliveryOrder'             => true,
					'carrierEntity'                  => $carrierCar,
				],
			],
		];
	}

	/**
	 * @dataProvider actionUpdateOrderNonPickupProvider
	 */
	public function testActionUpdateOrderNonPickup( array $data ): void {
		$orderUpdater = $this->createOrderUpdater();

		$rateId       = $data['resolveChosenMethod'];
		$checkoutData = $data['getPostDataIncludingStoredData'][ $rateId ];

		$this->checkoutServiceMock
			->method( 'resolveChosenMethod' )
			->willReturn( $data['resolveChosenMethod'] );
		$this->checkoutServiceMock
			->method( 'isPacketeryShippingMethod' )
			->willReturn( $data['isPacketeryShippingMethod'] );

		$this->checkoutStorageMock
			->method( 'getPostDataIncludingStoredData' )
			->willReturn( $checkoutData );
		$this->checkoutServiceMock
			->method( 'getCarrierIdFromPacketeryShippingMethod' )
			->willReturn( $data['getCarrierIdFromPacketeryShippingMethod'] );
		$this->checkoutServiceMock
			->method( 'isPickupPointOrder' )
			->willReturn( $data['isPickupPointOrder'] );
		if ( isset( $data['isHomeDeliveryOrder'] ) ) {
			$this->checkoutServiceMock
				->method( 'isHomeDeliveryOrder' )
				->willReturn( $data['isHomeDeliveryOrder'] );
		}
		if ( isset( $data['areBlocksUsedInCheckout'] ) ) {
			$this->checkoutServiceMock
				->method( 'areBlocksUsedInCheckout' )
				->willReturn( $data['areBlocksUsedInCheckout'] );
		}
		if ( isset( $data['isCarDeliveryOrder'] ) ) {
			$this->checkoutServiceMock
				->method( 'isCarDeliveryOrder' )
				->willReturn( $data['isCarDeliveryOrder'] );
		}
		$this->carrierEntityRepositoryMock
			->method( 'getAnyById' )
			->willReturn( $data['carrierEntity'] );

		if ( isset( $data['isHomeDeliveryOrder'] ) && $data['isHomeDeliveryOrder'] === true ) {
			$validatedAddress = new Entity\Address(
				$checkoutData[ Attribute::ADDRESS_STREET ],
				$checkoutData[ Attribute::ADDRESS_CITY ],
				$checkoutData[ Attribute::ADDRESS_POST_CODE ]
			);
			$validatedAddress->setHouseNumber( $checkoutData[ Attribute::ADDRESS_HOUSE_NUMBER ] );
			$validatedAddress->setCounty( $checkoutData[ Attribute::ADDRESS_COUNTY ] );
			$validatedAddress->setLatitude( $checkoutData[ Attribute::ADDRESS_LATITUDE ] );
			$validatedAddress->setLongitude( $checkoutData[ Attribute::ADDRESS_LONGITUDE ] );
			$this->attributeMapperMock
				->method( 'toValidatedAddress' )
				->willReturn( $validatedAddress );
		}

		if ( isset( $data['isCarDeliveryOrder'] ) && $data['isCarDeliveryOrder'] === true ) {
			$carAddress = new Entity\Address(
				$checkoutData[ Attribute::ADDRESS_STREET ],
				$checkoutData[ Attribute::ADDRESS_CITY ],
				$checkoutData[ Attribute::ADDRESS_POST_CODE ]
			);
			$carAddress->setHouseNumber( $checkoutData[ Attribute::ADDRESS_HOUSE_NUMBER ] );
			$this->attributeMapperMock
				->method( 'toCarDeliveryAddress' )
				->willReturn( $carAddress );
		}

		$capturedOrder = null;
		$this->orderRepositoryMock->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->callback(
					function ( Entity\Order $order ) use ( &$capturedOrder ): bool {
						$capturedOrder = $order;

						return true;
					}
				)
			);

		$wcOrderMock = $this->createMock( WC_Order::class );
		$wcOrderMock->method( 'get_id' )->willReturn( 2002 );

		$orderUpdater->actionUpdateOrder( $wcOrderMock );

		$this->assertInstanceOf( Entity\Order::class, $capturedOrder );
		if ( isset( $data['isHomeDeliveryOrder'] ) && $data['isHomeDeliveryOrder'] === true ) {
			$this->assertTrue( $capturedOrder->isAddressValidated() );
			$this->assertNotNull( $capturedOrder->getValidatedDeliveryAddress() );
		}
		if ( isset( $data['isCarDeliveryOrder'] ) && $data['isCarDeliveryOrder'] === true ) {
			$this->assertTrue( $capturedOrder->isAddressValidated() );
			$this->assertSame( 'cd_123', $capturedOrder->getCarDeliveryId() );
		}
	}
}
