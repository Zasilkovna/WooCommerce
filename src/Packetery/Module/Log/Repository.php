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
use Packetery\Module\WpdbAdapter;

/**
 * Class Repository
 *
 * @package Packetery\Module\Log
 */
class Repository {

	private const TABLE_LOG = 'packetery_log';

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
	 *
	 * @param int $orderId Order ID.
	 *
	 * @return int
	 */
	public function countByOrderId( int $orderId ): int {
		return (int) $this->wpdbAdapter->get_var(
			$this->wpdbAdapter->prepare( 'SELECT COUNT(*) FROM `' . $this->wpdbAdapter->wpdb->prefix . self::TABLE_LOG . '` WHERE `order_id` = %d', $orderId )
		);
	}

	/**
	 * Counts records.
	 *
	 * @return int
	 */
	public function countAll(): int {
		return (int) $this->wpdbAdapter->get_var( 'SELECT COUNT(*) FROM `' . $this->wpdbAdapter->wpdb->prefix . self::TABLE_LOG . '`' );
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
				$where[] = $this->wpdbAdapter->prepare( '`date` > %s', Helper::now()->modify( $dateQueryItem['after'] )->format( Helper::MYSQL_DATETIME_FORMAT ) );
			}
		}

		if ( is_numeric( $orderId ) ) {
			$where[] = $this->wpdbAdapter->prepare( '`order_id` = %d', $orderId );
		}

		$whereClause = '';
		if ( $where ) {
			$whereClause = ' WHERE ' . implode( ' AND ', $where );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $this->wpdbAdapter->get_results( 'SELECT * FROM `' . $this->wpdbAdapter->wpdb->prefix . self::TABLE_LOG . '` ' . $whereClause . $orderByClause . $limitClause );
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
		$dateToFormatted = Helper::now()->modify( $before )->format( Helper::MYSQL_DATETIME_FORMAT );
		$this->wpdbAdapter->query(
			$this->wpdbAdapter->prepare( 'DELETE FROM `' . $this->wpdbAdapter->wpdb->prefix . self::TABLE_LOG . '` WHERE `date` < %s', $dateToFormatted )
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
		$this->wpdbAdapter->query(
			'
			CREATE TABLE IF NOT EXISTS `' . $this->wpdbAdapter->wpdb->prefix . self::TABLE_LOG . "` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`order_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
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
	 * Adds order id column.
	 *
	 * @return void
	 */
	public function addOrderIdColumn(): void {
		$this->wpdbAdapter->query( 'ALTER TABLE `' . $this->wpdbAdapter->wpdb->prefix . self::TABLE_LOG . '` ADD COLUMN `order_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `id`' );
	}

	/**
	 * Drops log table.
	 *
	 * @return void
	 */
	public function drop(): void {
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->wpdb->prefix . self::TABLE_LOG . '`' );
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

		$this->wpdbAdapter->insertReplaceHelper( $this->wpdbAdapter->wpdb->prefix . self::TABLE_LOG, $data, null, 'REPLACE' );
	}
}
