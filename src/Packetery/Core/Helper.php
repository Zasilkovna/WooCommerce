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
	public const DATEPICKER_FORMAT     = 'Y-m-d';

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
	 */
	public static function now(): \DateTimeImmutable {
		return ( new \DateTimeImmutable() )->setTimezone( new \DateTimeZone( 'UTC' ) );
	}

	/**
	 * Creates string in given format from DateTimeImmutable object
	 *
	 * @param \DateTimeImmutable|null $date   Datetime.
	 * @param string                  $format Datetime format.
	 *
	 * @return string|null
	 */
	public static function getStringFromDateTime( ?\DateTimeImmutable $date, string $format ): ?string {
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
	public static function getDateTimeFromString( ?string $date ): ?\DateTimeImmutable {
		return $date ? new \DateTimeImmutable( $date ) : null;
	}
}
