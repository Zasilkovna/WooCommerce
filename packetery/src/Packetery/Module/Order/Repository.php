<?php
/**
 * Class Repository.
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Entity;
use Packetery\Module\EntityFactory;

/**
 * Class Repository.
 *
 * @package Packetery\Module\Order
 */
class Repository {

	/**
	 * Order factory.
	 *
	 * @var EntityFactory\Order
	 */
	private $orderEntityFactory;

	/**
	 * Repository constructor.
	 *
	 * @param EntityFactory\Order $orderEntityFactory Order factory.
	 */
	public function __construct( EntityFactory\Order $orderEntityFactory ) {
		$this->orderEntityFactory = $orderEntityFactory;
	}

	/**
	 * Loads order entities by list of ids.
	 *
	 * @param array $orderIds Order ids.
	 *
	 * @return Entity\Order[]
	 */
	public function getOrdersByIds( array $orderIds ): array {
		$orderEntities = [];
		$posts         = get_posts(
			[
				'post_type'   => 'shop_order',
				'post__in'    => $orderIds,
				'post_status' => [ 'any', 'trash' ],
				'nopaging'    => true,
			]
		);
		foreach ( $posts as $post ) {
			$wcOrder = wc_get_order( $post );
			$order   = $this->orderEntityFactory->create( $wcOrder );
			if ( $wcOrder && $order ) {
				$orderEntities[ $order->getNumber() ] = $order;
			}
		}

		return $orderEntities;
	}

}
