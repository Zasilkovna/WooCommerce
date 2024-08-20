<?php
/**
 * Product category entity.
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module\ProductCategory;

/**
 * Class Entity
 *
 * @package Packetery
 */
class Entity {

	public const META_DISALLOWED_SHIPPING_RATES = 'packetery_disallowed_shipping_rates_by_cat';
	public const TAXONOMY_NAME                  = 'product_cat';

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
	 * Disallowed carrier choices.
	 *
	 * @return array
	 */
	public function getDisallowedShippingRateChoices(): array {
		$choices = get_term_meta( $this->category->term_id, self::META_DISALLOWED_SHIPPING_RATES, true );
		if ( ! is_array( $choices ) ) {
			return [];
		}

		return $choices;
	}

	/**
	 * Disallowed carrier choices.
	 *
	 * @return array
	 */
	public function getDisallowedShippingRateIds(): array {
		return array_keys( $this->getDisallowedShippingRateChoices() );
	}

	/**
	 * Gets product ID.
	 *
	 * @return int
	 */
	public function getId(): int {
		return $this->category->term_id;
	}
}
