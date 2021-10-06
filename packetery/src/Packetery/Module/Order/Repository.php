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
	 * @return array
	 */
	public function getEntitiesByIds( array $orderIds ): array {
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
			$wcOrder                             = wc_get_order( $post );
			$orderEntities[ $wcOrder->get_id() ] = new Entity( $wcOrder );
		}

		return $orderEntities;
	}

}
