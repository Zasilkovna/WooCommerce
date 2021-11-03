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
	public const TRACKING_URL = 'https://tracking.packeta.com/?id=%s';

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

}
