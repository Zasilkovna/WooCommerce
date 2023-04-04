<?php
/**
 * Class Repository.
 *
 * @package Packetery\Module\Options
 */

declare( strict_types=1 );

namespace Packetery\Module\Options;

use Packetery\Module\WpdbAdapter;

/**
 * Class Repository.
 *
 * @package Packetery\Module\Options
 */
class Repository {

	/**
	 * WpdbAdapter.
	 *
	 * @var WpdbAdapter
	 */
	private $wpdbAdapter;

	/**
	 * Constructor.
	 *
	 * @param WpdbAdapter $wpdbAdapter WpdbAdapter.
	 */
	public function __construct( WpdbAdapter $wpdbAdapter ) {
		$this->wpdbAdapter = $wpdbAdapter;
	}

	/**
	 * Get all packetery related options.
	 *
	 * @return object[]|null
	 */
	public function getPluginOptions(): ?array {
		return $this->wpdbAdapter->get_results( 'SELECT `option_name` FROM `' . $this->wpdbAdapter->options . "` WHERE `option_name` LIKE 'packetery%'" );
	}

}
