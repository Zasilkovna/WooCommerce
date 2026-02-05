<?php

declare( strict_types=1 );

namespace Packetery\Module\ProductCategory;

use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Log\ArgumentTypeErrorLogger;

class CategoryGridExtender {

	public const COLUMN_SHIPPING_RESTRICTIONS = 'packetery_shipping_restrictions';

	private ProductCategoryEntityFactory $productCategoryEntityFactory;
	private ArgumentTypeErrorLogger $argumentTypeErrorLogger;
	private WpAdapter $wpAdapter;

	public function __construct(
		ProductCategoryEntityFactory $productCategoryEntityFactory,
		ArgumentTypeErrorLogger $argumentTypeErrorLogger,
		WpAdapter $wpAdapter
	) {
		$this->productCategoryEntityFactory = $productCategoryEntityFactory;
		$this->argumentTypeErrorLogger      = $argumentTypeErrorLogger;
		$this->wpAdapter                    = $wpAdapter;
	}

	/**
	 * @param array<string, string>|mixed $columns
	 * @return array<string, string>|mixed
	 */
	public function addCategoryListColumns( $columns ) {
		if ( ! is_array( $columns ) ) {
			$this->argumentTypeErrorLogger->log( __METHOD__, 'columns', 'array', $columns );

			return $columns;
		}

		$columns[ self::COLUMN_SHIPPING_RESTRICTIONS ] = $this->wpAdapter->__( 'Shipping method restrictions', 'packeta' );

		return $columns;
	}

	/**
	 * @param string|mixed $content
	 * @param string|mixed $columnName
	 * @param int|mixed    $termId
	 */
	public function fillCategoryListColumn( $content, $columnName, $termId ): string {
		if ( $columnName !== self::COLUMN_SHIPPING_RESTRICTIONS ) {
			return is_string( $content ) ? $content : '';
		}

		if ( ! is_int( $termId ) && ! is_numeric( $termId ) ) {
			return '';
		}

		$termIdInt = (int) $termId;
		$entity    = $this->getEntityByTermId( $termIdInt );

		if ( $entity === null ) {
			return '';
		}

		$hasRestrictions = $entity->getDisallowedShippingRateIds() !== [];
		$cellContent     = $hasRestrictions ? $this->wpAdapter->__( 'Yes', 'packeta' ) : '—';

		return esc_html( $cellContent );
	}

	/**
	 * @param string[]|mixed   $hidden
	 * @param \WP_Screen|mixed $screen
	 * @return string[]|mixed
	 */
	public function hideCategoryListColumnByDefault( $hidden, $screen ) {
		if ( ! is_array( $hidden ) ) {
			return $hidden;
		}

		if ( ! $screen instanceof \WP_Screen ) {
			return $hidden;
		}

		if ( $screen->id !== 'edit-product_cat' ) {
			return $hidden;
		}

		$hidden[] = self::COLUMN_SHIPPING_RESTRICTIONS;

		return $hidden;
	}

	private function getEntityByTermId( int $termId ): ?Entity {
		static $entityCache = [];

		if ( ! isset( $entityCache[ $termId ] ) ) {
			$term = $this->wpAdapter->getTerm( $termId );

			if ( ! $term instanceof \WP_Term ) {
				$entityCache[ $termId ] = null;

				return null;
			}

			$entityCache[ $termId ] = $this->productCategoryEntityFactory->fromTerm( $term );
		}

		return $entityCache[ $termId ];
	}
}
