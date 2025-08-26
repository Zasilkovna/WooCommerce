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
	 * @return string[]
	 */
	public function getExpiredTransientsByPrefix( string $prefix ): array {
		$transientPrefix        = sprintf( '_transient_%s', $prefix );
		$transientTimeoutPrefix = sprintf( '_transient_timeout_%s', $prefix );

		return $this->wpdbAdapter->get_col(
			$this->wpdbAdapter->prepare(
				"SELECT SUBSTRING(`a`.`option_name`, 12)
				FROM `{$this->wpdbAdapter->options}` `a`
				JOIN `{$this->wpdbAdapter->options}` `b`
				ON `b`.`option_name` = CONCAT('_transient_timeout_', SUBSTRING(`a`.`option_name`, 12))
				WHERE `a`.`option_name` LIKE %s
				AND `a`.`option_name` NOT LIKE %s
				AND `b`.`option_value` < %d",
				$this->wpdbAdapter->escLike( $transientPrefix ) . '%',
				$this->wpdbAdapter->escLike( $transientTimeoutPrefix ) . '%',
				time()
			)
		);
	}

	/**
	 * @param string[] $prefixes
	 *
	 * @return string[]
	 */
	public function getAllTransientsByPrefixes( array $prefixes ): array {
		if ( $prefixes === [] ) {
			return [];
		}

		$optionPrefixes = [];
		foreach ( $prefixes as $prefix ) {
			$optionPrefixes[] = '_transient_' . $prefix;
		}

		$transientNames = [];
		$result         = $this->getAllOptionNamesByPrefixes( $optionPrefixes );

		foreach ( $result as $optionName ) {
			$transientNames[] = preg_replace( '~^_transient_~', '', $optionName );
		}

		return $transientNames;
	}

	/**
	 * @param string[] $prefixes
	 *
	 * @return string[]
	 */
	public function getAllOptionNamesByPrefixes( array $prefixes ): array {
		if ( $prefixes === [] ) {
			return [];
		}

		$prefixWhere = [];
		foreach ( $prefixes as $prefix ) {
			$prefixWhere[] = sprintf( '`option_name` LIKE "%s"', $this->wpdbAdapter->escLike( $prefix ) . '%' );
		}

		$prefixWhereDisjunction = implode( ' OR ', $prefixWhere );

		return $this->wpdbAdapter->get_col( "SELECT `option_name` FROM `{$this->wpdbAdapter->options}` WHERE {$prefixWhereDisjunction}" );
	}
}
