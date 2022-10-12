<?php
/**
 * Class ValidationResult.
 *
 * @package Packetery\Core
 */

declare( strict_types=1 );

namespace Packetery\Core;

/**
 * Class ValidationResult.
 *
 * @package Packetery\Core
 */
class ValidationResult {

	/**
	 * Errors.
	 *
	 * @var array
	 */
	private $errors = [];

	/**
	 * Adds error.
	 *
	 * @param string $error Error.
	 *
	 * @return void
	 */
	public function addError( string $error ): void {
		$this->errors[] = $error;
	}

	/**
	 * Tells if report has error.
	 *
	 * @return bool
	 */
	public function hasError(): bool {
		return ! empty( $this->errors );
	}

	/**
	 * Gets errors.
	 *
	 * @return string[]
	 */
	public function getErrors(): array {
		return $this->errors;
	}
}
