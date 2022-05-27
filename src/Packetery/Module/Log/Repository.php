<?php
/**
 * Class Page
 *
 * @package Packetery\Module\Log
 */

declare( strict_types=1 );


namespace Packetery\Module\Log;

use Packetery\Core\Helper;
use Packetery\Core\Log\Record;

/**
 * Class Repository
 *
 * @package Packetery\Module\Log
 */
class Repository {

	/**
	 * WPDB.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 *
	 * @param \wpdb $wpdb WPDB.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Creates SQL query string.
	 *
	 * @param array $arguments Arguments.
	 *
	 * @return string
	 */
	private function buildFindQuery( array $arguments ): string {
		$wpdb      = $this->wpdb;
		$select    = $arguments['select'] ?? [];
		$orderBy   = $arguments['orderby'] ?? [];
		$limit     = $arguments['limit'] ?? null;
		$dateQuery = $arguments['date_query'] ?? [];

		$selectSqlItems = [];
		foreach ( $select as $column ) {
			$selectSqlItems[] = '`' . $column . '`';
		}

		if ( $selectSqlItems ) {
			$selectSql = implode( ',', $selectSqlItems );
		} else {
			$selectSql = '*';
		}

		$orderByTransformed = [];
		foreach ( $orderBy as $orderByKey => $orderByValue ) {
			if ( ! in_array( $orderByValue, [ 'ASC', 'DESC' ], true ) ) {
				$orderByValue = 'ASC';
			}

			$orderByTransformed[] = '`' . $orderByKey . '` ' . $orderByValue;
		}

		$orderByClause = '';
		if ( $orderByTransformed ) {
			$orderByClause = ' ORDER BY ' . implode( ', ', $orderByTransformed );
		}

		$limitClause = '';
		if ( is_numeric( $limit ) ) {
			$limitClause = ' LIMIT ' . $limit;
		}

		$where = [];
		foreach ( $dateQuery as $dateQueryItem ) {
			if ( isset( $dateQueryItem['after'] ) ) {
				$where[] = $wpdb->prepare( '`date` > %s', Helper::now()->modify( $dateQueryItem['after'] )->format( Helper::MYSQL_DATETIME_FORMAT ) );
			}
			if ( isset( $dateQueryItem['before'] ) ) {
				$where[] = $wpdb->prepare( '`date` < %s', Helper::now()->modify( $dateQueryItem['before'] )->format( Helper::MYSQL_DATETIME_FORMAT ) );
			}
		}

		$whereClause = '';
		if ( $where ) {
			$whereClause = ' WHERE ' . implode( ' AND ', $where );
		}

		return 'SELECT ' . $selectSql . ' FROM `' . $wpdb->packetery_log . '` ' . $whereClause . $orderByClause . $limitClause;
	}

	/**
	 * Finds logs.
	 *
	 * @param array $arguments Search arguments.
	 *
	 * @return iterable|Record[]
	 * @throws \Exception From DateTimeImmutable.
	 */
	public function find( array $arguments ): iterable {
		$wpdb = $this->wpdb;
		$sql  = $this->buildFindQuery( $arguments );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->get_results( $sql );
		if ( is_iterable( $result ) ) {
			return $this->remapToRecord( $result );
		}

		return [];
	}

	/**
	 * Delete old records.
	 *
	 * @param array $arguments Arguments.
	 *
	 * @return void
	 */
	public function deleteMany( array $arguments ): void {
		$wpdb = $this->wpdb;
		$sql  = $this->buildFindQuery( [ 'select' => [ 'id' ] ] + $arguments );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( 'DELETE pl FROM `' . $wpdb->packetery_log . '` pl JOIN (' . $sql . ') s ON s.`id` = pl.`id`' );
	}

	/**
	 * Remaps logs.
	 *
	 * @param iterable $logs Logs.
	 *
	 * @return \Generator|Record[]
	 */
	public function remapToRecord( iterable $logs ): \Generator {
		foreach ( $logs as $log ) {
			$record         = new Record();
			$record->id     = $log->id;
			$record->status = $log->status;
			$record->date   = \DateTimeImmutable::createFromFormat( Helper::MYSQL_DATETIME_FORMAT, $log->date, new \DateTimeZone( 'UTC' ) )
												->setTimezone( wp_timezone() );
			$record->action = $log->action;
			$record->title  = $log->title;

			if ( $log->params ) {
				$record->params = json_decode( $log->params, true );
			} else {
				$record->params = [];
			}

			$record->note = $this->getNote( $record->title, $record->params );

			yield $record;
		}
	}

	/**
	 * Gets note.
	 *
	 * @param string $title Title.
	 * @param array  $params Params.
	 *
	 * @return string
	 */
	private function getNote( string $title, array $params ): string {
		return implode(
			' ',
			array_filter(
				[
					$title,
					( $params ? 'Data: ' . wp_json_encode( $params, JSON_UNESCAPED_UNICODE ) : '' ),
				]
			)
		);
	}

	/**
	 * Creates log table.
	 *
	 * @return void
	 */
	public function createTable(): void {
		$wpdb = $this->wpdb;
		$wpdb->query(
			'
			CREATE TABLE IF NOT EXISTS `' . $wpdb->packetery_log . "` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`title` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_general_ci',
				`params` TEXT NOT NULL DEFAULT '' COLLATE 'utf8_general_ci',
				`status` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_general_ci',
				`action` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_general_ci',
				`date` DATETIME NOT NULL,
				PRIMARY KEY (`id`) USING BTREE
			)
			COLLATE='utf8_general_ci'
			ENGINE=InnoDB
		"
		);
	}

	/**
	 * Drops log table.
	 *
	 * @return void
	 */
	public function drop(): void {
		$wpdb = $this->wpdb;
		$wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->packetery_log . '`' );
	}

	/**
	 * Save.
	 *
	 * @param Record $record Record.
	 *
	 * @return void
	 * @throws \Exception From DateTimeImmutable.
	 */
	public function save( Record $record ): void {
		$date = $record->date;
		if ( null === $date ) {
			$date = Helper::now();
		}

		$dateString = $date->setTimezone( new \DateTimeZone( 'UTC' ) )->format( Helper::MYSQL_DATETIME_FORMAT );

		$paramsString = '';
		if ( $record->params ) {
			$paramsString = wp_json_encode( $record->params );
		}

		$data = [
			'id'     => $record->id,
			'title'  => ( $record->title ?? '' ),
			'status' => ( $record->status ?? '' ),
			'action' => ( $record->action ?? '' ),
			'params' => $paramsString,
			'date'   => $dateString,
		];

		$this->wpdb->_insert_replace_helper( $this->wpdb->packetery_log, $data, null, 'REPLACE' );
	}
}
