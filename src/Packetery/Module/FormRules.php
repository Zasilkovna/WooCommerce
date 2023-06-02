<?php
/**
 * Class FormRules.
 *
 * @package Packetery
 */

declare(strict_types=1);

namespace Packetery\Module;

/**
 * Class FormRules.
 */
class FormRules {

	/**
	 * Creates greaterThan validator parts for addRule form method.
	 *
	 * @param float $threshold Threshold.
	 * @return array
	 */
	public static function getGreaterThanParts( float $threshold ): array {
		return [
			[ FormValidators::class, 'greaterThan' ],
			// translators: %d is numeric threshold.
			__( 'Enter number greater than %d', 'packeta' ),
			$threshold,
		];
	}
}
