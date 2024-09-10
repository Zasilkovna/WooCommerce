<?php
/**
 * Trait WpAdapter.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

/**
 * Trait WpAdapter.
 *
 * @package Packetery
 */
class WpAdapter {
	use HookTrait;
	use TransientTrait;

	/**
	 * WP get_option adapter.
	 *
	 * @param string $optionId Option id.
	 *
	 * @return mixed
	 */
	public function getOption( string $optionId ) {
		return get_option( $optionId );
	}

	/**
	 * Gets WP term.
	 *
	 * @param int $termId Term id.
	 *
	 * @return \WP_Term|\WP_Error|null
	 */
	public function getTerm( int $termId ) {
		return get_term( $termId );
	}

}
