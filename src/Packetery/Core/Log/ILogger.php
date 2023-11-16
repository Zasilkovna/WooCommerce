<?php
/**
 * Interface ILogger
 *
 * @package Packetery\Log
 */

declare( strict_types=1 );


namespace Packetery\Core\Log;

/**
 * Interface ILogger
 *
 * @package Packetery\Log
 */
interface ILogger {

	/**
	 * Registers log driver.
	 */
	public function register(): void;

	/**
	 * Adds log record.
	 *
	 * @param Record $record Record.
	 */
	public function add( Record $record ): void;

	/**
	 * Get logs.
	 *
	 * @param mixed                 $orderId Order ID.
	 * @param string|null           $action  Action.
	 * @param array<string, string> $sorting Sorting config.
	 * @param int                   $limit   Limit.
	 *
	 * @return iterable<Record>
	 */
	public function getRecords( $orderId, ?string $action, array $sorting = [], int $limit = 100 ): iterable;

	/**
	 * Counts records.
	 *
	 * @param mixed       $orderId Order ID.
	 * @param string|null $action  Action.
	 *
	 * @return int
	 */
	public function countRecords( $orderId = null, ?string $action = null): int;
}
