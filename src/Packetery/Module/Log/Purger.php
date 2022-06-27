<?php
/**
 * Class Page
 *
 * @package Packetery\Module\Log
 */

declare( strict_types=1 );


namespace Packetery\Module\Log;

use Packetery\Core\Helper;

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
		$this->logRepository->deleteOld( get_option( 'packetery_delete_old_before', '- 90 days' ) );
	}
}
