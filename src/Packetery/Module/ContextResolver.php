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
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
		global $pagenow, $typenow, $plugin_page;

		if ( ModuleHelper::isHposEnabled() ) {
			// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
			return $pagenow === 'admin.php' && $plugin_page === 'wc-orders' && in_array( $this->request->getQuery( 'action' ), [ 'edit', 'new' ], true ) === false;
		}

		return $pagenow === 'edit.php' && $typenow === 'shop_order';
	}

	/**
	 * Tells if requesting user is at order detail page.
	 *
	 * @return bool
	 */
	public function isOrderDetailPage(): bool {
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
		global $pagenow, $typenow, $plugin_page;

		if ( ModuleHelper::isHposEnabled() ) {
			// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
			return $pagenow === 'admin.php' && $plugin_page === 'wc-orders' && $this->request->getQuery( 'action' ) === 'edit';
		}

		return $pagenow === 'post.php' && $typenow === 'shop_order';
	}

	/**
	 * Tells if requesting user is at product detail page.
	 *
	 * @return bool
	 */
	private function isProductDetailPage(): bool {
		global $pagenow, $typenow;

		return $pagenow === 'post.php' && $typenow === 'product';
	}

	/**
	 * Tells if requesting user is at product create page.
	 *
	 * @return bool
	 */
	private function isProductCreatePage(): bool {
		global $pagenow, $typenow;

		return $pagenow === 'post-new.php' && $typenow === 'product';
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
	public function isConfirmModalPage(): bool {
		return $this->isOrderDetailPage() || $this->isOrderGridPage();
	}

	/**
	 * Tells if requesting user is at product category grid page.
	 *
	 * @return bool
	 */
	public function isProductCategoryGridPage(): bool {
		global $pagenow;

		return $pagenow === 'edit-tags.php' && $this->request->getQuery( 'taxonomy' ) === ProductCategory\Entity::TAXONOMY_NAME;
	}

	/**
	 * Tells if requesting user is at product category grid page.
	 *
	 * @return bool
	 */
	public function isProductCategoryDetailPage(): bool {
		global $pagenow;

		return $pagenow === 'term.php' && $this->request->getQuery( 'taxonomy' ) === ProductCategory\Entity::TAXONOMY_NAME;
	}

	/**
	 * Tells if user is at page detail.
	 *
	 * @return bool
	 */
	public function isPageDetail(): bool {
		global $pagenow, $typenow;

		return $pagenow === 'post.php' && $typenow === 'page';
	}

	private function isShippingZoneDetailPage(): bool {
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
		global $pagenow, $plugin_page;

		return (
			$pagenow === 'admin.php' &&
			// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
			$plugin_page === 'wc-settings' &&
			$this->request->getQuery( 'tab' ) === 'shipping' &&
			$this->request->getQuery( 'zone_id' ) > 0
		);
	}

	public function getShippingZoneId(): ?int {
		if ( $this->isShippingZoneDetailPage() ) {
			return (int) $this->request->getQuery( 'zone_id' );
		}

		return null;
	}
}
