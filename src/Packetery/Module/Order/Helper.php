<?php
/**
 * Class Helper
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );


namespace Packetery\Module\Order;

/**
 * Class Helper
 *
 * @package Packetery\Module\Order
 */
class Helper {

	/**
	 * Formats order weight.
	 *
	 * @param float|null $weight Order weight.
	 *
	 * @return string
	 */
	public static function getFormattedWeight( ?float $weight ): string {
		return ( null !== $weight ? number_format( $weight, 3, wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ) : '' );
	}
}
