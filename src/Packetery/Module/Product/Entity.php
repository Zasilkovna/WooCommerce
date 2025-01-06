<?php
/**
 * Product entity.
 *
 * @package Packetery\Module\Product
 */

declare( strict_types=1 );

namespace Packetery\Module\Product;

use Packetery\Core\CoreHelper;
use Packetery\Module\Framework\WpAdapter;

/**
 * Class Entity
 *
 * @package Packetery\Module\Product
 */
class Entity {

	public const META_AGE_VERIFICATION_18_PLUS  = 'packetery_age_verification_18_plus';
	public const META_DISALLOWED_SHIPPING_RATES = 'packetery_disallowed_shipping_rates';

	/**
	 * Product.
	 *
	 * @var \WC_Product
	 */
	private $product;

	/**
	 * Entity constructor.
	 *
	 * @param \WC_Product $product Product.
	 */
	public function __construct( \WC_Product $product ) {
		$this->product = $product;
	}

	/**
	 * Is product relevant for Packeta processing?
	 *
	 * @return bool
	 */
	public function isPhysical(): bool {
		return $this->product->is_virtual() === false && $this->product->is_downloadable() === false;
	}

	/**
	 * Is age verification required?
	 *
	 * @return bool
	 */
	public function isAgeVerificationRequired(): bool {
		return (string) $this->product->get_meta( self::META_AGE_VERIFICATION_18_PLUS ) === '1';
	}

	/**
	 * Disallowed carrier choices.
	 *
	 * @return string[]
	 */
	public function getDisallowedShippingRateChoices(): array {
		$choices = $this->product->get_meta( self::META_DISALLOWED_SHIPPING_RATES );
		if ( ! is_array( $choices ) ) {
			// TODO: log this event, really happened.
			return [];
		}

		return $choices;
	}

	/**
	 * Disallowed carrier ids.
	 *
	 * @return string[]
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
		return $this->product->get_id();
	}

	public function getLengthInCm( WpAdapter $wpAdapter ): float {
		return round(
			CoreHelper::convertToCentimeters(
				(float) $this->product->get_length(),
				(string) $wpAdapter->getOption( 'woocommerce_dimension_unit' )
			)
		);
	}

	public function getWidthInCm( WpAdapter $wpAdapter ): float {
		return round(
			CoreHelper::convertToCentimeters(
				(float) $this->product->get_width(),
				(string) $wpAdapter->getOption( 'woocommerce_dimension_unit' )
			)
		);
	}

	public function getHeightInCm( WpAdapter $wpAdapter ): float {
		return round(
			CoreHelper::convertToCentimeters(
				(float) $this->product->get_height(),
				(string) $wpAdapter->getOption( 'woocommerce_dimension_unit' )
			)
		);
	}
}
