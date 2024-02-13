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

	/**
	 * Fork of delete_expired_transients.
	 *
	 * @param string $prefix Custom transient prefix.
	 *
	 * @return int|bool
	 */
	public function deleteExpiredTransientsByPrefix( string $prefix ) {
		$transientPrefix        = sprintf( '_transient_%s', $prefix );
		$transientTimeoutPrefix = sprintf( '_transient_timeout_%s', $prefix );

		return $this->wpdbAdapter->query(
			$this->wpdbAdapter->prepare(
				"DELETE a, b FROM {$this->wpdbAdapter->options} a, {$this->wpdbAdapter->options} b
				WHERE a.option_name LIKE %s
				AND a.option_name NOT LIKE %s
				AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
				AND b.option_value < %d",
				$this->wpdbAdapter->escLike( $transientPrefix ) . '%',
				$this->wpdbAdapter->escLike( $transientTimeoutPrefix ) . '%',
				time()
			)
		);
	}

}
