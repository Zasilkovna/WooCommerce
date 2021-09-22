<?php
/**
 * Class Size.
 *
 * @package Packetery\Entity
 */

declare( strict_types=1 );

namespace Packetery\Entity;

/**
 * Class Size.
 *
 * @package Packetery\Entity
 */
class Size {
	/**
	 * Length.
	 *
	 * @var float
	 */
	private $length;

	/**
	 * Width.
	 *
	 * @var float
	 */
	private $width;

	/**
	 * Height.
	 *
	 * @var float
	 */
	private $height;

	/**
	 * Size constructor.
	 *
	 * @param float $length Length.
	 * @param float $width Width.
	 * @param float $height Height.
	 */
	public function __construct( float $length, float $width, float $height ) {
		$this->length = $length;
		$this->width  = $width;
		$this->height = $height;
	}

	/**
	 * Gets all properties as array.
	 *
	 * @return array
	 */
	public function __toArray(): array {
		return get_object_vars( $this );
	}
}
