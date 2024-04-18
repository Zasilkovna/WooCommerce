<?php
/**
 * Class WpdbAdapter
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module;

use Packetery\Tracy\Debugger;
use WC_Logger;

/**
 * Class WpdbAdapter
 *
 * @package Packetery
 */
class WpdbAdapter {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	public $packetery_carrier;

	/**
	 * Table name.
	 *
	 * @var string
	 */
	public $packetery_order;

	/**
	 * Table name.
	 *
	 * @var string
	 */
	public $packetery_log;

	/**
	 * Table name.
	 *
	 * @var string
	 */
	public $packetery_customs_declaration;

	/**
	 * Table name.
	 *
	 * @var string
	 */
	public $packetery_customs_declaration_item;

	/**
	 * Table name.
	 *
	 * @var string
	 */
	public $wc_orders;

	/**
	 * Table name.
	 *
	 * @var string
	 */
	public $posts;

	/**
	 * Table name.
	 *
	 * @var string
	 */
	public $options;

	/**
	 * Table name.
	 *
	 * @var string
	 */
	public $postmeta;

	/**
	 * Wpdb.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 *
	 * @param \wpdb $wpdb Wpdb.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Gets row.
	 *
	 * @param string $query  SQL query.
	 * @param string $output Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which
	 *                       correspond to an stdClass object, an associative array, or a numeric array,
	 *                       respectively. Default OBJECT.
	 *
	 * @return array|object|null Database query result or null on failure.
	 */
	public function get_row( string $query, string $output = OBJECT ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $this->wpdb->get_row( $query, $output );
		if ( null === $result ) {
			$this->handleError();
		}

		return $result;
	}

	/**
	 * Prepares a SQL query for safe execution.
	 *
	 * @param string $query Query.
	 * @param mixed  ...$args Arguments.
	 *
	 * @return string
	 */
	public function prepare( string $query, ...$args ): string {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $this->wpdb->prepare( $query, ...$args );
		if ( null === $result ) {
			$this->logError( 'Query to prepare is invalid. Likely due placeholder count mismatch.' );
		}

		return (string) $result;
	}

	/**
	 * Executes SQL query.
	 *
	 * @param string $query Query.
	 *
	 * @return int|bool Boolean true for CREATE, ALTER, TRUNCATE and DROP queries. Number of rows
	 *                  affected/selected for all other queries. Boolean false on error.
	 */
	public function query( string $query ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $this->wpdb->query( $query );
		if ( false === $result ) {
			$this->handleError();
		}

		return $result;
	}

	/**
	 * Helper function for insert and replace.
	 *
	 * @param string     $table  Table name.
	 * @param array      $data   Data to insert (in column => value pairs).
	 * @param array|null $format Optional. An array of formats to be mapped to each of the value in $data.
	 * @param string     $type   Optional. Type of operation. Possible values include 'INSERT' or 'REPLACE'.
	 *
	 * @return int|false The number of rows affected, or false on error.
	 */
	public function insertReplaceHelper( string $table, array $data, ?array $format = null, string $type = 'INSERT' ) {
		$result = $this->wpdb->_insert_replace_helper( $table, $data, $format, $type );
		if ( false === $result ) {
			$this->handleError();
		}

		return $result;
	}

	/**
	 * Deletes a row in the table.
	 *
	 * @param string      $table       Table name.
	 * @param array       $where       A named array of WHERE clauses (in column => value pairs).
	 * @param string|null $whereFormat Optional. An array of formats to be mapped to each of the values in $where.
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function delete( string $table, array $where, ?string $whereFormat = null ) {
		$result = $this->wpdb->delete( $table, $where, $whereFormat );
		if ( false === $result ) {
			$this->handleError();
		}

		return $result;
	}

	/**
	 * Inserts a row into the table.
	 *
	 * @param string $table Table name.
	 * @param array  $data  Data to insert (in column => value pairs).
	 *
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public function insert( string $table, array $data ) {
		$result = $this->wpdb->insert( $table, $data );
		if ( false === $result ) {
			$this->handleError();
		}

		return $result;
	}

	/**
	 * Updates a row in the table.
	 *
	 * @param string $table Table name.
	 * @param array  $data  Data to update (in column => value pairs).
	 * @param array  $where A named array of WHERE clauses (in column => value pairs).
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function update( string $table, array $data, array $where ) {
		$result = $this->wpdb->update( $table, $data, $where );
		if ( false === $result ) {
			$this->handleError();
		}

		return $result;
	}

	/**
	 * Gets charset collate.
	 *
	 * @return string
	 */
	public function get_charset_collate(): string {
		return $this->wpdb->get_charset_collate();
	}

	/**
	 * Retrieves an entire SQL result set from the database (i.e., many rows).
	 *
	 * @param string $query  SQL query.
	 * @param string $output Optional. Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants.
	 *
	 * @return array|object[]|null Database query results.
	 */
	public function get_results( string $query, string $output = OBJECT ): ?array {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $this->wpdb->get_results( $query, $output );
		$this->handleError();

		return $result;
	}

	/**
	 * Retrieves one variable from the database.
	 *
	 * @param string $query SQL query. Defaults to null, use the result from the previous query.
	 *
	 * @return string|null Database query result (as string), or null on failure.
	 */
	public function get_var( string $query ): ?string {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $this->wpdb->get_var( $query );
		if ( null === $result ) {
			$this->handleError();
		}

		return $result;
	}

	/**
	 * Tells if packetery table is queried.
	 *
	 * @param string $query Query.
	 *
	 * @return bool
	 */
	private function isPacketeryTableQueried( string $query ): bool {
		return 1 === preg_match( '~\s*(FROM|JOIN|INTO|UPDATE|TABLE)\s*`?' . preg_quote( $this->getPacketeryPrefix(), '~' ) . '~i', $query );
	}

	/**
	 * Gets packetery prefix.
	 *
	 * @return string
	 */
	public function getPacketeryPrefix(): string {
		return sprintf( '%spacketery_', $this->wpdb->prefix );
	}

	/**
	 * Logs wpdb error.
	 *
	 * @param string $errorMessage Error message.
	 *
	 * @return void
	 */
	private function logError( string $errorMessage ): void {
		Debugger::log( $errorMessage, sprintf( 'wpdb-errors_%s', gmdate( 'Y-m-d' ) ) );
	}

	/**
	 * Handles wpdb error.
	 *
	 * @return void
	 */
	private function handleError(): void {
		if ( '' !== $this->getLastWpdbError() && $this->isPacketeryTableQueried( (string) $this->wpdb->last_query ) ) {
			$this->logError( $this->getLastWpdbError() );
		}
	}

	/**
	 * Gets last wpdb error.
	 *
	 * @return string
	 */
	public function getLastWpdbError(): string {
		return $this->wpdb->last_error;
	}

	/**
	 * Gets wpdb queries.
	 *
	 * @return \Generator
	 */
	public function getWpdbQueries(): \Generator {
		if ( ! empty( $this->wpdb->queries ) ) {
			foreach ( $this->wpdb->queries as $queryInfo ) {
				yield $queryInfo;
			}
		}
	}

	/**
	 * This method outputs a one dimensional array. If more than one column is returned by the query,
	 * only the specified column will be returned, but the entire result is cached for later use.
	 *
	 * @param string $query The query you wish to execute. Setting this parameter to null will return the specified column from the cached results of the previous query.
	 * @param int    $column_offset The desired column (0 being the first). Defaults to 0.
	 *
	 * @return array Returns an empty array if no result is found.
	 */
	public function get_col( string $query, int $column_offset = 0 ): array {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $this->wpdb->get_col( $query, $column_offset );
		if ( [] === $result ) {
			$this->handleError();
		}

		return $result;
	}

	/**
	 * Quote array of strings.
	 *
	 * @param array $input Input.
	 *
	 * @return array
	 */
	private function quoteArrayOfStrings( array $input ): array {
		return array_map(
			function ( string $item ) {
				return $this->prepare( '%s', $item );
			},
			$input
		);
	}

	/**
	 * Prepare IN clause from array of strings.
	 *
	 * @param array $input Input array.
	 *
	 * @return string
	 */
	public function prepareInClause( array $input ): string {
		return implode( ',', $this->quoteArrayOfStrings( $input ) );
	}

	/**
	 * Wrapper for dbDelta function, logs result.
	 *
	 * @param string $createTableQuery Create table query.
	 * @param string $tableName Table name.
	 *
	 * @return bool
	 */
	public function dbDelta( string $createTableQuery, string $tableName ): bool {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$result1 = dbDelta( $createTableQuery );
		$result2 = dbDelta( $createTableQuery );

		/**
		 * WC logger.
		 *
		 * @var WC_Logger $wcLogger
		 */
		$wcLogger = wc_get_logger();
		foreach ( $result1 as $tableOrColumn => $message ) {
			$wcLogger->info( sprintf( 'dbDelta: %s => %s', $tableOrColumn, $message ), [ 'source' => 'packeta' ] );
		}

		// If the first command tries to create the table and so does the second, it means it failed.
		// Otherwise, we assume everything is fine.
		$parsedResult1 = $this->parseDbdeltaOutput( $result1 );
		$parsedResult2 = $this->parseDbdeltaOutput( $result2 );
		if (
			in_array( $tableName, $parsedResult1['created_tables'], true ) &&
			in_array( $tableName, $parsedResult2['created_tables'], true )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Parses the output given by dbDelta and returns information about it. Taken from DatabaseUtil 7.5.1.
	 *
	 * @param array $dbdeltaOutput The output from the execution of dbDelta.
	 *
	 * @return array[] An array containing a 'created_tables' key whose value is an array with the names of the tables that have been (or would have been) created.
	 */
	private function parseDbdeltaOutput( array $dbdeltaOutput ): array {
		$createdTables = [];

		foreach ( $dbdeltaOutput as $tableName => $result ) {
			if ( "Created table $tableName" === $result ) {
				$createdTables[] = $tableName;
			}
		}

		return [ 'created_tables' => $createdTables ];
	}

	/**
	 * Gets last insert ID.
	 *
	 * @return string|null
	 */
	public function getLastInsertId(): ?string {
		if ( 0 === $this->wpdb->insert_id ) {
			return null;
		}

		return (string) $this->wpdb->insert_id;
	}

	/**
	 * Wpdb esc_like method proxy.
	 *
	 * @param string $text Text to escape.
	 *
	 * @return string
	 */
	public function escLike( string $text ): string {
		return $this->wpdb->esc_like( $text );
	}

}
