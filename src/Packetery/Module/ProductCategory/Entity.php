<?php
/**
 * Product entity.
 *
 * @package Packetery\Module\Product
 */

declare( strict_types=1 );


namespace Packetery\Module\ProductCategory;

/**
 * Class Entity
 *
 * @package Packetery\Module\ProductCategory
 */
class Entity {

	public const META_DISALLOWED_CARRIERS = 'packetery_disallowed_carriers_by_cat';
	public const TAXONOMY_NAME            = 'product_cat';

	/**
	 * Category.
	 *
	 * @var \WP_Term
	 */
	private $category;

	/**
	 * Entity constructor.
	 *
	 * @param \WP_term $category Category.
	 */
	public function __construct( \WP_term $category ) {
		$this->category = $category;
	}

	/**
	 * Create instance from term ID.
	 *
	 * @param int|string $termId Term ID.
	 *
	 * @return static
	 */
	public static function fromTermId( $termId ): self {
		$product = get_term( $termId );

		return new self( $product );
	}

	/**
	 * Disallowed carrier choices.
	 *
	 * @return array
	 */
	public function getDisallowedCarriers(): array {
		$choices = get_term_meta( $this->category->term_id, self::META_DISALLOWED_CARRIERS, true );
		if ( ! $choices ) {
			return [];
		}

		return $choices;
	}

	/**
	 * Disallowed carrier ids.
	 *
	 * @return array
	 */
	public function getDisallowedCarriersIds(): array {
		return array_keys( $this->getDisallowedCarriers() );
	}

	/**
	 * Gets product ID.
	 *
	 * @return int
	 */
	public function getId(): int {
		return $this->category->term_id;
	}

	/**
	 * Check if given term is category
	 *
	 * @param \WP_Term $term WP_Term.
	 *
	 * @return int|mixed|string|string[]|null
	 */
	public static function isValidCategory( \WP_Term $term ) {
		return term_exists( $term->term_id, self::TAXONOMY_NAME );
	}
}
