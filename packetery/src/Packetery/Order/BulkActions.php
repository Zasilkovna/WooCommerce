<?php
/**
 * Class BulkActions
 *
 * @package Packetery\Order
 */

namespace Packetery\Order;

use Packetery\Api\OrderClient;
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
	 * @var OrderClient
	 */
	private $orderApiClient;

	/**
	 * BulkActions constructor.
	 *
	 * @param Engine      $latteEngine Latte engine.
	 * @param Request     $httpRequest HTTP request.
	 * @param OrderClient $orderApiClient Order API Client.
	 */
	public function __construct( Engine $latteEngine, Request $httpRequest, OrderClient $orderApiClient ) {
		$this->latteEngine    = $latteEngine;
		$this->httpRequest    = $httpRequest;
		$this->orderApiClient = $orderApiClient;
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
	 * @param array $postIds Order ids.
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
					$results = $this->orderApiClient->submitPacket( $order, $results );
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

	/**
	 * Prints packets export result.
	 */
	public function adminNotices(): void {
		$get = $this->httpRequest->query;
		if ( empty( $get['submit_to_api'] ) ) {
			return;
		}

		$latteParams    = [
			'success' => null,
			'info'    => null,
			'error'   => null,
		];
		$submittedCount = ( isset( $get['submitted_count'] ) ? (int) $get['submitted_count'] : 0 );
		if ( $submittedCount ) {
			/* translators: %s: count of orders. */
			$latteParams['success'] = sprintf( __( 'someShipments%sSubmitted', 'packetery' ), $submittedCount );
		}

		$skippedCount = ( isset( $get['skipped_count'] ) ? (int) $get['skipped_count'] : 0 );
		if ( $skippedCount ) {
			/* translators: %s: count of orders. */
			$latteParams['info'] = sprintf( __( 'someShipments%sSkipped', 'packetery' ), $skippedCount );
		}

		$errors = ( isset( $get['errors'] ) ? (int) $get['errors'] : 0 );
		if ( $errors ) {
			/* translators: %s: count of orders. */
			$latteParams['error'] = sprintf( __( 'someShipments%sFailed', 'packetery' ), $errors );
		}

		$this->latteEngine->render( PACKETERY_PLUGIN_DIR . '/template/order/export-result.latte', $latteParams );
	}
}
