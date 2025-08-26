<?php
/**
 * Class PacketeryStatusResolver
 *
 * @package Packetery\Module\Order
 */

declare(strict_types=1);

namespace Packetery\Module\Order;

/**
 * Class PacketeryStatusResolver
 *
 * @package Packetery\Module\Order
 */
class PacketStatusResolver {

	/**
	 * @var PacketSynchronizer
	 */
	private $packetSynchronizer;

	public function __construct( PacketSynchronizer $packetSynchronizer ) {
		$this->packetSynchronizer = $packetSynchronizer;
	}

	/**
	 * Gets translated name of packet status.
	 *
	 * @param string|null $status Status code.
	 *
	 * @return string|null
	 */
	public function getTranslatedName( ?string $status ): ?string {
		$statuses = $this->packetSynchronizer->getPacketStatuses();

		return isset( $statuses[ $status ] ) ? $statuses[ $status ]->getTranslatedName() : $status;
	}
}
