<?php
/**
 * Class ValidatorTranslationsInterface
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Interfaces;

/**
 * Class ValidatorTranslationsInterface
 *
 * @package Packetery
 */
interface ValidatorTranslationsInterface {
	/**
	 * Gets translations for keys specified in validate method.
	 *
	 * @return array<string, string>
	 */
	public function get(): array;
}
