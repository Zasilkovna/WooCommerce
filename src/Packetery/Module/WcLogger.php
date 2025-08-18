<?php
/**
 * Class Upgrade.
 *
 * @package Packetery
 */

declare(strict_types=1);

namespace Packetery\Module;

use Packetery\Tracy\Logger;

/**
 * Class WcLogger.
 */
class WcLogger {

	private const LEVEL_ERROR = 'error';

	/**
	 * @param string|\Throwable|mixed $message
	 * @param string                  $level
	 * @return void
	 */
	private static function log( $message, string $level ): void {
		$logCallback = static function () use ( $level, $message ): void {
			/** WC_Logger is always returned. @noinspection NullPointerExceptionInspection */
			wc_get_logger()->log(
				$level,
				Logger::formatMessage( $message ),
				[ 'source' => 'packeta' ]
			);
		};

		if ( did_action( 'woocommerce_init' ) > 0 ) {
			$logCallback();
		} else {
			add_action( 'woocommerce_init', $logCallback );
		}
	}

	/**
	 * @param string $method
	 * @param string $param
	 * @param string $expectedType
	 * @param mixed  $givenValue
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
