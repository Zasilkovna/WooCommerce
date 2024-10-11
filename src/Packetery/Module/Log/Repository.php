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
		return (int) $this->wpdbAdapter->get_var( 'SELECT COUNT(*) FROM `' . $this->wpdbAdapter->packetery_log . '`' . $whereClause );
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
		$orderId   = $arguments['order_id'] ?? null;
		$action    = $arguments['action'] ?? null;
		$orderBy   = $arguments['orderby'] ?? [];
		$limit     = $arguments['limit'] ?? null;
		$dateQuery = $arguments['date_query'] ?? [];

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
				$where[] = $this->wpdbAdapter->prepare( '`date` > %s', CoreHelper::now()->modify( $dateQueryItem['after'] )->format( CoreHelper::MYSQL_DATETIME_FORMAT ) );
			}
		}

		$whereClause = $this->getWhereClause( $where, $orderId, $action );

		$result = $this->wpdbAdapter->get_results( 'SELECT * FROM `' . $this->wpdbAdapter->packetery_log . '` ' . $whereClause . $orderByClause . $limitClause );
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
			$this->wpdbAdapter->prepare( 'DELETE FROM `' . $this->wpdbAdapter->packetery_log . '` WHERE `date` < %s', $dateToFormatted )
		);
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
			$record->date   = \DateTimeImmutable::createFromFormat( CoreHelper::MYSQL_DATETIME_FORMAT, $log->date, new \DateTimeZone( 'UTC' ) )
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
	 * @return bool
	 */
	public function createOrAlterTable(): bool {
		$createTableQuery = 'CREATE TABLE ' . $this->wpdbAdapter->packetery_log . " (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`order_id` bigint(20) unsigned NULL,
			`title` varchar(255) NOT NULL DEFAULT '',
			`params` text NOT NULL,
			`status` varchar(255) NOT NULL DEFAULT '',
			`action` varchar(255) NOT NULL DEFAULT '',
			`date` datetime NOT NULL,
			PRIMARY KEY  (`id`)
		) " . $this->wpdbAdapter->get_charset_collate();

		return $this->wpdbAdapter->dbDelta( $createTableQuery, $this->wpdbAdapter->packetery_log );
	}

	/**
	 * Drops log table.
	 *
	 * @return void
	 */
	public function drop(): void {
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packetery_log . '`' );
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
			$date = CoreHelper::now();
		}

		$dateString = $date->setTimezone( new \DateTimeZone( 'UTC' ) )->format( CoreHelper::MYSQL_DATETIME_FORMAT );

		$paramsString = '';
		if ( $record->params ) {
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
			'title'    => ( $record->title ?? '' ),
			'status'   => ( $record->status ?? '' ),
			'action'   => ( $record->action ?? '' ),
			'params'   => $paramsString,
			'date'     => $dateString,
		];

		$this->wpdbAdapter->insertReplaceHelper( $this->wpdbAdapter->packetery_log, $data, null, 'REPLACE' );
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
		if ( null !== $action ) {
			$where[] = $this->wpdbAdapter->prepare( '`action` = %s', $action );
		}

		$whereClause = '';
		if ( $where ) {
			$whereClause = ' WHERE ' . implode( ' AND ', $where );
		}

		return $whereClause;
	}

}
