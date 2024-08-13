<?php
/**
 * Product category entity factory.
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module\ProductCategory;

/**
 * Class EntityFactory
 *
 * @package Packetery
 */
class EntityFactory {

	/**
	 * Create instance from term ID.
	 *
	 * @param int $termId Term ID.
	 *
	 * @return Entity
	 */
	public function fromTermId( int $termId ): Entity {
		return Entity::fromTermId( $termId );
	}
}
