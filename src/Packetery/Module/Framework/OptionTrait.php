<?php
/**
 * Trait OptionTrait.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

/**
 * Trait OptionTrait.
 *
 * @package Packetery
 */
trait OptionTrait {

	/**
	 * WP get_option adapter.
	 *
	 * @param string $optionId Option id.
	 * @param mixed  $defaultValue Default value.
	 *
	 * @return mixed
	 */
	public function getOption( string $optionId, $defaultValue = false ) {
		return get_option( $optionId, $defaultValue );
	}

	/**
	 * WP update_option adapter.
	 *
	 * @param string $optionId Option id.
	 * @param mixed  $newValue New value.
	 *
	 * @return bool
	 */
	public function updateOption( string $optionId, $newValue ): bool {
		return update_option( $optionId, $newValue );
	}

}
