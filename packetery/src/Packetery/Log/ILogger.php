<?php

declare( strict_types=1 );


namespace Packetery\Log;

interface ILogger {

	/**
	 * Registers log driver.
	 */
	public function register(): void;

	/**
	 * Adds log record.
	 */
	public function add( Record $record ): void;

	/**
	 * Get logs.
	 */
	public function getRecords( array $sorting = [] ): array;
}
