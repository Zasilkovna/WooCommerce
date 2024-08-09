<?php
/**
 * Class SizeReport
 *
 * @package Packetery
 */

namespace Packetery\Core\Validator;

/**
 * Class SizeReport
 *
 * @package Packetery
 */
class SizeReport {

	/**
	 * Tells if height is valid.
	 *
	 * @var bool
	 */
	private $isHeightValid;

	/**
	 * Tells if length is valid.
	 *
	 * @var bool
	 */
	private $isLengthValid;

	/**
	 * Tells if width is valid.
	 *
	 * @var bool
	 */
	private $isWidthValid;

	/**
	 * Constructor.
	 *
	 * @param bool $isHeightValid Tells if height is valid.
	 * @param bool $isWidthValid Tells if width is valid.
	 * @param bool $isLengthValid Tells if length is valid.
	 */
	public function __construct( bool $isHeightValid, bool $isWidthValid, bool $isLengthValid ) {
		$this->isHeightValid = $isHeightValid;
		$this->isWidthValid  = $isWidthValid;
		$this->isLengthValid = $isLengthValid;
	}

	/**
	 * Tells if height is valid.
	 *
	 * @return bool
	 */
	public function isHeightValid(): bool {
		return $this->isHeightValid;
	}

	/**
	 * Tells if width is valid.
	 *
	 * @return bool
	 */
	public function isWidthValid(): bool {
		return $this->isWidthValid;
	}

	/**
	 * Tells if length valid.
	 *
	 * @return bool
	 */
	public function isLengthValid(): bool {
		return $this->isLengthValid;
	}
}
