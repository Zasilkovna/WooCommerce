<?php
/**
 * Class WpdbAdapter
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module;

use PacketeryTracy\Debugger;

/**
 * Class WpdbAdapter
 *
 * @package Packetery
 */
class WpdbAdapter {

	/**
	 * Packetery carrier table name.
	 *
	 * @var string
	 */
	public $packetery_carrier;

	/**
	 * Packetery order table name.
	 *
	 * @var string
	 */
	public $packetery_order;

	/**
	 * Packetery log table name.
	 *
	 * @var string
	 */
	public $packetery_log;

	/**
	 * WordPress posts table name.
	 *
	 * @var string
	 */
	public $posts;

	/**
	 * WordPress options table name.
	 *
	 * @var string
	 */
	public $options;

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
		if ( null === $result && '' !== $this->getLastWpdbError() && $this->isPacketeryTableQueried( (string) $this->wpdb->last_query ) ) {
			$this->handleError( $this->getLastWpdbError() );
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
			$this->handleError( 'Query to prepare is invalid. Likely due placeholder count mismatch.' );
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
		if ( false === $result && '' !== $this->getLastWpdbError() && $this->isPacketeryTableQueried( (string) $this->wpdb->last_query ) ) {
			$this->handleError( $this->getLastWpdbError() );
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
		if ( false === $result && '' !== $this->getLastWpdbError() && $this->isPacketeryTableQueried( (string) $this->wpdb->last_query ) ) {
			$this->handleError( $this->getLastWpdbError() );
		}

		return $result;
	}

	/**
	 * Real escape.
	 *
	 * @param string $input Input.
	 *
	 * @return string
	 */
	public function realEscape( string $input ): string {
		return $this->wpdb->_real_escape( $input );
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
		if ( false === $result && '' !== $this->getLastWpdbError() && $this->isPacketeryTableQueried( (string) $this->wpdb->last_query ) ) {
			$this->handleError( $this->getLastWpdbError() );
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
		if ( false === $result && '' !== $this->getLastWpdbError() && $this->isPacketeryTableQueried( (string) $this->wpdb->last_query ) ) {
			$this->handleError( $this->getLastWpdbError() );
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
		if ( false === $result && '' !== $this->getLastWpdbError() && $this->isPacketeryTableQueried( (string) $this->wpdb->last_query ) ) {
			$this->handleError( $this->getLastWpdbError() );
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
	 * @return array|object|null Database query results.
	 */
	public function get_results( string $query, string $output = OBJECT ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $this->wpdb->get_results( $query, $output );
		if ( '' !== $this->getLastWpdbError() && $this->isPacketeryTableQueried( (string) $this->wpdb->last_query ) ) {
			$this->handleError( $this->getLastWpdbError() );
		}

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
		if ( null === $result && '' !== $this->getLastWpdbError() && $this->isPacketeryTableQueried( (string) $this->wpdb->last_query ) ) {
			$this->handleError( $this->getLastWpdbError() );
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
	 * Handles wpdb error.
	 *
	 * @param string $errorMessage Error message.
	 *
	 * @return void
	 */
	private function handleError( string $errorMessage ): void {
		Debugger::log( $errorMessage, sprintf( 'wpdb-errors_%s', gmdate( 'Y-m-d' ) ) );
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
}
