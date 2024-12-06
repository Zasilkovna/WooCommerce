<?php
/**
 * Class Product\ProductEntityFactory.
 *
 * @package Packetery\Module\Product
 */

declare( strict_types=1 );

namespace Packetery\Module\Product;

use Packetery\Module\Exception\ProductNotFoundException;
use Packetery\Module\Framework\WcAdapter;

/**
 * Class Product\ProductEntityFactory.
 *
 * @package Packetery\Module\Product
 */
class ProductEntityFactory {

	/**
	 * WP adapter.
	 *
	 * @var WcAdapter
	 */
	private $wcAdapter;

	/**
	 * Constructor.
	 *
	 * @param WcAdapter $wcAdapter WC adapter.
	 */
	public function __construct( WcAdapter $wcAdapter ) {
		$this->wcAdapter = $wcAdapter;
	}

	/**
	 * Create instance from post ID.
	 *
	 * @param int|string $postId Post ID.
	 *
	 * @return Entity
	 * @throws ProductNotFoundException Product not found.
	 */
	public function fromPostId( $postId ): Entity {
		$product = $this->wcAdapter->getProduct( $postId );
		if ( ! ( $product instanceof \WC_Product ) ) {
			throw new ProductNotFoundException( "Product $postId not found." );
		}

		return new Entity( $product );
	}

	/**
	 * Creates instance using global variables.
	 *
	 * @return Entity
	 */
	public function fromGlobals(): Entity {
		global $post;

		return $this->fromPostId( $post->ID );
	}
}
