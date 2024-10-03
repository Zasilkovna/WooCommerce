<?php
/**
 * Class ContextResolver.
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );


namespace Packetery\Module;

use Packetery\Nette\Http\Request;

/**
 * Class ContextResolver.
 *
 * @package Packetery\Module
 */
class ContextResolver {

	/**
	 * Request.
	 *
	 * @var Request
	 */
	private $request;

	/**
	 * ContextResolver constructor.
	 *
	 * @param Request $request Packetery request.
	 */
	public function __construct( Request $request ) {
		$this->request = $request;
	}

	/**
	 * Tells if requesting user is at order grid page.
	 *
	 * @return bool
	 */
	public function isOrderGridPage(): bool {
		global $pagenow, $typenow, $plugin_page;

		if ( Helper::isHposEnabled() ) {
			return 'admin.php' === $pagenow && 'wc-orders' === $plugin_page && false === in_array( $this->request->getQuery( 'action' ), [ 'edit', 'new' ], true );
		}

		return 'edit.php' === $pagenow && 'shop_order' === $typenow;
	}

	/**
	 * Tells if requesting user is at order detail page.
	 *
	 * @return bool
	 */
	public function isOrderDetailPage(): bool {
		global $pagenow, $typenow, $plugin_page;

		if ( Helper::isHposEnabled() ) {
			return 'admin.php' === $pagenow && 'wc-orders' === $plugin_page && 'edit' === $this->request->getQuery( 'action' );
		}

		return 'post.php' === $pagenow && 'shop_order' === $typenow;
	}

	/**
	 * Tells if requesting user is at product detail page.
	 *
	 * @return bool
	 */
	private function isProductDetailPage(): bool {
		global $pagenow, $typenow;

		return 'post.php' === $pagenow && 'product' === $typenow;
	}

	/**
	 * Tells if requesting user is at product create page.
	 *
	 * @return bool
	 */
	private function isProductCreatePage(): bool {
		global $pagenow, $typenow;

		return 'post-new.php' === $pagenow && 'product' === $typenow;
	}

	/**
	 * Tells if requesting user is at product page.
	 *
	 * @return bool
	 */
	public function isProductPage(): bool {
		return $this->isProductCreatePage() || $this->isProductDetailPage();
	}

	/**
	 * Tells if requesting user is at page using packetery confirm.
	 *
	 * @return bool
	 */
	public function isPacketeryConfirmPage(): bool {
		return $this->isOrderDetailPage() || $this->isOrderGridPage();
	}

	/**
	 * Tells if requesting user is at product category grid page.
	 *
	 * @return bool
	 */
	public function isProductCategoryGridPage(): bool {
		global $pagenow;

		return 'edit-tags.php' === $pagenow && ProductCategory\Entity::TAXONOMY_NAME === $this->request->getQuery( 'taxonomy' );
	}

	/**
	 * Tells if requesting user is at product category grid page.
	 *
	 * @return bool
	 */
	public function isProductCategoryDetailPage(): bool {
		global $pagenow;

		return 'term.php' === $pagenow && ProductCategory\Entity::TAXONOMY_NAME === $this->request->getQuery( 'taxonomy' );
	}

	/**
	 * Tells if user is at page detail.
	 *
	 * @return bool
	 */
	public function isPageDetail(): bool {
		global $pagenow, $typenow;

		return 'post.php' === $pagenow && 'page' === $typenow;
	}

	/**
	 * Tells if current page is a shipping zone detail page.
	 *
	 * @return bool
	 */
	private function isShippingZoneDetailPage(): bool {
		global $pagenow, $plugin_page;

		return (
			'admin.php' === $pagenow &&
			'wc-settings' === $plugin_page &&
			'shipping' === $this->request->getQuery( 'tab' ) &&
			$this->request->getQuery( 'zone_id' ) > 0
		);
	}

	/**
	 * Gets id shipping zone id.
	 *
	 * @return int|null
	 */
	public function getShippingZoneId(): ?int {
		if ( $this->isShippingZoneDetailPage() ) {
			return (int) $this->request->getQuery( 'zone_id' );
		}

		return null;
	}

}
