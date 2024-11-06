<?php
/**
 * Class Rounder
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core;

use InvalidArgumentException;

/**
 * Class Rounder
 *
 * Class for rounding numbers e.g. prices
 *
 * @package Packetery
 */
class Rounder {

	public const ROUND_UP       = 1;
	public const ROUND_DOWN     = -1;
	public const DONT_ROUND     = 0;
	public const ROUNDING_TYPES = [ self::ROUND_UP, self::ROUND_DOWN, self::DONT_ROUND ];

	/**
	 * Returns rounded amount.
	 *
	 * @param float $amount Amount.
	 * @param int   $roundingType Type of rounding.
	 * @param int   $precision Precision (number of digits after decimal point).
	 *
	 * @return float
	 * @throws InvalidArgumentException InvalidArgumentException.
	 */
	public static function round( float $amount, int $roundingType, int $precision ): float {
		if ( ( $precision < 0 ) || ! in_array( $roundingType, self::ROUNDING_TYPES, true ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'Precision should be non-negative number, roundingType should be one of %s.',
					implode( ', ', self::ROUNDING_TYPES )
				)
			);
		}

		$amount *= 10 ** $precision;
		$amount  = self::roundFloat( $amount, $roundingType );
		$amount /= ( 10 ** $precision );

		return $amount;
	}

	/**
	 * Rounds amount up according to precision.
	 *
	 * @param float $amount Amount to round.
	 * @param int   $precision Precision (number of digits after decimal point).
	 *
	 * @return float
	 */
	public static function roundUp( float $amount, int $precision = 0 ): float {
		return self::round( $amount, self::ROUND_UP, $precision );
	}

	/**
	 * Rounds amount up according to precision.
	 *
	 * @param float $amount Amount to round.
	 * @param int   $precision Precision (number of digits after decimal point).
	 *
	 * @return float
	 */
	public static function roundDown( float $amount, int $precision = 0 ): float {
		return self::round( $amount, self::ROUND_DOWN, $precision );
	}

	/**
	 * Returns rounded amount to multiple of $number.
	 *
	 * @param float $amount Amount.
	 * @param int   $roundingType Type of rounding.
	 * @param int   $divisor Round to multiple of this number.
	 *
	 * @return float
	 * @throws InvalidArgumentException InvalidArgumentException.
	 */
	public static function roundToMultipleOfNumber( float $amount, int $roundingType, int $divisor ): float {
		if ( ( $divisor < 1 ) || ! in_array( $roundingType, self::ROUNDING_TYPES, true ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'Divisor should be positive number, roundingType should be one of %s.',
					implode( ', ', self::ROUNDING_TYPES )
				)
			);
		}

		$amount /= $divisor;
		$amount  = self::roundFloat( $amount, $roundingType );

		return $divisor * $amount;
	}

	/**
	 * Returns rounded amount according to currency and currency constraints of Packeta API
	 *
	 * @param float  $amount Amount.
	 * @param string $currencyCode Currency code.
	 * @param int    $roundingType Type of rounding.
	 *
	 * @return float
	 * @throws InvalidArgumentException InvalidArgumentException.
	 */
	public static function roundByCurrency( float $amount, string $currencyCode, int $roundingType ): float {
		if ( ! in_array( $roundingType, self::ROUNDING_TYPES, true ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'RoundingType should should be one of %s.',
					implode( ', ', self::ROUNDING_TYPES )
				)
			);
		}

		if ( 'CZK' === $currencyCode ) {
			return self::round( $amount, $roundingType, 0 );
		}

		if ( 'HUF' === $currencyCode ) {
			return self::roundToMultipleOfNumber( $amount, $roundingType, 5 );
		}

		return self::round( $amount, $roundingType, 2 );
	}

	/**
	 * Returns rounded amount.
	 *
	 * @param float $amount Amount to round.
	 * @param int   $roundingType Type of rounding.
	 *
	 * @return float
	 */
	private static function roundFloat( float $amount, int $roundingType ): float {
		if ( self::ROUND_DOWN === $roundingType ) {
			return floor( self::sanitizeFloat( $amount ) );
		}
		if ( self::ROUND_UP === $roundingType ) {
			return ceil( self::sanitizeFloat( $amount ) );
		}

		return $amount;
	}

	/**
	 * Solves float precision problems, for example that (1.15 * 100) !== (float) 115.
	 * Works with locales not using decimal dot.
	 *
	 * @param float $number Number to sanitize.
	 *
	 * @return float
	 */
	private static function sanitizeFloat( float $number ): float {
		$oldLocale = setlocale( LC_NUMERIC, '0' );
		if ( false !== $oldLocale ) {
			setlocale( LC_NUMERIC, 'C' );
		}
		$newNumber = (float) ( (string) $number );
		if ( false !== $oldLocale ) {
			setlocale( LC_NUMERIC, $oldLocale );
		}

		return $newNumber;
	}

}
