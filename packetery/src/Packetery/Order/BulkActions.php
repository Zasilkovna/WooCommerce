<?php
/**
 * Class BulkActions
 *
 * @package Packetery\Order
 */

namespace Packetery\Order;

use PacketeryLatte\Engine;
use PacketeryNette\Http\Request;
use WC_Order;

/**
 * Class BulkActions
 *
 * @package Packetery\Order
 */
class BulkActions {
	/**
	 * Latte engine.
	 *
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * HTTP request.
	 *
	 * @var Request
	 */
	private $httpRequest;

	/**
	 * OrderApi.
	 *
	 * @var OrderApi
	 */
	private $orderApi;

	/**
	 * BulkActions constructor.
	 *
	 * @param Engine   $latteEngine Latte engine.
	 * @param Request  $httpRequest HTTP request.
	 * @param OrderApi $orderApi OrderApi.
	 */
	public function __construct( Engine $latteEngine, Request $httpRequest, OrderApi $orderApi ) {
		$this->latteEngine = $latteEngine;
		$this->httpRequest = $httpRequest;
		$this->orderApi    = $orderApi;
	}

	/**
	 * Adds custom actions to dropdown in admin order list.
	 *
	 * @param array $actions Array of action.
	 *
	 * @return array
	 */
	public function addActions( array $actions ): array {
		$actions['submit_to_api'] = __( 'actionSubmitOrders', 'packetery' );
		$actions['print_labels']  = __( 'actionPrintLabels', 'packetery' );

		return $actions;
	}

	/**
	 * Executes the action for selected orders and returns url to redirect to.
	 *
	 * @param string $redirectTo Url.
	 * @param string $action Action id.
	 * @param array  $postIds Order ids.
	 *
	 * @return string
	 */
	public function handleActions( string $redirectTo, string $action, array $postIds ): string {
		if ( 'print_labels' === $action ) {
			return add_query_arg( [ 'orderIds' => implode( ',', $postIds ) ], 'admin.php?page=label-print' );
		}

		if ( 'submit_to_api' === $action ) {
			$results = [];
			foreach ( $postIds as $postId ) {
				$order = wc_get_order( $postId );
				if ( is_a( $order, WC_Order::class ) ) {
					$results = $this->orderApi->createPacket( $order, $results );
				}
			}

			$queryArgs = [
				'submit_to_api'   => '1',
				'submitted_count' => null,
				'skipped_count'   => null,
				'errors'          => null,
			];
			if ( ! empty( $results['SUCCESS'] ) ) {
				$queryArgs['submitted_count'] = count( $results['SUCCESS'] );
			}
			if ( ! empty( $results['INFO'] ) ) {
				$queryArgs['skipped_count'] = count( $results['INFO'] );
			}
			if ( ! empty( $results['ERROR'] ) ) {
				$queryArgs['errors'] = count( $results['ERROR'] );
			}

			return add_query_arg( $queryArgs, $redirectTo );
		}

		return $redirectTo;
	}
}
