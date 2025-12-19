<?php

declare( strict_types=1 );

namespace Packetery\Module\Labels;

use LogicException;
use Packetery\Core\Entity\Order;

class LabelPrintPacketData {

	/**
	 * @var LabelPrintPacketDataItem[]
	 */
	protected array $items;

	/**
	 * @var array<int, Order>|null
	 */
	private ?array $ordersByOrderIdCache = null;

	public function __construct() {
		$this->items = [];
	}

	public function addItem( Order $order, string $packetId ): void {
		$this->items[]              = new LabelPrintPacketDataItem( $order, $packetId );
		$this->ordersByOrderIdCache = null;
	}

	/**
	 * @return LabelPrintPacketDataItem[]
	 */
	public function getItems(): array {
		return $this->items;
	}

	/**
	 * @return string[]
	 */
	public function getPacketIds(): array {
		$packetIds = [];
		foreach ( $this->items as $item ) {
			$packetIds[] = $item->getPacketId();
		}

		return $packetIds;
	}

	public function isEmpty(): bool {
		return $this->items === [];
	}

	public function getCount(): int {
		return count( $this->items );
	}

	public function getFirstItem(): LabelPrintPacketDataItem {
		$item = reset( $this->items );

		if ( $item === false ) {
			throw new LogicException( 'Cannot get first item from empty collection.' );
		}

		return $item;
	}

	/**
	 * @return array<int, Order>
	 */
	private function getOrdersByOrderId(): array {
		if ( $this->ordersByOrderIdCache !== null ) {
			return $this->ordersByOrderIdCache;
		}

		$ordersByOrderId = [];
		foreach ( $this->items as $item ) {
			$orderId                     = (int) $item->getOrder()->getNumber();
			$ordersByOrderId[ $orderId ] = $item->getOrder();
		}
		$this->ordersByOrderIdCache = $ordersByOrderId;

		return $this->ordersByOrderIdCache;
	}

	public function getOrderByOrderId( int $orderId ): Order {
		$ordersByOrderId = $this->getOrdersByOrderId();

		if ( ! isset( $ordersByOrderId[ $orderId ] ) ) {
			throw new LogicException( "Order with ID $orderId not found in collection." );
		}

		return $ordersByOrderId[ $orderId ];
	}
}
