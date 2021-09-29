<?php
/**
 * Product entity.
 *
 * @package Packetery\Product
 */

declare( strict_types=1 );


namespace Packetery\Product;

/**
 * Class Entity
 *
 * @package Packetery\Product
 */
class Entity {

	public const META_AGE_VERIFICATION_18_PLUS = 'packetery_age_verification_18_plus';

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
	 * Creates instance using global variables.
	 *
	 * @return static
	 */
	public static function fromGlobals(): self {
		global $post;

		return self::fromPostId( $post->ID );
	}

	/**
	 * Create instance from post ID.
	 *
	 * @param int|string $postId Post ID.
	 *
	 * @return static
	 */
	public static function fromPostId( $postId ): self {
		$product = wc_get_product( $postId );

		return new self( $product );
	}

	/**
	 * Is product relevant for Packeta processing?
	 *
	 * @return bool
	 */
	public function isPhysical(): bool {
		return false === $this->product->is_virtual() && false === $this->product->is_downloadable();
	}

	/**
	 * Is age verification required?
	 *
	 * @return bool
	 */
	public function isAgeVerification18PlusRequired(): bool {
		return $this->product->get_meta( self::META_AGE_VERIFICATION_18_PLUS ) === '1';
	}

	/**
	 * Gets product ID.
	 *
	 * @return int
	 */
	public function getId(): int {
		return $this->product->get_id();
	}
}
