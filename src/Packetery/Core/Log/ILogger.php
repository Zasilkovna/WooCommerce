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
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public function add( Record $record );

	/**
	 * @param LogPageArguments $arguments
	 *
	 * @return iterable<Record>
	 */
	public function getRecords( LogPageArguments $arguments ): iterable;

	/**
	 * @param LogPageArguments $arguments
	 */
	public function countRecords( LogPageArguments $arguments ): int;

	/**
	 * @param string $before DateTime modifier.
	 */
	public function deleteOld( string $before ): void;
}
