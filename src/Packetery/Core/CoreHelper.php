<?php
/**
 * Class CoreHelper
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core;

use DateTimeImmutable;

/**
 * Class CoreHelper
 *
 * @package Packetery
 */
class CoreHelper {
	public const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';
	public const MYSQL_DATE_FORMAT     = 'Y-m-d';
	public const DATEPICKER_FORMAT     = 'Y-m-d';
	public const DATEPICKER_FORMAT_JS  = 'yy-mm-dd';

	/**
	 * @var string
	 */
	private $trackingUrl;

	public function __construct( string $trackingUrl ) {
		$this->trackingUrl = $trackingUrl;
	}

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
		if ( $value === null ) {
			return null;
		}

		return (float) number_format( $value, $maxDecimalPlaces, '.', '' );
	}

	public static function trimDecimalPlaces( ?float $value, int $decimals ): ?string {
		if ( $value === null ) {
			return null;
		}

		$formattedValue = number_format( $value, $decimals, '.', '' );

		if ( $decimals > 0 ) {
			$valueWithoutTrailingZeros = rtrim( $formattedValue, '0' );

			return rtrim( $valueWithoutTrailingZeros, '.' );
		}

		return $formattedValue;
	}

	public function getTrackingUrl( ?string $packetId ): ?string {
		if ( $packetId === null ) {
			return null;
		}

		return sprintf( $this->trackingUrl, rawurlencode( $packetId ) );
	}

	public static function now(): DateTimeImmutable {
		return new DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
	}

	/**
	 * Creates string in given format from DateTimeImmutable object
	 *
	 * @param DateTimeImmutable|null $date   Datetime.
	 * @param string                 $format Datetime format.
	 *
	 * @return string|null
	 */
	public function getStringFromDateTime( ?DateTimeImmutable $date, string $format ): ?string {
		return $date !== null ? $date->format( $format ) : null;
	}

	/**
	 * Creates DateTimeImmutable object from string
	 *
	 * @param string $date Date.
	 *
	 * @return \DateTimeImmutable
	 * @throws \Exception From DateTimeImmutable.
	 */
	public function getDateTimeFromString( ?string $date ): ?DateTimeImmutable {
		return $date !== null && $date !== '' ? new DateTimeImmutable( $date ) : null;
	}

	public static function convertToCentimeters( float $value, string $fromUnit ): float {
		$conversionFactors = [
			'cm' => 1,
			'm'  => 100,
			'mm' => 0.1,
			'in' => 2.54,
			'yd' => 91.44,
		];

		if ( ! isset( $conversionFactors[ $fromUnit ] ) ) {
			// Do not convert unknown values.
			return $value;
		}

		return $value * $conversionFactors[ $fromUnit ];
	}
}
