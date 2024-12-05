<?php
/**
 * Class FormValidators
 *
 * @package Packetery\Module
 */

declare(strict_types=1);

namespace Packetery\Module;

use DateTimeImmutable;
use Packetery\Core\CoreHelper;
use Packetery\Nette\Forms\Controls\BaseControl;

/**
 * Class FormValidators
 *
 * @package Packetery\Module
 */
class FormValidators {

	/**
	 * Tests if input value is greater than argument.
	 *
	 * @param BaseControl $input Form input.
	 * @param float       $arg Validation argument.
	 *
	 * @return bool
	 */
	public static function greaterThan( BaseControl $input, float $arg ): bool {
		return $input->getValue() > $arg;
	}

	/**
	 * Tests if input date is later than argument date
	 *
	 * @param BaseControl $input Form input.
	 * @param string      $date Validation argument.
	 *
	 * @return bool
	 */
	public static function dateIsLater( BaseControl $input, string $date ): bool {
		return strtotime( $input->getValue() ) > strtotime( $date );
	}

	/**
	 * Tests if input date is in proper format.
	 *
	 * @param BaseControl $input Form input.
	 *
	 * @return bool
	 */
	public static function dateIsInMysqlFormat( BaseControl $input ): bool {
		$date = DateTimeImmutable::createFromFormat(
			CoreHelper::MYSQL_DATE_FORMAT,
			$input->getValue()
		);

		if ( false === $date ) {
			return false;
		}

		return ( $input->getValue() === $date->format( CoreHelper::MYSQL_DATE_FORMAT ) );
	}

	/**
	 * Tests if input time is in Clock-Time(00:00-23:59) format.
	 *
	 * @param BaseControl $input Form input.
	 * @return bool
	 */
	public static function hasClockTimeFormat( BaseControl $input ): bool {
		$value   = $input->getValue();
		$pattern = '/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/';

		if ( ! preg_match( $pattern, $value ) ) {
			return false;
		}

		return true;
	}

}
