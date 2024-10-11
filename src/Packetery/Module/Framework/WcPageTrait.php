<?php
/**
 * Trait WcPageTrait.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Framework;

/**
 * Trait WcPageTrait.
 *
 * @package Packetery
 */
trait WcPageTrait {

	/**
	 * Retrieve page ids - used for myaccount, edit_address, shop, cart, checkout, pay, view_order, terms. returns -1 if no page is found.
	 *
	 * @param string $page Page slug.
	 *
	 * @return int
	 */
	public function getPageId( string $page ): int {
		return wc_get_page_id( $page );
	}
}
