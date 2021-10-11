<?php
/**
 * Class Size.
 *
 * @package Packetery\Entity
 */

declare( strict_types=1 );

namespace Packetery\Core\Entity;

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
	 * @param float|null $length Length.
	 * @param float|null $width Width.
	 * @param float|null $height Height.
	 */
	public function __construct( ?float $length, ?float $width, ?float $height ) {
		$this->length = $length;
		$this->width  = $width;
		$this->height = $height;
	}

	/**
	 * Gets length.
	 *
	 * @return float|null
	 */
	public function getLength(): ?float {
		return $this->length;
	}

	/**
	 * Gets width.
	 *
	 * @return float|null
	 */
	public function getWidth(): ?float {
		return $this->width;
	}

	/**
	 * Gets height.
	 *
	 * @return float|null
	 */
	public function getHeight(): ?float {
		return $this->height;
	}
}
