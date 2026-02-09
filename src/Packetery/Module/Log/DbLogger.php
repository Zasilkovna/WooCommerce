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
use Packetery\Core\Log\LogPageArguments;
use Packetery\Core\Log\Record;

/**
 * Class DbLogger
 *
 * @package Packetery\Module\Log
 */
class DbLogger implements ILogger {

	private Repository $logRepository;

	public function __construct( Repository $logRepository ) {
		$this->logRepository = $logRepository;
	}

	/**
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public function add( Record $record ) {
		if ( $record->date === null ) {
			$record->date = CoreHelper::now();
		}

		return $this->logRepository->save( $record );
	}

	/**
	 * @param LogPageArguments $arguments
	 *
	 * @return \Generator<Record>|array{}
	 * @throws \Exception From DateTimeImmutable.
	 */
	public function getRecords( LogPageArguments $arguments ): iterable {
		$logs = $this->logRepository->find( $arguments );
		if ( ! $logs instanceof \Generator ) {
			return [];
		}

		return $logs;
	}

	/**
	 * @param LogPageArguments $arguments
	 *
	 * @return int
	 */
	public function countRecords( LogPageArguments $arguments ): int {
		$where = $this->logRepository->buildQueryConditions( $arguments );

		return $this->logRepository->countRows( $arguments->getOrderId(), $arguments->getAction(), $where );
	}

	/**
	 * @param string $before DateTime modifier.
	 */
	public function deleteOld( string $before ): void {
		$this->logRepository->deleteOld( $before );
	}

	/**
	 * @param array<array<string, string>> $dateQuery Date_query compatible array.
	 *
	 * @return \Generator<Record>|array{}
	 * @throws \Exception From DateTimeImmutable.
	 */
	public function getForPeriodAsArray( array $dateQuery ) {
		$arguments = new LogPageArguments();
		$arguments->setOrderBy( [ 'date' => 'ASC' ] );
		$arguments->setDateQuery( $dateQuery );
		$arguments->setUseExactTimes( true );

		$logs = $this->logRepository->find( $arguments );
		if ( ! $logs instanceof \Generator ) {
			return [];
		}

		return $logs;
	}
}
