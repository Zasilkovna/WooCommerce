<?php

declare( strict_types=1 );


namespace Packetery\Module\Log;

use Packetery\Core\Helper;
use Packetery\Core\Log\ILogger;
use Packetery\Core\Log\Record;

class Repository {

	/**
	 * WPDB.
	 *
	 * @var \wpdb;
	 */
	private $wpdb;

	/**
	 * @param \wpdb $wpdb
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Finds logs.
	 *
	 * @param array $arguments Search arguments.
	 *
	 * @return iterable|Record[]
	 */
	public function find( array $arguments ): iterable {
		$orderBy        = $arguments['orderby'] ?? [];
		$limit          = $arguments['limit'] ?? null;
		$dateQuery      = $arguments['date_query'] ?? [];

		$orderByTransformed = [];
		foreach ( $orderBy as $orderByKey => $orderByValue ) {
			$orderByTransformed[] = $orderByKey . ' ' . $orderByValue;
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
				$where[] = $this->wpdb->prepare('`date` > %s', Helper::now()->modify( $dateQueryItem['after'] )->format( Helper::MYSQL_DATETIME_FORMAT ) );
			}
		}

		$whereClause = '';
		if ( $where ) {
			$whereClause = ' WHERE ' . implode( ' AND ', $where );
		}

		$result = $this->wpdb->get_results( "SELECT * FROM " . $this->wpdb->packetery_log . $whereClause . $orderByClause . $limitClause );
		if ( is_iterable( $result ) ) {
			return $this->remapToRecord( $result );
		}

		return [];
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
				$params         = str_replace( '&quot;', '\\', $log->params );
				$record->params = json_decode( $params, true, 512, ILogger::JSON_FLAGS );
			} else {
				$record->params = [];
			}

			yield $record;
		}
	}

	/**
	 * Creates log table.
	 *
	 * @return void
	 */
	public function createTable(): void {
		$this->wpdb->query( "
			CREATE TABLE IF NOT EXISTS `" . $this->wpdb->packetery_log . "` (
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
		" );
	}

	/**
	 * Drops log table.
	 *
	 * @return void
	 */
	public function drop(): void {
		$this->wpdb->query( 'DROP TABLE IF EXISTS `' . $this->wpdb->packetery_log . '`' );
	}

	/**
	 * Save.
	 *
	 * @param Record $record Record.
	 *
	 * @return void
	 */
	public function save( Record $record ): void {
		$date = null;
		if ( $record->date ) {
			$date = $record->date->setTimezone(  new \DateTimeZone( 'UTC' ) )->format( Helper::MYSQL_DATETIME_FORMAT );
		}

		$params = '';
		if ( $record->params ) {
			$params = wp_json_encode( $record->params, ILogger::JSON_FLAGS );
			$params = str_replace( '\\', '&quot;', $params );
		}

		$data = [
			'id'     => $record->id,
			'title'  => ( $record->title ?? '' ),
			'status' => ( $record->status ?? '' ),
			'action' => ( $record->action ?? '' ),
			'params' => $params,
			'date'   => $date,
		];

		$this->wpdb->_insert_replace_helper( $this->wpdb->packetery_log, $data, null, 'REPLACE' );
	}
}
