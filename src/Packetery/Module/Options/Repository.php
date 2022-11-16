<?php
/**
 * Class Repository.
 *
 * @package Packetery\Module\Options
 */

declare( strict_types=1 );

namespace Packetery\Module\Options;

use Packetery\Module\Wpdb;

/**
 * Class Repository.
 *
 * @package Packetery\Module\Options
 */
class Repository {

	/**
	 * Wpdb.
	 *
	 * @var Wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 *
	 * @param Wpdb $wpdb Wpdb.
	 */
	public function __construct( Wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Get all packetery related options.
	 *
	 * @return array|object|null
	 */
	public function getPluginOptions() {
		$wpdb = $this->wpdb;

		return $wpdb->get_results( "SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE 'packetery%'" );
	}

}
