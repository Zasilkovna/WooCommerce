<?php
/**
 * Class ContextResolver.
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );


namespace Packetery\Module;

use PacketeryNette\Http\Request;

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
	 * Tells if requesting user is at product detail page.
	 *
	 * @return bool
	 */
	public function isProductDetailPage(): bool {
		global $pagenow, $typenow;

		return 'post.php' === $pagenow && 'product' === $typenow;
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
}
