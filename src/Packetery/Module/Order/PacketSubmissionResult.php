<?php
/**
 * Class ValidationReport.
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

/**
 * Class ValidationReport.
 *
 * @package Packetery\Module\Order
 */
class PacketSubmissionResult {

	/**
	 * Submission result counter.
	 *
	 * @var int[]
	 */
	private $counter = [
		'success' => 0,
		'ignored' => 0,
		'errors'  => 0,
		'logs'    => 0,
	];

	/**
	 * Increases success count.
	 *
	 * @return void
	 */
	public function increaseSuccessCount(): void {
		$this->counter['success'] ++;
	}

	/**
	 * Increases ignored count.
	 *
	 * @return void
	 */
	public function increaseIgnoredCount(): void {
		$this->counter['ignored'] ++;
	}

	/**
	 * Increases errors count.
	 *
	 * @return void
	 */
	public function increaseErrorsCount(): void {
		$this->counter['errors'] ++;
	}

	/**
	 * Increases logs count.
	 *
	 * @return void
	 */
	public function increaseLogsCount(): void {
		$this->counter['logs'] ++;
	}

	/**
	 * Merges given result into $this.
	 *
	 * @param self $result Result.
	 *
	 * @return void
	 */
	public function merge( self $result ): void {
		$this->counter['success'] += $result->getCounter()['success'];
		$this->counter['ignored'] += $result->getCounter()['ignored'];
		$this->counter['errors']  += $result->getCounter()['errors'];
		$this->counter['logs']    += $result->getCounter()['logs'];
	}

	/**
	 * Gets counter.
	 *
	 * @return array
	 */
	public function getCounter(): array {
		return $this->counter;
	}
}
