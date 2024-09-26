<?php
/**
 * Product category entity factory.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\ProductCategory;

use Packetery\Module\Framework\WpAdapter;

/**
 * Class ProductCategoryEntityFactory
 *
 * @package Packetery
 */
class ProductCategoryEntityFactory {

	/**
	 * WP adapter.
	 *
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * Constructor.
	 *
	 * @param WpAdapter $wpAdapter WP adapter.
	 */
	public function __construct( WpAdapter $wpAdapter ) {
		$this->wpAdapter = $wpAdapter;
	}

	/**
	 * Create instance from term ID.
	 *
	 * @param int $termId Term ID.
	 *
	 * @return Entity
	 */
	public function fromTermId( int $termId ): Entity {
		$product = $this->wpAdapter->getTerm( $termId );

		return new Entity( $product );
	}

}
