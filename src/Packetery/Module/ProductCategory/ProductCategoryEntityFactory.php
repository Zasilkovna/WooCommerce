<?php
/**
 * Product category entity factory.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\ProductCategory;

/**
 * Class ProductCategoryEntityFactory
 *
 * @package Packetery
 */
class ProductCategoryEntityFactory {

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
