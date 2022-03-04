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
use Packetery\Module\Order\Repository;
use WC_Order;

/**
 * Class PickupPoint.
 *
 * @package Packetery\Module\EntityFactory
 */
class PickupPoint {

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * PickupPoint constructor.
	 *
	 * @param Repository $orderRepository Order repository.
	 */
	public function __construct( Repository $orderRepository ) {
		$this->orderRepository = $orderRepository;
	}

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
		$moduleOrder = new Order\Entity( $wcOrder, $this->orderRepository );
		if ( $moduleOrder->getPointId() === null ) {
			return null;
		}

		return $this->create( $moduleOrder );
	}

}
