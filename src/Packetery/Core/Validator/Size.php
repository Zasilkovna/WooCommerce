<?php
/**
 * Class Size
 *
 * @package Packetery\Validator
 */

declare( strict_types=1 );

namespace Packetery\Core\Validator;

use Packetery\Core\Entity;

/**
 * Class Size
 *
 * @package Packetery\Validator
 */
class Size {

	/**
	 * Validates data needed to instantiate.
	 *
	 * @param Entity\Size $size Size entity.
	 *
	 * @return SizeReport
	 */
	public function validate( Entity\Size $size ): SizeReport {
		return new SizeReport(
			(bool) $size->getHeight(),
			(bool) $size->getWidth(),
			(bool) $size->getLength()
		);
	}

}
