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
		'success'         => 0,
		'ignored'         => 0,
		'errors'          => 0,
		'logs'            => 0,
		'statusUnchanged' => 0,
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
	 * Increases count of situations when order status couldn't be changed.
	 *
	 * @return void
	 */
	public function increaseStatusUnchangedCount(): void {
		$this->counter['statusUnchanged'] ++;
	}

	/**
	 * Merges given result into $this.
	 *
	 * @param self $result Result.
	 *
	 * @return void
	 */
	public function merge( self $result ): void {
		$counter                           = $result->getCounter();
		$this->counter['success']         += $counter['success'];
		$this->counter['ignored']         += $counter['ignored'];
		$this->counter['errors']          += $counter['errors'];
		$this->counter['logs']            += $counter['logs'];
		$this->counter['statusUnchanged'] += $counter['statusUnchanged'];
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
