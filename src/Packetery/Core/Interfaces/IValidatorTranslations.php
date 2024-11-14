<?php
/**
 * Class IValidatorTranslations
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Interfaces;

/**
 * Class IValidatorTranslations
 *
 * @package Packetery
 */
interface IValidatorTranslations {
	/**
	 * Gets translations for keys specified in validate method.
	 *
	 * @return array<string, string>
	 */
	public function get(): array;
}
