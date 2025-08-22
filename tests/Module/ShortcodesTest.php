<?php

declare(strict_types=1);

namespace Tests\Module;

use Packetery\Core\Entity\Order;
use Packetery\Core\Entity\PickupPoint;
use Packetery\Module\Email\EmailShortcodes;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Order\Repository;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class EmailShortcodesTest extends TestCase {
	private const DUMMY_PROCESSED_CONTENT = 'processed_content';
	private const DUMMY_CONTENT           = 'content';
	private const DUMMY_INVALID_VALUE     = 'invalid';

	private WpAdapter $wpAdapterMock;
	private Repository $orderRepositoryMock;
	private EmailShortcodes $shortcodes;

	private function createShortcodes(): EmailShortcodes {
		$this->wpAdapterMock       = $this->createMock( WpAdapter::class );
		$this->orderRepositoryMock = $this->createMock( Repository::class );

		return new EmailShortcodes( $this->wpAdapterMock, $this->orderRepositoryMock );
	}

	public static function providerIfPickupPoint(): array {
		return [
			'no order'          => [
				'order'       => null,
				'pickupPoint' => null,
				'expected'    => '',
			],
			'no pickup point'   => [
				'order'       => DummyFactory::createOrderCzHdIncomplete(),
				'pickupPoint' => null,
				'expected'    => '',
			],
			'with pickup point' => [
				'order'       => DummyFactory::createOrderCzHdIncomplete(),
				'pickupPoint' => DummyFactory::createPickupPoint(),
				'expected'    => self::DUMMY_PROCESSED_CONTENT,
			],
		];
	}

	/**
	 * @dataProvider providerIfPickupPoint
	 */
	public function testIfPickupPoint(
		?Order $order,
		?PickupPoint $pickupPoint,
		string $expected
	): void {
		$this->shortcodes = $this->createShortcodes();

		if ( $pickupPoint !== null && $order !== null ) {
			$order->setPickupPoint( $pickupPoint );
		}
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$this->wpAdapterMock->method( 'doShortcode' )->willReturn( $expected );

		$result = $this->shortcodes->ifPickupPoint( [ 'order_id' => 123 ], self::DUMMY_CONTENT );
		$this->assertSame( $expected, $result );
	}

	public function testIfExternalCarrierReturnsEmptyWhenNoOrder(): void {
		$this->shortcodes = $this->createShortcodes();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( null );
		$result = $this->shortcodes->ifExternalCarrier( [ 'order_id' => 123 ], self::DUMMY_CONTENT );
		$this->assertSame( '', $result );
	}

	public function testIfExternalCarrierReturnsEmptyWhenNotExternalCarrier(): void {
		$this->shortcodes = $this->createShortcodes();
		$carrier          = DummyFactory::createCarrierCzechPp();
		$order            = new Order( 'orderNumber', $carrier );
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->ifExternalCarrier( [ 'order_id' => 123 ], self::DUMMY_CONTENT );
		$this->assertSame( '', $result );
	}

	public function testIfExternalCarrierReturnsContentWhenExternalCarrier(): void {
		$this->shortcodes = $this->createShortcodes();
		$carrier          = DummyFactory::createCarDeliveryCarrier();
		$order            = new Order( 'orderNumber', $carrier );
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$this->wpAdapterMock->method( 'doShortcode' )->willReturn( self::DUMMY_PROCESSED_CONTENT );
		$result = $this->shortcodes->ifExternalCarrier( [ 'order_id' => 123 ], self::DUMMY_CONTENT );
		$this->assertSame( self::DUMMY_PROCESSED_CONTENT, $result );
	}

	public function testPickupPointAddressReturnsEmptyWhenNoOrder(): void {
		$this->shortcodes = $this->createShortcodes();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( null );
		$result = $this->shortcodes->pickupPointAddress( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testPickupPointAddressReturnsEmptyWhenNoPickupPoint(): void {
		$this->shortcodes = $this->createShortcodes();
		$order            = DummyFactory::createOrderCzHdIncomplete();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointAddress( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public static function providerPickupPointAddress(): array {
		return [
			'no order'          => [
				'order'       => null,
				'pickupPoint' => null,
				'expected'    => '',
			],
			'no pickup point'   => [
				'order'       => DummyFactory::createOrderCzHdIncomplete(),
				'pickupPoint' => null,
				'expected'    => '',
			],
			'with pickup point' => [
				'order'       => DummyFactory::createOrderCzHdIncomplete(),
				'pickupPoint' => DummyFactory::createPickupPoint(),
				'expected'    => DummyFactory::createPickupPoint()->getFullAddress(),
			],
		];
	}

	/**
	 * @dataProvider providerPickupPointAddress
	 */
	public function testPickupPointAddress( ?Order $order, ?PickupPoint $pickupPoint, string $expected ): void {
		$this->shortcodes = $this->createShortcodes();
		if ( $pickupPoint !== null && $order !== null ) {
			$order->setPickupPoint( $pickupPoint );
		}
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointAddress( [ 'order_id' => 123 ] );
		$this->assertSame( $expected, $result );
	}

	public function testPickupPointStreetReturnsEmptyWhenNoOrder(): void {
		$this->shortcodes = $this->createShortcodes();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( null );
		$result = $this->shortcodes->pickupPointStreet( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testPickupPointStreetReturnsEmptyWhenNoPickupPoint(): void {
		$this->shortcodes = $this->createShortcodes();
		$order            = DummyFactory::createOrderCzHdIncomplete();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointStreet( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public static function providerPickupPointStreet(): array {
		return [
			'no order'          => [
				'order'       => null,
				'pickupPoint' => null,
				'expected'    => '',
			],
			'no pickup point'   => [
				'order'       => DummyFactory::createOrderCzHdIncomplete(),
				'pickupPoint' => null,
				'expected'    => '',
			],
			'with pickup point' => [
				'order'       => DummyFactory::createOrderCzHdIncomplete(),
				'pickupPoint' => DummyFactory::createPickupPoint(),
				'expected'    => DummyFactory::createPickupPoint()->getStreet(),
			],
		];
	}

	/**
	 * @dataProvider providerPickupPointStreet
	 */
	public function testPickupPointStreet( ?Order $order, ?PickupPoint $pickupPoint, string $expected ): void {
		$this->shortcodes = $this->createShortcodes();
		if ( $pickupPoint !== null && $order !== null ) {
			$order->setPickupPoint( $pickupPoint );
		}
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointStreet( [ 'order_id' => 123 ] );
		$this->assertSame( $expected, $result );
	}

	public function testPickupPointCountryReturnsEmptyWhenNoOrder(): void {
		$this->shortcodes = $this->createShortcodes();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( null );
		$result = $this->shortcodes->pickupPointCountry( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testPickupPointCountryReturnsEmptyWhenNoPickupPoint(): void {
		$this->shortcodes = $this->createShortcodes();
		$order            = DummyFactory::createOrderCzHdIncomplete();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointCountry( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testPickupPointCountryReturnsCountryWhenPickupPointExists(): void {
		$this->shortcodes = $this->createShortcodes();
		$order            = DummyFactory::createOrderCzHdIncomplete();
		$pickupPoint      = DummyFactory::createPickupPoint();
		$order->setPickupPoint( $pickupPoint );
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointCountry( [ 'order_id' => 123 ] );
		$this->assertSame( $order->getShippingCountry() ?? '', $result );
	}

	public function testTrackingNumberReturnsEmptyWhenNoOrder(): void {
		$this->shortcodes = $this->createShortcodes();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( null );
		$result = $this->shortcodes->trackingNumber( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testTrackingNumberReturnsEmptyWhenNoBarcode(): void {
		$this->shortcodes = $this->createShortcodes();
		$order            = DummyFactory::createOrderCzHdIncomplete();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->trackingNumber( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testTrackingNumberReturnsBarcodeWhenAvailable(): void {
		$this->shortcodes = $this->createShortcodes();
		$order            = DummyFactory::createOrderCzHdIncomplete();
		$order->setPacketId( 'TRACK123456' );
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->trackingNumber( [ 'order_id' => 123 ] );
		$this->assertSame( 'ZTRACK123456', $result );
	}

	public function testTrackingUrlReturnsEmptyWhenNoOrder(): void {
		$this->shortcodes = $this->createShortcodes();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( null );
		$result = $this->shortcodes->trackingUrl( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testTrackingUrlReturnsEmptyWhenNoUrl(): void {
		$this->shortcodes = $this->createShortcodes();
		$order            = DummyFactory::createOrderCzHdIncomplete();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->trackingUrl( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testTrackingUrlReturnsUrlWhenAvailable(): void {
		$this->shortcodes = $this->createShortcodes();
		$order            = DummyFactory::createOrderCzHdIncomplete();
		$order->setPacketTrackingUrl( 'https://tracking.example.com/TRACK123456' );
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->trackingUrl( [ 'order_id' => 123 ] );
		$this->assertSame( 'https://tracking.example.com/TRACK123456', $result );
	}

	public function testPickupPointIdReturnsEmptyWhenNoOrder(): void {
		$this->shortcodes = $this->createShortcodes();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( null );
		$result = $this->shortcodes->pickupPointId( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testPickupPointIdReturnsEmptyWhenNoPickupPoint(): void {
		$this->shortcodes = $this->createShortcodes();
		$order            = DummyFactory::createOrderCzHdIncomplete();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointId( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testPickupPointIdReturnsIdWhenPickupPointExists(): void {
		$this->shortcodes = $this->createShortcodes();
		$order            = DummyFactory::createOrderCzHdIncomplete();
		$pickupPoint      = DummyFactory::createPickupPoint();
		$order->setPickupPoint( $pickupPoint );
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointId( [ 'order_id' => 123 ] );
		$this->assertSame( $pickupPoint->getId(), $result );
	}

	public function testPickupPointNameReturnsEmptyWhenNoOrder(): void {
		$this->shortcodes = $this->createShortcodes();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( null );
		$result = $this->shortcodes->pickupPointName( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testPickupPointNameReturnsEmptyWhenNoPickupPoint(): void {
		$this->shortcodes = $this->createShortcodes();
		$order            = DummyFactory::createOrderCzHdIncomplete();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointName( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testPickupPointNameReturnsNameWhenPickupPointExists(): void {
		$this->shortcodes = $this->createShortcodes();
		$order            = DummyFactory::createOrderCzHdIncomplete();
		$pickupPoint      = DummyFactory::createPickupPoint();
		$order->setPickupPoint( $pickupPoint );
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointName( [ 'order_id' => 123 ] );
		$this->assertSame( $pickupPoint->getName(), $result );
	}

	public function testPickupPointCityReturnsEmptyWhenNoOrder(): void {
		$this->shortcodes = $this->createShortcodes();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( null );
		$result = $this->shortcodes->pickupPointCity( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testPickupPointCityReturnsEmptyWhenNoPickupPoint(): void {
		$this->shortcodes = $this->createShortcodes();
		$order            = DummyFactory::createOrderCzHdIncomplete();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointCity( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testPickupPointCityReturnsCityWhenPickupPointExists(): void {
		$this->shortcodes = $this->createShortcodes();
		$order            = DummyFactory::createOrderCzHdIncomplete();
		$pickupPoint      = DummyFactory::createPickupPoint();
		$order->setPickupPoint( $pickupPoint );
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointCity( [ 'order_id' => 123 ] );
		$this->assertSame( $pickupPoint->getCity(), $result );
	}

	public function testPickupPointZipReturnsEmptyWhenNoOrder(): void {
		$this->shortcodes = $this->createShortcodes();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( null );
		$result = $this->shortcodes->pickupPointZip( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testPickupPointZipReturnsEmptyWhenNoPickupPoint(): void {
		$this->shortcodes = $this->createShortcodes();
		$order            = DummyFactory::createOrderCzHdIncomplete();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointZip( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testPickupPointZipReturnsZipWhenPickupPointExists(): void {
		$this->shortcodes = $this->createShortcodes();
		$order            = DummyFactory::createOrderCzHdIncomplete();
		$pickupPoint      = DummyFactory::createPickupPoint();
		$order->setPickupPoint( $pickupPoint );
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointZip( [ 'order_id' => 123 ] );
		$this->assertSame( $pickupPoint->getZip(), $result );
	}

	public function testCarrierNameReturnsEmptyWhenNoOrder(): void {
		$this->shortcodes = $this->createShortcodes();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( null );
		$result = $this->shortcodes->carrierName( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testCarrierNameReturnsCarrierNameWhenOrderExists(): void {
		$this->shortcodes = $this->createShortcodes();
		$carrier          = DummyFactory::createCarrierCzechPp();
		$order            = new Order( 'orderNumber', $carrier );
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->carrierName( [ 'order_id' => 123 ] );
		$this->assertSame( $carrier->getName(), $result );
	}

	public function testIfPacketSubmittedReturnsEmptyWhenNoOrder(): void {
		$this->shortcodes = $this->createShortcodes();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( null );
		$result = $this->shortcodes->ifPacketSubmitted( [ 'order_id' => 123 ], self::DUMMY_CONTENT );
		$this->assertSame( '', $result );
	}

	public function testIfPacketSubmittedReturnsEmptyWhenNoPacketId(): void {
		$this->shortcodes = $this->createShortcodes();
		$order            = DummyFactory::createOrderCzHdIncomplete();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->ifPacketSubmitted( [ 'order_id' => 123 ], self::DUMMY_CONTENT );
		$this->assertSame( '', $result );
	}

	public function testIfPacketSubmittedReturnsContentWhenPacketIdExists(): void {
		$this->shortcodes = $this->createShortcodes();
		$order            = DummyFactory::createOrderCzHdIncomplete();
		$order->setPacketId( 'PACKET123' );
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$this->wpAdapterMock->method( 'doShortcode' )->willReturn( self::DUMMY_PROCESSED_CONTENT );
		$result = $this->shortcodes->ifPacketSubmitted( [ 'order_id' => 123 ], self::DUMMY_CONTENT );
		$this->assertSame( self::DUMMY_PROCESSED_CONTENT, $result );
	}

	public function testMethodsReturnEmptyWhenOrderIdIsMissing(): void {
		$this->shortcodes = $this->createShortcodes();
		$result           = $this->shortcodes->trackingNumber( [] );
		$this->assertSame( '', $result );
	}

	public function testMethodsReturnEmptyWhenOrderIdIsNotNumeric(): void {
		$this->shortcodes = $this->createShortcodes();
		$result           = $this->shortcodes->trackingNumber( [ 'order_id' => self::DUMMY_INVALID_VALUE ] );
		$this->assertSame( '', $result );
	}

	public function testMethodsReturnEmptyWhenOrderIdIsNull(): void {
		$this->shortcodes = $this->createShortcodes();
		$result           = $this->shortcodes->trackingNumber( [ 'order_id' => null ] );
		$this->assertSame( '', $result );
	}
}
