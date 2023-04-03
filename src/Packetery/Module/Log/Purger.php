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

	public const PURGER_OPTION_NAME      = 'packetery_delete_old_before';
	public const PURGER_MODIFIER_DEFAULT = '- 90 days';

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
		$this->logRepository->deleteOld( get_option( self::PURGER_OPTION_NAME, self::PURGER_MODIFIER_DEFAULT ) );
	}
}
