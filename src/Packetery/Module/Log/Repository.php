<?php
/**
 * Class Page
 *
 * @package Packetery\Module\Log
 */

declare( strict_types=1 );

namespace Packetery\Module\Log;

use Packetery\Core\CoreHelper;
use Packetery\Core\Log\Record;
use Packetery\Module\ModuleHelper;
use Packetery\Module\WpdbAdapter;

/**
 * Class Repository
 *
 * @package Packetery\Module\Log
 */
class Repository {

	/**
	 * WpdbAdapter.
	 *
	 * @var WpdbAdapter
	 */
	private $wpdbAdapter;

	/**
	 * Constructor.
	 *
	 * @param WpdbAdapter $wpdbAdapter WpdbAdapter.
	 */
	public function __construct( WpdbAdapter $wpdbAdapter ) {
		$this->wpdbAdapter = $wpdbAdapter;
	}

	/**
	 * Counts records.

	 * @param int|null    $orderId Order ID.
	 * @param string|null $action  Action.
	 *
	 * @return int
	 */
	public function countRows( ?int $orderId, ?string $action ): int {
		$whereClause = $this->getWhereClause( [], $orderId, $action );

		return (int) $this->wpdbAdapter->get_var( 'SELECT COUNT(*) FROM `' . $this->wpdbAdapter->packeteryLog . '`' . $whereClause );
	}

	/**
	 * Finds logs.
	 *
	 * @param array<string, string|int|bool|float|null|array<string,mixed>> $arguments Search arguments.
	 *
	 * @return \Generator<Record>|array{}
	 * @throws \Exception From DateTimeImmutable.
	 */
	public function find( array $arguments ) {
		$orderId   = $arguments['order_id'] ?? null;
		$action    = $arguments['action'] ?? null;
		$orderBy   = $arguments['orderby'] ?? [];
		$limit     = $arguments['limit'] ?? null;
		$dateQuery = $arguments['date_query'] ?? [];

		$orderByTransformed = [];
		if ( count( $orderBy ) > 0 ) {
			foreach ( $orderBy as $orderByKey => $orderByValue ) {
				if ( ! in_array( $orderByValue, [ 'ASC', 'DESC' ], true ) ) {
					$orderByValue = 'ASC';
				}

				$orderByTransformed[] = '`' . $orderByKey . '` ' . $orderByValue;
			}
		}

		$orderByClause = '';
		if ( count( $orderByTransformed ) > 0 ) {
			$orderByClause = ' ORDER BY ' . implode( ', ', $orderByTransformed );
		}

		$limitClause = '';
		if ( is_numeric( $limit ) ) {
			$limitClause = ' LIMIT ' . $limit;
		}

		$where = [];
		if ( count( $dateQuery ) > 0 ) {
			foreach ( $dateQuery as $dateQueryItem ) {
				if ( isset( $dateQueryItem['after'] ) ) {
					$where[] = $this->wpdbAdapter->prepare( '`date` > %s', CoreHelper::now()->modify( $dateQueryItem['after'] )->format( CoreHelper::MYSQL_DATETIME_FORMAT ) );
				}
			}
		}

		$whereClause = $this->getWhereClause( $where, $orderId, $action );

		$result = $this->wpdbAdapter->get_results( 'SELECT * FROM `' . $this->wpdbAdapter->packeteryLog . '` ' . $whereClause . $orderByClause . $limitClause );
		if ( is_iterable( $result ) ) {
			return $this->remapToRecord( $result );
		}

		return [];
	}

	/**
	 * Delete old records.
	 *
	 * @param string $before DateTime modifier.
	 *
	 * @return void
	 */
	public function deleteOld( string $before ): void {
		$dateToFormatted = CoreHelper::now()->modify( $before )->format( CoreHelper::MYSQL_DATETIME_FORMAT );
		$this->wpdbAdapter->query(
			$this->wpdbAdapter->prepare( 'DELETE FROM `' . $this->wpdbAdapter->packeteryLog . '` WHERE `date` < %s', $dateToFormatted )
		);
	}

	/**
	 * Remaps logs.
	 *
	 * @param array $logs Logs.
	 *
	 * @return \Generator<Record>
	 */
	public function remapToRecord( array $logs ): \Generator {
		foreach ( $logs as $log ) {
			$record         = new Record();
			$record->id     = $log->id;
			$record->status = $log->status;
			$record->date   = \DateTimeImmutable::createFromFormat( CoreHelper::MYSQL_DATETIME_FORMAT, $log->date, new \DateTimeZone( 'UTC' ) )
												->setTimezone( wp_timezone() );
			$record->action = $log->action;
			$record->title  = $log->title;

			if ( $log->params ) {
				$record->params = json_decode( $log->params, true );
			} else {
				$record->params = [];
			}

			if ( ! is_array( $record->params ) ) {
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
					( count( $params ) > 0 ? 'Data: ' . wp_json_encode( $params, JSON_UNESCAPED_UNICODE ) : '' ),
				]
			)
		);
	}

	/**
	 * Creates log table.
	 *
	 * @return bool
	 */
	public function createOrAlterTable(): bool {
		$createTableQuery = 'CREATE TABLE ' . $this->wpdbAdapter->packeteryLog . " (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`order_id` bigint(20) unsigned NULL,
			`title` varchar(255) NOT NULL DEFAULT '',
			`params` text NOT NULL,
			`status` varchar(255) NOT NULL DEFAULT '',
			`action` varchar(255) NOT NULL DEFAULT '',
			`date` datetime NOT NULL,
			PRIMARY KEY  (`id`)
		) " . $this->wpdbAdapter->get_charset_collate();

		return $this->wpdbAdapter->dbDelta( $createTableQuery, $this->wpdbAdapter->packeteryLog );
	}

	/**
	 * Drops log table.
	 *
	 * @return void
	 */
	public function drop(): void {
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packeteryLog . '`' );
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
		if ( $date === null ) {
			$date = CoreHelper::now();
		}

		$dateString = $date->setTimezone( new \DateTimeZone( 'UTC' ) )->format( CoreHelper::MYSQL_DATETIME_FORMAT );

		$paramsString = '';
		if ( $record->params !== null && count( $record->params ) > 0 ) {
			$params       = ModuleHelper::convertArrayFloatsToStrings( $record->params );
			$paramsString = wp_json_encode( $params );
		}

		$orderId = $record->orderId;
		if ( is_numeric( $orderId ) ) {
			$orderId = (int) $orderId;
		}

		$data = [
			'id'       => $record->id,
			'order_id' => $orderId,
			'title'    => $record->title,
			'status'   => $record->status,
			'action'   => $record->action,
			'params'   => $paramsString,
			'date'     => $dateString,
		];

		$this->wpdbAdapter->insertReplaceHelper( $this->wpdbAdapter->packeteryLog, $data, null, 'REPLACE' );
	}

	/**
	 * Gets where clause for find and count queries.
	 *
	 * @param array       $where   Conditions.
	 * @param int|null    $orderId Order id.
	 * @param string|null $action  Action.
	 *
	 * @return string
	 */
	private function getWhereClause( array $where, ?int $orderId, ?string $action ): string {
		if ( is_numeric( $orderId ) ) {
			$where[] = $this->wpdbAdapter->prepare( '`order_id` = %d', $orderId );
		}
		if ( $action !== null ) {
			$where[] = $this->wpdbAdapter->prepare( '`action` = %s', $action );
		}

		$whereClause = '';
		if ( count( $where ) > 0 ) {
			$whereClause = ' WHERE ' . implode( ' AND ', $where );
		}

		return $whereClause;
	}
}
