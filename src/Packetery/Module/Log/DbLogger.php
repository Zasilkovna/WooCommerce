<?php
/**
 * Class DbLogger
 *
 * @package Packetery\Module\Log
 */

declare( strict_types=1 );


namespace Packetery\Module\Log;

use Packetery\Core\Helper;
use Packetery\Core\Log\Record;

/**
 * Class DbLogger
 *
 * @package Packetery\Module\Log
 */
class DbLogger implements \Packetery\Core\Log\ILogger {

	/**
	 * Log repository.
	 *
	 * @var Repository
	 */
	private $logRepository;

	/**
	 * Constructor.
	 *
	 * @param Repository $logRepository Log repository.
	 */
	public function __construct( Repository $logRepository ) {
		$this->logRepository = $logRepository;
	}

	/**
	 * Registers logger.
	 *
	 * @return void
	 */
	public function register(): void {
	}

	/**
	 * Adds record.
	 *
	 * @param Record $record Record.
	 *
	 * @return void
	 * @throws \Exception From DateTimeImmutable.
	 */
	public function add( Record $record ): void {
		if ( null === $record->date ) {
			$record->date = Helper::now();
		}

		$this->logRepository->save( $record );
	}

	/**
	 * Gets records.
	 *
	 * @param mixed $orderId Order ID.
	 * @param array $sorting Sorting config.
	 * @param int   $limit   Limit.
	 *
	 * @return iterable|Record[]
	 * @throws \Exception From DateTimeImmutable.
	 */
	public function getRecords( $orderId, array $sorting = [], int $limit ): iterable {
		$arguments = [
			'orderby' => $sorting,
			'limit'   => $limit,
		];

		if ( is_numeric( $orderId ) ) {
			$arguments['order_id'] = $orderId;
		}

		$logs = $this->logRepository->find( $arguments );
		if ( ! $logs ) {
			return [];
		}

		return $logs;
	}

	/**
	 * Counts records.
	 *
	 * @param int|null $orderId Order ID.
	 *
	 * @return int
	 */
	public function countRecords( $orderId ): int {
		if ( is_numeric( $orderId ) ) {
			return $this->logRepository->countByOrderId( $orderId );
		}

		if ( null === $orderId ) {
			return $this->logRepository->countAll();
		}
	}

	/**
	 * Gets logs for given period as array.
	 *
	 * @param array $dateQuery Date_query compatible array.
	 *
	 * @return array
	 * @throws \Exception From DateTimeImmutable.
	 */
	public function getForPeriodAsArray( array $dateQuery ): iterable {
		$arguments = [
			'orderby'    => [ 'date' => 'ASC' ],
			'date_query' => $dateQuery,
		];

		$logs = $this->logRepository->find( $arguments );
		if ( ! $logs ) {
			return [];
		}

		return $logs;
	}
}
