<?php
/**
 * Class Page
 *
 * @package Packetery\Module\Log
 */

declare( strict_types=1 );


namespace Packetery\Module\Log;

/**
 * Class Page
 *
 * @package Packetery\Module\Log
 */
class Purger {

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
	 * Hook for auto-deletion.
	 *
	 * @return void
	 */
	public function autoDeleteHook(): void {
		$this->autoDelete( 90 );
	}

	/**
	 * Auto delete.
	 *
	 * @param int $maxRecordAgeInDays Max number of days that record can exist.
	 *
	 * @return void
	 */
	private function autoDelete( int $maxRecordAgeInDays ): void {
		$this->logRepository->deleteMany(
			[
				'date_query' => [
					[
						'before' => '- ' . $maxRecordAgeInDays . ' days',
					],
				],
			]
		);
	}
}
