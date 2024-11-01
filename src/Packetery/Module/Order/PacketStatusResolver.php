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
	 * Resolve translate for packet status.
	 *
	 * @param string|null $status Status.
	 *
	 * @return string|null
	 */
	public static function resolveTranslatedStatus( ?string $status ): ?string {
		$statuses = PacketSynchronizer::getPacketStatuses();
		return isset( $statuses[ $status ] ) ? $statuses[ $status ]->getTranslatedName() : $status;
	}

	/**
	 * Resolve name status(code text in Packetery API) for packet status.
	 *
	 * @param string|null $status Status.
	 *
	 * @return string|null
	 */
	public static function resolveName( ?string $status ): ?string {
		$statuses = PacketSynchronizer::getPacketStatuses();
		return isset( $statuses[ $status ] ) ? $statuses[ $status ]->getName() : $status;
	}
}
