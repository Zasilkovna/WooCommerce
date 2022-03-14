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
	 * @param array $sorting Sorting config.
	 *
	 * @return iterable|Record[]
	 */
	public function getRecords( array $sorting = [] ): iterable {
		$arguments = [
			'orderby' => $sorting,
			'limit'   => 100,
		];

		$logs = $this->logRepository->find( $arguments );
		if ( ! $logs ) {
			return [];
		}

		return $logs;
	}

	/**
	 * Gets logs for given period as array.
	 *
	 * @param array $dateQuery Date_query compatible array.
	 *
	 * @return array
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
