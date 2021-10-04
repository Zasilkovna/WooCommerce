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
	 * @param array $sorting Sorting.
	 *
	 * @return array
	 */
	public function getRecords( array $sorting = [] ): array;
}
