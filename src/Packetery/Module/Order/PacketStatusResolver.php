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
	 * Gets translated name of packet status.
	 *
	 * @param string|null $status Status code.
	 *
	 * @return string|null
	 */
	public static function getTranslatedName( ?string $status ): ?string {
		$statuses = PacketSynchronizer::getPacketStatuses();

		return isset( $statuses[ $status ] ) ? $statuses[ $status ]->getTranslatedName() : $status;
	}
}
