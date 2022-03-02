<?php
/**
 * Class Wpdb_Factory
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

/**
 * Class Wpdb_Factory
 *
 * @package Packetery
 */
class WpdbFactory {

	/**
	 * Gets wpdb instance
	 *
	 * @return \wpdb
	 */
	public function create(): \wpdb {
		global $wpdb;
		$wpdb->packetery_carrier = $wpdb->prefix . 'packetery_carrier';
		$wpdb->packetery_order   = $wpdb->prefix . 'packetery_order';

		return $wpdb;
	}
}
