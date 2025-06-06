<?php

declare(strict_types=1);

namespace Tests\Module;

use Packetery\Core\Entity\Order;
use Packetery\Module\Email\Shortcodes;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Order\Repository;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class ShortcodesTest extends TestCase {
	private WpAdapter $wpAdapterMock;
	private Repository $orderRepositoryMock;
	private Shortcodes $shortcodes;

	protected function setUp(): void {
		$this->wpAdapterMock       = $this->createMock( WpAdapter::class );
		$this->orderRepositoryMock = $this->createMock( Repository::class );
		$this->shortcodes          = new Shortcodes( $this->wpAdapterMock, $this->orderRepositoryMock );
	}

	/**
	 * @dataProvider providerIfPickupPoint
	 */
	public function testIfPickupPoint(
		?Order $order,
		?object $pickupPoint,
		?string $expected
	) {
		if ( $pickupPoint !== null && $order !== null ) {
			$order->setPickupPoint( $pickupPoint );
		}
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		if ( $expected === 'processed_content' ) {
			$this->wpAdapterMock->method( 'doShortcode' )->willReturn( 'processed_content' );
			$result = $this->shortcodes->ifPickupPoint( [ 'order_id' => 123 ], 'content' );
			$this->assertSame( 'processed_content', $result );
		} else {
			$result = $this->shortcodes->ifPickupPoint( [ 'order_id' => 123 ], 'content' );
			$this->assertSame( $expected, $result );
		}
	}

	public static function providerIfPickupPoint(): array {
		return [
			'no order'          => [ null, null, '' ],
			'no pickup point'   => [ DummyFactory::createOrderCzHdIncomplete(), null, '' ],
			'with pickup point' => [ DummyFactory::createOrderCzHdIncomplete(), DummyFactory::createPickupPoint(), 'processed_content' ],
		];
	}

	public function testIfCarrierReturnsEmptyWhenNoOrder(): void {
		$this->orderRepositoryMock->method( 'findById' )->willReturn( null );
		$result = $this->shortcodes->ifCarrier( [ 'order_id' => 123 ], 'content' );
		$this->assertSame( '', $result );
	}

	public function testIfCarrierReturnsEmptyWhenNotExternalCarrier(): void {
		$carrier = DummyFactory::createCarrierCzechPp();
		$order   = new Order( 'orderNumber', $carrier );
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->ifCarrier( [ 'order_id' => 123 ], 'content' );
		$this->assertSame( '', $result );
	}

	public function testIfCarrierReturnsContentWhenExternalCarrier(): void {
		$carrier = DummyFactory::createCarDeliveryCarrier();
		$order   = new Order( 'orderNumber', $carrier );
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$this->wpAdapterMock->method( 'doShortcode' )->willReturn( 'processed_content' );
		$result = $this->shortcodes->ifCarrier( [ 'order_id' => 123 ], 'content' );
		$this->assertSame( 'processed_content', $result );
	}

	public function testPickupPointAddressReturnsEmptyWhenNoOrder(): void {
		$this->orderRepositoryMock->method( 'findById' )->willReturn( null );
		$result = $this->shortcodes->pickupPointAddress( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testPickupPointAddressReturnsEmptyWhenNoPickupPoint(): void {
		$order = DummyFactory::createOrderCzHdIncomplete();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointAddress( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	/**
	 * @dataProvider providerPickupPointAddress
	 */
	public function testPickupPointAddress( ?Order $order, ?object $pickupPoint, string $expected ): void {
		if ( $pickupPoint !== null && $order !== null ) {
			$order->setPickupPoint( $pickupPoint );
		}
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointAddress( [ 'order_id' => 123 ] );
		$this->assertSame( $expected, $result );
	}

	public static function providerPickupPointAddress(): array {
		return [
			'no order'          => [ null, null, '' ],
			'no pickup point'   => [ DummyFactory::createOrderCzHdIncomplete(), null, '' ],
			'with pickup point' => [ DummyFactory::createOrderCzHdIncomplete(), DummyFactory::createPickupPoint(), DummyFactory::createPickupPoint()->getFullAddress() ],
		];
	}

	public function testPickupPointStreetReturnsEmptyWhenNoOrder(): void {
		$this->orderRepositoryMock->method( 'findById' )->willReturn( null );
		$result = $this->shortcodes->pickupPointStreet( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testPickupPointStreetReturnsEmptyWhenNoPickupPoint(): void {
		$order = DummyFactory::createOrderCzHdIncomplete();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointStreet( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	/**
	 * @dataProvider providerPickupPointStreet
	 */
	public function testPickupPointStreet( ?Order $order, ?object $pickupPoint, string $expected ): void {
		if ( $pickupPoint !== null && $order !== null ) {
			$order->setPickupPoint( $pickupPoint );
		}
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointStreet( [ 'order_id' => 123 ] );
		$this->assertSame( $expected, $result );
	}

	public static function providerPickupPointStreet(): array {
		return [
			'no order'          => [ null, null, '' ],
			'no pickup point'   => [ DummyFactory::createOrderCzHdIncomplete(), null, '' ],
			'with pickup point' => [ DummyFactory::createOrderCzHdIncomplete(), DummyFactory::createPickupPoint(), DummyFactory::createPickupPoint()->getStreet() ],
		];
	}

	public function testPickupPointCountryReturnsEmptyWhenNoOrder(): void {
		$this->orderRepositoryMock->method( 'findById' )->willReturn( null );
		$result = $this->shortcodes->pickupPointCountry( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testPickupPointCountryReturnsEmptyWhenNoPickupPoint(): void {
		$order = DummyFactory::createOrderCzHdIncomplete();
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointCountry( [ 'order_id' => 123 ] );
		$this->assertSame( '', $result );
	}

	public function testPickupPointCountryReturnsCountryWhenPickupPointExists(): void {
		$order       = DummyFactory::createOrderCzHdIncomplete();
		$pickupPoint = DummyFactory::createPickupPoint();
		$order->setPickupPoint( $pickupPoint );
		$this->orderRepositoryMock->method( 'findById' )->willReturn( $order );
		$result = $this->shortcodes->pickupPointCountry( [ 'order_id' => 123 ] );
		$this->assertSame( $order->getShippingCountry() ?? '', $result );
	}
}
