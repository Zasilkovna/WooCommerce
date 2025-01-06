<?php
/**
 * Class DbLogger
 *
 * @package Packetery\Module\Log
 */

declare( strict_types=1 );

namespace Packetery\Module\Log;

use Packetery\Core\CoreHelper;
use Packetery\Core\Log\ILogger;
use Packetery\Core\Log\Record;

/**
 * Class DbLogger
 *
 * @package Packetery\Module\Log
 */
class DbLogger implements ILogger {

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

	public function add( Record $record ): void {
		if ( $record->date === null ) {
			$record->date = CoreHelper::now();
		}

		$this->logRepository->save( $record );
	}

	/**
	 * Gets records.
	 *
	 * @param int|null              $orderId Order ID.
	 * @param string|null           $action  Action.
	 * @param array<string, string> $sorting Sorting config.
	 * @param int                   $limit   Limit.
	 *
	 * @return \Generator<Record>|array{}
	 * @throws \Exception From DateTimeImmutable.
	 */
	public function getRecords( ?int $orderId, ?string $action, array $sorting = [], int $limit = 100 ): iterable {
		$arguments = [
			'orderby' => $sorting,
			'limit'   => $limit,
		];

		if ( is_numeric( $orderId ) ) {
			$arguments['order_id'] = $orderId;
		}
		if ( $action !== null ) {
			$arguments['action'] = $action;
		}

		$logs = $this->logRepository->find( $arguments );
		if ( ! $logs instanceof \Generator ) {
			return [];
		}

		return $logs;
	}

	/**
	 * Counts records.
	 *
	 * @param int|null    $orderId Order ID.
	 * @param string|null $action  Action.
	 *
	 * @return int
	 */
	public function countRecords( ?int $orderId = null, ?string $action = null ): int {
		return $this->logRepository->countRows( $orderId, $action );
	}

	/**
	 * Gets logs for given period as array.
	 *
	 * @param array<array<string, string>> $dateQuery Date_query compatible array.
	 *
	 * @return \Generator<Record>|array{}
	 * @throws \Exception From DateTimeImmutable.
	 */
	public function getForPeriodAsArray( array $dateQuery ) {
		$arguments = [
			'orderby'    => [ 'date' => 'ASC' ],
			'date_query' => $dateQuery,
		];

		$logs = $this->logRepository->find( $arguments );
		if ( ! $logs instanceof \Generator ) {
			return [];
		}

		return $logs;
	}
}
