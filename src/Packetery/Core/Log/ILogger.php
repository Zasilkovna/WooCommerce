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
	 * Adds log record.
	 *
	 * @param Record $record Record.
	 */
	public function add( Record $record ): void;

	/**
	 * Get logs.
	 *
	 * @param int|null              $orderId Order ID.
	 * @param string|null           $action  Action.
	 * @param array<string, string> $sorting Sorting config.
	 * @param int                   $limit   Limit.
	 *
	 * @return iterable<Record>
	 */
	public function getRecords( ?int $orderId, ?string $action, array $sorting = [], int $limit = 100 ): iterable;

	/**
	 * Counts records.
	 *
	 * @param int|null    $orderId Order ID.
	 * @param string|null $action  Action.
	 *
	 * @return int
	 */
	public function countRecords( ?int $orderId = null, ?string $action = null ): int;
}
