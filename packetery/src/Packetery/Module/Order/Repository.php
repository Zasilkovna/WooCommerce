<?php
/**
 * Class Repository.
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

/**
 * Class Repository.
 *
 * @package Packetery\Module\Order
 */
class Repository {

	/**
	 * Loads order entities by list of ids.
	 *
	 * @param array $orderIds Order ids.
	 *
	 * @return \Packetery\Module\Order\Entity[]
	 */
	public function getOrdersByIds( array $orderIds ): array {
		$orderEntities = [];
		$posts         = get_posts(
			[
				'post_type'   => 'shop_order',
				'post__in'    => $orderIds,
				'post_status' => 'any',
				'nopaging'    => true,
			]
		);
		foreach ( $posts as $post ) {
			$wcOrder = wc_get_order( $post );
			if ( $wcOrder ) {
				$orderEntities[] = new \Packetery\Module\Order\Entity( $wcOrder );
			}
		}

		return $orderEntities;
	}

}
