<?php
/**
 * Class Helper
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Core;

/**
 * Class Helper
 *
 * @package Packetery
 */
class Helper {
	public const TRACKING_URL          = 'https://tracking.packeta.com/?id=%s';
	public const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';
	public const ROUND_UP              = 1;
	public const ROUND_DOWN            = -1;
	public const DONT_ROUND            = 0;

	/**
	 * Simplifies weight.
	 *
	 * @param float|null $weight Weight.
	 *
	 * @return float|null
	 */
	public static function simplifyWeight( ?float $weight ): ?float {
		return self::simplifyFloat( $weight, 3 );
	}

	/**
	 * Simplifies float value to have max decimal places.
	 *
	 * @param float|null $value            Value.
	 * @param int        $maxDecimalPlaces Max decimal places.
	 *
	 * @return float|null
	 */
	public static function simplifyFloat( ?float $value, int $maxDecimalPlaces ): ?float {
		if ( null === $value ) {
			return null;
		}

		return (float) number_format( $value, $maxDecimalPlaces, '.', '' );
	}

	/**
	 * Returns tracking URL.
	 *
	 * @param string $packet_id Packet ID.
	 *
	 * @return string
	 */
	public function get_tracking_url( string $packet_id ): string {
		return sprintf( self::TRACKING_URL, rawurlencode( $packet_id ) );
	}

	/**
	 * Creates UTC DateTime.
	 *
	 * @return \DateTimeImmutable
	 * @throws \Exception From DateTimeImmutable.
	 */
	public static function now(): \DateTimeImmutable {
		return new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
	}

	/**
	 * Returns rounded amount.
	 *
	 * @param float $amount Amount.
	 * @param int   $roundingType Type of rounding.
	 * @param int   $precision Precision (number of digits after decimal point).
	 *
	 * @return float
	 */
	public static function customRound( float $amount, int $roundingType, int $precision ): float {
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
	 * @param int    $roundingType Type of rounding.
	 * @param string $currencyCode Currency code.
	 *
	 * @return float
	 */
	public static function customRoundByCurrency( float $amount, int $roundingType, string $currencyCode ): float {
		if ( 'CZK' === $currencyCode ) {
			return self::customRound( $amount, $roundingType, 0 );
		}

		if ( 'HUF' === $currencyCode ) {
			return self::roundToMultipleOfFive( $amount, $roundingType );
		}

		return self::customRound( $amount, $roundingType, 2 );
	}
}
