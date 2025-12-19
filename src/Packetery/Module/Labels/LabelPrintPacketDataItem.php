<?php

declare( strict_types=1 );

namespace Packetery\Module\Labels;

use Packetery\Core\Entity\Order;

class LabelPrintPacketDataItem {

	private Order $order;
	private string $packetId;

	public function __construct( Order $order, string $packetId ) {
		$this->order    = $order;
		$this->packetId = $packetId;
	}

	public function getOrder(): Order {
		return $this->order;
	}

	public function getPacketId(): string {
		return $this->packetId;
	}
}
