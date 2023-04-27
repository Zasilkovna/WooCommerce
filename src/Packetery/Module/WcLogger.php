<?php
/**
 * Class Upgrade.
 *
 * @package Packetery
 */

declare(strict_types=1);

namespace Packetery\Module;

use PacketeryTracy\Logger;

/**
 * Class WcLogger.
 */
class WcLogger {

	public const LEVEL_INFO  = 'info';
	public const LEVEL_ERROR = 'error';

	/**
	 * Logs message to WooCommerce storage.
	 *
	 * @param string|\Throwable|mixed $message Message to log.
	 * @param string                  $level Log level.
	 * @return void
	 */
	public static function log( $message, string $level = self::LEVEL_INFO ): void {
		$logCallback = static function () use ( $level, $message ): void {
			wc_get_logger()->log(
				$level,
				Logger::formatMessage( $message ),
				[ 'source' => 'packeta' ]
			);
		};

		if ( did_action( 'woocommerce_init' ) ) {
			$logCallback();
		} else {
			add_action( 'woocommerce_init', $logCallback );
		}
	}

	/**
	 * Logs argument type error.
	 *
	 * @param string $method Method.
	 * @param string $param Param.
	 * @param string $expectedType Expected type.
	 * @param mixed  $givenValue Given value.
	 * @return void
	 */
	public static function logArgumentTypeError( string $method, string $param, string $expectedType, $givenValue ): void {
		self::log(
			sprintf(
				'Method %s expects parameter "%s" to be type of "%s", type "%s" given. %s%s',
				$method,
				$param,
				$expectedType,
				is_object( $givenValue ) ? get_class( $givenValue ) : gettype( $givenValue ),
				PHP_EOL,
				( new \Exception() )->getTraceAsString()
			),
			self::LEVEL_ERROR
		);
	}
}
