<?php
/**
 * Class ContextResolver.
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );


namespace Packetery\Module;

/**
 * Class ContextResolver.
 *
 * @package Packetery\Module
 */
class ContextResolver {

	/**
	 * Tells if requesting user is at order grid page.
	 *
	 * @return bool
	 */
	public function isOrderGridPage(): bool {
		global $pagenow, $typenow;

		return 'edit.php' === $pagenow && 'shop_order' === $typenow;
	}

	/**
	 * Tells if requesting user is at order detail page.
	 *
	 * @return bool
	 */
	public function isOrderDetailPage(): bool {
		global $pagenow, $typenow;

		return 'post.php' === $pagenow && 'shop_order' === $typenow;
	}

	/**
	 * Tells if requesting user is at page using packetery confirm.
	 *
	 * @return bool
	 */
	public function isPacketeryConfirmPage(): bool {
		return $this->isOrderDetailPage() || $this->isOrderGridPage();
	}
}
