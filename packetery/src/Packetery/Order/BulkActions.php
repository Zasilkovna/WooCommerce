<?php
/**
 * Class BulkActions
 *
 * @package Packetery\Order
 */

namespace Packetery\Order;

/**
 * Class BulkActions
 *
 * @package Packetery\Order
 */
class BulkActions {

	/**
	 * Registers BulkActions hooks.
	 */
	public function register(): void {
		// Adding a custom action to admin order list bulk dropdown.
		add_filter( 'bulk_actions-edit-shop_order', [ $this, 'bulkActionsEditShopOrder' ], 20, 1 );

		// Make the action from selected orders.
		add_filter( 'handle_bulk_actions-edit-shop_order', [ $this, 'handleBulkActionsEditShopOrder' ], 10, 3 );
	}

	/**
	 * Hook filter method.
	 *
	 * @param array $actions Array of action.
	 *
	 * @return array
	 */
	public function bulkActionsEditShopOrder( array $actions ): array {
		$actions['submit_to_api'] = __( 'Submit orders to Packeta', 'packetery' );
		$actions['print_labels']  = __( 'Print labels', 'packetery' );

		return $actions;
	}

	/**
	 * Hook filter method.
	 *
	 * @param string $redirectTo Url.
	 * @param string $action Action.
	 * @param array  $postIds Order ids.
	 *
	 * @return string
	 */
	public function handleBulkActionsEditShopOrder( string $redirectTo, string $action, array $postIds ): string {
		return $redirectTo;
	}
}
