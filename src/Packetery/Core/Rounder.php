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
 * @package Packetery
 */
class Rounder {
	public const ROUND_UP   = 1;
	public const ROUND_DOWN = -1;
	public const DONT_ROUND = 0;

	/**
	 * Returns rounded amount.
	 *
	 * @param float $amount Amount.
	 * @param int   $roundingType Type of rounding.
	 * @param int   $precision Precision (number of digits after decimal point).
	 *
	 * @return float
	 */
	private static function customRound( float $amount, int $roundingType, int $precision ): float {
		$amount *= 10 ** $precision;

		if ( self::ROUND_DOWN === $roundingType ) {
			$amount = floor( (float) ( (string) $amount ) ); // conversion float to string to float should fix float precision problems.
		}

		if ( self::ROUND_UP === $roundingType ) {
			$amount = ceil( (float) ( (string) $amount ) );
		}

		$amount /= ( 10 ** $precision );

		return $amount;
	}

	/**
	 * Returns rounded amount to multiple of 5.
	 *
	 * @param float $amount Amount.
	 * @param int   $roundingType Type of rounding.
	 *
	 * @return float
	 */
	private static function roundToMultipleOfFive( float $amount, int $roundingType ): float {
		$amount /= 5;

		if ( self::ROUND_UP === $roundingType ) {
			$amount = ceil( (float) ( (string) $amount ) );
		}

		if ( self::ROUND_DOWN === $roundingType ) {
			$amount = floor( (float) ( (string) $amount ) );
		}

		return 5 * $amount;
	}

	/**
	 * Returns rounded amount according to currency and currency constraints of Packeta API
	 *
	 * @param float  $amount Amount.
	 * @param string $currencyCode Currency code.
	 * @param int    $roundingType Type of rounding.
	 *
	 * @return float
	 */
	public static function roundByCurrency( float $amount, string $currencyCode, int $roundingType ): float {
		if ( 'CZK' === $currencyCode ) {
			return self::customRound( $amount, $roundingType, 0 );
		}

		if ( 'HUF' === $currencyCode ) {
			return self::roundToMultipleOfFive( $amount, $roundingType );
		}

		return self::customRound( $amount, $roundingType, 2 );
	}
}
