<?php
/**
 * Class Repository.
 *
 * @package Packetery\Module\Options
 */

declare( strict_types=1 );

namespace Packetery\Module\Options;

/**
 * Class Repository.
 *
 * @package Packetery\Module\Options
 */
class Repository {

	/**
	 * Get all packetery related options.
	 *
	 * @return array|object|null
	 */
	public function getPluginOptions() {
		global $wpdb;

		return $wpdb->get_results( "SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE 'packetery%'" );
	}

}
