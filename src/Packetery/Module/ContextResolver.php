<?php
/**
 * Class ContextResolver.
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Module\Framework\WpAdapter;
use Packetery\Nette\Http\Request;
use WP_REST_Request;

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
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * Routes of the REST requests whose callbacks run now. The last item is the current route.
	 *
	 * @var string[]
	 */
	private $restRoutes = [];

	/**
	 * ContextResolver constructor.
	 *
	 * @param Request   $request   Packetery request.
	 * @param WpAdapter $wpAdapter WP adapter.
	 */
	public function __construct( Request $request, WpAdapter $wpAdapter ) {
		$this->request   = $request;
		$this->wpAdapter = $wpAdapter;
	}

	/**
	 * Keeps the route of the REST request before its callback runs.
	 *
	 * @param mixed                     $response Response that the filter does not change.
	 * @param array<mixed>              $handler  Route handler.
	 * @param WP_REST_Request<string[]> $request  REST request.
	 *
	 * @return mixed
	 */
	// phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
	public function pushRestRoute( $response, array $handler, WP_REST_Request $request ) {
		$this->restRoutes[] = $request->get_route();

		return $response;
	}

	/**
	 * Removes the route of the REST request after its callback runs.
	 *
	 * @param mixed $response Response that the filter does not change.
	 *
	 * @return mixed
	 */
	public function popRestRoute( $response ) {
		array_pop( $this->restRoutes );

		return $response;
	}

	/**
	 * Tells if the shipping method settings show the link to the carrier settings.
	 * The link is necessary in wp-admin and in the WooCommerce REST API v1 to v3.
	 * The checkout and the Store API do not show the link.
	 */
	public function isCarrierSettingsLinkContext(): bool {
		if ( $this->wpAdapter->isAdmin() ) {
			return true;
		}

		$currentRoute = end( $this->restRoutes );

		return $currentRoute !== false && preg_match( '#^/wc/v[1-3](/|$)#', $currentRoute ) === 1;
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

	public function isPluginsOverviewPage(): bool {
		global $pagenow;

		return $pagenow === 'plugins.php';
	}
}
