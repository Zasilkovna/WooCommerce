<?php
declare(strict_types=1);

namespace Packetery\Module\Log;

use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Tracy\Logger;

class ArgumentTypeErrorLogger {

	private const LEVEL_ERROR = 'error';

	private WcAdapter $wcAdapter;
	private WpAdapter $wpAdapter;

	public function __construct( WcAdapter $wcAdapter, WpAdapter $wpAdapter ) {
		$this->wcAdapter = $wcAdapter;
		$this->wpAdapter = $wpAdapter;
	}

	private function write( string $message, string $level ): void {
		$wcLogger = $this->wcAdapter->getLogger();

		$wcLogger->log(
			$level,
			Logger::formatMessage( $message ),
			[ 'source' => 'packeta' ]
		);
	}

	/**
	 * @param string $method
	 * @param string $param
	 * @param string $expectedType
	 * @param mixed  $givenValue
	 */
	public function log( string $method, string $param, string $expectedType, $givenValue ): void {
		$message = sprintf(
			'Method %s expects parameter "%s" to be type of "%s", type "%s" given. %s%s',
			$method,
			$param,
			$expectedType,
			is_object( $givenValue ) ? get_class( $givenValue ) : gettype( $givenValue ),
			PHP_EOL,
			( new \Exception() )->getTraceAsString()
		);

		if ( $this->wpAdapter->didAction( 'woocommerce_init' ) > 0 ) {
			$this->write( $message, self::LEVEL_ERROR );
		} else {
			$this->wpAdapter->addAction(
				'woocommerce_init',
				function () use ( $message ): void {
					$this->write( $message, self::LEVEL_ERROR );
				}
			);
		}
	}
}
