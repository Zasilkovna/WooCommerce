<?php
/**
 * Class ActionSchedulerTrait.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

/**
 * Class ActionSchedulerTrait.
 *
 * @package Packetery
 */
trait ActionSchedulerTrait {
	/**
	 * Schedule an action to run one time.
	 *
	 * @param int    $timestamp Timestamp.
	 * @param string $hook Hook.
	 * @param array  $args Arguments.
	 *
	 * @return void
	 */
	public function asScheduleSingleAction( int $timestamp, string $hook, array $args = [] ): void {
		as_schedule_single_action( $timestamp, $hook, $args );
	}
}
