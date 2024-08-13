<?php
/**
 * Class Product\EntityFactory.
 *
 * @package Packetery\Module\Product
 */

declare( strict_types=1 );


namespace Packetery\Module\Product;

/**
 * Class Product\EntityFactory.
 *
 * @package Packetery\Module\Product
 */
class EntityFactory {

	/**
	 * Create instance from post ID.
	 *
	 * @param int|string $postId Post ID.
	 *
	 * @return Entity
	 */
	public function fromPostId( $postId ): Entity {
		return Entity::fromPostId( $postId );
	}
}
