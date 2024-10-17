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
	public const TRACKING_URL          = 'https://tracking.packeta.com/?id=%s';
	public const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';
	public const MYSQL_DATE_FORMAT     = 'Y-m-d';
	public const DATEPICKER_FORMAT     = 'Y-m-d';
	public const DATEPICKER_FORMAT_JS  = 'yy-mm-dd';

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
	 * Trims the decimals to a desired format.
	 *
	 * @param float $value Value.
	 * @param int   $position Position of a decimal.
	 *
	 * @return string
	 */
	public static function trimDecimalPlaces( float $value, int $position ): string {
		return rtrim( number_format( $value, $position, '.', '' ), '0.' );
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
	 * @return DateTimeImmutable
	 * @throws \Exception From DateTimeImmutable.
	 */
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
		return $date ? $date->format( $format ) : null;
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
		return $date ? new DateTimeImmutable( $date ) : null;
	}
}
