<?php
/**
 * Class PickupPoint.
 *
 * @package Packetery\Module\EntityFactory
 */

declare( strict_types=1 );

namespace Packetery\Module\EntityFactory;

use Packetery\Core\Entity;
use Packetery\Module\Order;
use WC_Order;

/**
 * Class PickupPoint.
 *
 * @package Packetery\Module\EntityFactory
 */
class PickupPoint {

	/**
	 * Creates PickupPoint entity.
	 *
	 * @param Order\Entity $moduleOrder Order entity.
	 *
	 * @return Entity\PickupPoint
	 */
	public function create( Order\Entity $moduleOrder ): Entity\PickupPoint {
		return new Entity\PickupPoint(
			$moduleOrder->getPointId(),
			$moduleOrder->getPointName(),
			$moduleOrder->getPointCity(),
			$moduleOrder->getPointZip(),
			$moduleOrder->getPointStreet(),
			$moduleOrder->getPointUrl()
		);
	}

	/**
	 * Creates PickupPoint entity from WC order.
	 *
	 * @param WC_Order $wcOrder WC order.
	 *
	 * @return Entity\PickupPoint|null
	 */
	public function fromWcOrder( WC_Order $wcOrder ): ?Entity\PickupPoint {
		$moduleOrder = new Order\Entity( $wcOrder );
		if ( $moduleOrder->getPointId() === null ) {
			return null;
		}

		return $this->create( $moduleOrder );
	}

}
