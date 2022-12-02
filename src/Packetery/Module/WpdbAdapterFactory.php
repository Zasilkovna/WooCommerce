<?php
/**
 * Class WpdbAdapterFactory
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

/**
 * Class WpdbAdapterFactory
 *
 * @package Packetery
 */
class WpdbAdapterFactory {

	/**
	 * Creates WpdbAdapter instance
	 *
	 * @return WpdbAdapter
	 */
	public function create(): WpdbAdapter {
		global $wpdb;

		$instance = new WpdbAdapter( $wpdb );

		$instance->posts   = $wpdb->posts;
		$instance->options = $wpdb->options;

		return $instance;
	}
}
