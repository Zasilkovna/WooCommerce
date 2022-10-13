<?php
/**
 * Class Rounder
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Core;

/**
 * Class Rounder
 *
 * Class for rounding NON-NEGATIVE numbers
 *
 * @package Packetery
 */
class Rounder {
	public const ROUND_UP       = 1;
	public const ROUND_DOWN     = -1;
	public const DONT_ROUND     = 0;
	public const ROUNDING_TYPES = [ self::ROUND_UP, self::ROUND_DOWN, self::DONT_ROUND ];

	/**
	 * Returns ceil of amount. Should solve float precision problems along the way.
	 *
	 * @param float $amount Amount to ceil.
	 *
	 * @return float
	 */
	public static function ceil( float $amount ): float {
		return ceil( (float) ( (string) $amount ) );
	}

	/**
	 * Returns floor of amount. Should solve float precision problems along the way.
	 *
	 * @param float $amount Amount to floor.
	 *
	 * @return float
	 */
	public static function floor( float $amount ): float {
		return floor( (float) ( (string) $amount ) );
	}

	/**
	 * Returns rounded amount.
	 *
	 * @param float $amount Amount.
	 * @param int   $roundingType Type of rounding.
	 * @param int   $precision Precision (number of digits after decimal point).
	 *
	 * @return float
	 * @throws \InvalidArgumentException InvalidArgumentException.
	 */
	public static function round( float $amount, int $roundingType, int $precision ): float {
		if ( ( $amount < 0 ) || ( $precision < 0 ) || ! in_array( $roundingType, self::ROUNDING_TYPES, true ) ) {
			throw new \InvalidArgumentException( 'Amount and precision should be non-negative numbers, roundingType should be one of [-1, 0, 1].' );
		}

		$amount *= 10 ** $precision;

		if ( self::ROUND_DOWN === $roundingType ) {
			$amount = self::floor( $amount );
		}

		if ( self::ROUND_UP === $roundingType ) {
			$amount = self::ceil( $amount );
		}

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
	 * @param int   $number Round to multiple of this.
	 *
	 * @return float
	 * @throws \InvalidArgumentException InvalidArgumentException.
	 */
	public static function roundToMultipleOfNumber( float $amount, int $roundingType, int $number ): float {
		if ( ( $amount < 0 ) || ( $number < 1 ) || ! in_array( $roundingType, self::ROUNDING_TYPES, true ) ) {
			throw new \InvalidArgumentException( 'Amount should be non-negative number, number should be positive number roundingType should be one of [-1, 0, 1].' );
		}

		$amount /= $number;

		if ( self::ROUND_DOWN === $roundingType ) {
			$amount = self::floor( $amount );
		}

		if ( self::ROUND_UP === $roundingType ) {
			$amount = self::ceil( $amount );
		}

		return $number * $amount;
	}

	/**
	 * Returns rounded amount according to currency and currency constraints of Packeta API
	 *
	 * @param float  $amount Amount.
	 * @param string $currencyCode Currency code.
	 * @param int    $roundingType Type of rounding.
	 *
	 * @return float
	 * @throws \InvalidArgumentException InvalidArgumentException.
	 */
	public static function roundByCurrency( float $amount, string $currencyCode, int $roundingType ): float {
		if ( ( $amount < 0 ) || ! in_array( $roundingType, self::ROUNDING_TYPES, true ) ) {
			throw new \InvalidArgumentException( 'Amount should be non-negative number, roundingType should be one of [-1, 0, 1].' );
		}

		if ( 'CZK' === $currencyCode ) {
			return self::round( $amount, $roundingType, 0 );
		}

		if ( 'HUF' === $currencyCode ) {
			return self::roundToMultipleOfNumber( $amount, $roundingType, 5 );
		}

		return self::round( $amount, $roundingType, 2 );
	}

}
