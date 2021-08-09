<?php
/**
 * Class Wpdb_Factory
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery;

/**
 * Class Wpdb_Factory
 *
 * @package Packetery
 */
class Wpdb_Factory {

	/**
	 * Creates wpdb factory
	 *
	 * @return \wpdb
	 */
	public function create(): \wpdb {
		global $wpdb;
		$wpdb->packetery_carrier = $wpdb->prefix . 'packetery_carrier';

		return $wpdb;
	}
}
