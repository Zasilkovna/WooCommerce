<?php
/**
 * Class ValidationException.
 *
 * @package Packetery\Core
 */

declare( strict_types=1 );

namespace Packetery\Core;

use Exception;

/**
 * Class ValidationException.
 *
 * @package Packetery\Core
 */
class ValidationException extends Exception {

	/**
	 * ValidationResult.
	 *
	 * @var ValidationResult
	 */
	private $validationResult;

	/**
	 * Constructor.
	 *
	 * @param ValidationResult $validationResult Validation result.
	 */
	public function __construct( ValidationResult $validationResult ) {
		parent::__construct( 'Data are invalid' );
		$this->validationResult = $validationResult;
	}

	/**
	 * Gets validation result errors.
	 *
	 * @return string[]
	 */
	public function getValidationErrors(): array {
		return $this->validationResult->getErrors();
	}
}
