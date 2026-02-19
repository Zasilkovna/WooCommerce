<?php

declare( strict_types=1 );

namespace Packetery\Module\Log;

use Packetery\Core\CoreHelper;
use Packetery\Core\Log\LogPageArguments;
use Packetery\Core\Log\Record;
use Packetery\Module\ModuleHelper;
use Packetery\Module\WpdbAdapter;

class Repository {

	private WpdbAdapter $wpdbAdapter;

	public function __construct( WpdbAdapter $wpdbAdapter ) {
		$this->wpdbAdapter = $wpdbAdapter;
	}

	/**
	 * @param int|null    $orderId
	 * @param string|null $action
	 * @param string[]    $where
	 *
	 * @return int
	 */
	public function countRows( ?int $orderId, ?string $action, array $where = [] ): int {
		$whereClause = $this->getWhereClause( $where, $orderId, $action );

		return (int) $this->wpdbAdapter->get_var( 'SELECT COUNT(*) FROM `' . $this->wpdbAdapter->packeteryLog . '`' . $whereClause );
	}

	/**
	 * @param LogPageArguments $arguments
	 *
	 * @return \Generator<Record>|array{}
	 * @throws \Exception From DateTimeImmutable.
	 */
	public function find( LogPageArguments $arguments ) {
		$orderByTransformed = [];
		if ( $arguments->getOrderBy() !== null ) {
			$allowedColumns = [ 'date' ];
			foreach ( $arguments->getOrderBy() as $orderByKey => $orderByValue ) {
				if ( ! in_array( $orderByKey, $allowedColumns, true ) ) {
					continue;
				}
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
		if ( $arguments->getLimit() !== null ) {
			$limitClause = ' LIMIT ' . $arguments->getLimit();
			if ( $arguments->getOffset() !== null ) {
				$limitClause .= ' OFFSET ' . $arguments->getOffset();
			}
		}

		$where = $this->buildQueryConditions( $arguments );

		$whereClause = $this->getWhereClause( $where, $arguments->getOrderId(), $arguments->getAction() );

		$result = $this->wpdbAdapter->get_results( 'SELECT * FROM `' . $this->wpdbAdapter->packeteryLog . '` ' . $whereClause . $orderByClause . $limitClause );
		if ( is_iterable( $result ) ) {
			return $this->remapToRecord( $result );
		}

		return [];
	}

	/**
	 * @param string $before DateTime modifier.
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
	 * Save.
	 *
	 * @param Record $record Record.
	 *
	 * @return int|false The number of rows updated, or false on error.
	 * @throws \Exception From DateTimeImmutable.
	 */
	public function save( Record $record ) {
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

		return $this->wpdbAdapter->insertReplaceHelper( $this->wpdbAdapter->packeteryLog, $data, null, 'REPLACE' );
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

	/**
	 * @return string[]
	 */
	public function buildQueryConditions( LogPageArguments $arguments ): array {
		$where = [];
		if ( $arguments->getDateQuery() !== null ) {
			foreach ( $arguments->getDateQuery() as $dateQueryItem ) {
				if ( isset( $dateQueryItem['after'] ) ) {
					$after = CoreHelper::now()->modify( $dateQueryItem['after'] );
					if ( $arguments->getUseExactTimes() === false ) {
						$after = $after->setTime( 0, 0 );
					}
					$where[] = $this->wpdbAdapter->prepare( '`date` >= %s', $after->format( CoreHelper::MYSQL_DATETIME_FORMAT ) );
				}
				if ( isset( $dateQueryItem['before'] ) ) {
					$before = CoreHelper::now()->modify( $dateQueryItem['before'] );
					if ( $arguments->getUseExactTimes() === false ) {
						$before = $before->setTime( 23, 59, 59 );
					}
					$where[] = $this->wpdbAdapter->prepare( '`date` <= %s', $before->format( CoreHelper::MYSQL_DATETIME_FORMAT ) );
				}
			}
		}

		if ( $arguments->getStatus() !== null ) {
			$where[] = $this->wpdbAdapter->prepare( '`status` = %s', $arguments->getStatus() );
		}

		if ( $arguments->getSearch() !== null && $arguments->getSearch() !== '' ) {
			$searchWildcard = '%' . $this->wpdbAdapter->escLike( $arguments->getSearch() ) . '%';
			$where[]        = $this->wpdbAdapter->prepare( '(`title` LIKE %s OR `params` LIKE %s)', $searchWildcard, $searchWildcard );
		}

		return $where;
	}
}
