<?php

declare( strict_types=1 );

namespace Packetery\Module\ProductCategory;

use Packetery\Module\Framework\WpAdapter;

class ProductCategoryEntityFactory {

	private WpAdapter $wpAdapter;

	public function __construct( WpAdapter $wpAdapter ) {
		$this->wpAdapter = $wpAdapter;
	}

	public function fromTermId( int $termId ): Entity {
		$term = $this->wpAdapter->getTerm( $termId );

		return new Entity( $term );
	}

	public function fromTerm( \WP_Term $term ): Entity {
		return new Entity( $term );
	}
}
