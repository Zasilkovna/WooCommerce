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
	 * @param mixed $orderId Order ID.
	 * @param array $sorting Sorting.
	 * @param int   $limit Sorting.
	 *
	 * @return iterable
	 */
	public function getRecords( $orderId, array $sorting = [], int $limit ): iterable;

	/**
	 * Counts records.
	 *
	 * @param mixed $orderId Order ID.
	 *
	 * @return int
	 */
	public function countRecords( $orderId ): int;
}
