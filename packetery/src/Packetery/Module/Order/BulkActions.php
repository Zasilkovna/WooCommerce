<?php
/**
 * Class BulkActions
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

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
	 * @var PacketSubmitter
	 */
	private $packetSubmitter;

	/**
	 * BulkActions constructor.
	 *
	 * @param Engine          $latteEngine Latte engine.
	 * @param Request         $httpRequest HTTP request.
	 * @param PacketSubmitter $packetSubmitter Order API Client.
	 */
	public function __construct( Engine $latteEngine, Request $httpRequest, PacketSubmitter $packetSubmitter ) {
		$this->latteEngine     = $latteEngine;
		$this->httpRequest     = $httpRequest;
		$this->packetSubmitter = $packetSubmitter;
	}

	/**
	 * Adds custom actions to dropdown in admin order list.
	 *
	 * @param array $actions Array of action.
	 *
	 * @return array
	 */
	public function addActions( array $actions ): array {
		$actions['submit_to_api']                                  = __( 'actionSubmitOrders', 'packetery' );
		$actions[ LabelPrint::ACTION_PACKETA_LABELS ]              = __( 'actionPrintLabels', 'packetery' );
		$actions[ LabelPrint::ACTION_CARRIER_LABELS ]              = __( 'actionPrintCarrierLabels', 'packetery' );
		$actions[ CollectionPrint::ACTION_PRINT_ORDER_COLLECTION ] = __( 'actionPrintOrderCollection', 'packetery' );

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
		if ( CollectionPrint::ACTION_PRINT_ORDER_COLLECTION === $action ) {
			set_transient( CollectionPrint::getOrderIdsTransientName(), $postIds );

			return add_query_arg(
				[
					'page'   => CollectionPrint::PAGE_SLUG,
				],
				admin_url( 'admin.php' )
			);
		}

		if ( in_array( $action, [ LabelPrint::ACTION_PACKETA_LABELS, LabelPrint::ACTION_CARRIER_LABELS ], true ) ) {
			set_transient( LabelPrint::getOrderIdsTransientName(), $postIds, 60 * 60 );
			set_transient( LabelPrint::getBackLinkTransientName(), $redirectTo, 60 * 60 );

			return add_query_arg(
				[
					'page'                       => 'label-print',
					LabelPrint::LABEL_TYPE_PARAM => $action,
				],
				'admin.php'
			);
		}

		if ( 'submit_to_api' === $action ) {
			$resultsCounter = [
				'success' => 0,
				'ignored' => 0,
				'errors'  => 0,
			];
			foreach ( $postIds as $postId ) {
				$order = wc_get_order( $postId );
				if ( is_a( $order, WC_Order::class ) ) {
					$this->packetSubmitter->submitPacket( $order, $resultsCounter );
				}
			}

			$queryArgs                  = $resultsCounter;
			$queryArgs['submit_to_api'] = true;

			return add_query_arg( $queryArgs, $redirectTo );
		}

		return $redirectTo;
	}

	/**
	 * Renders packets export result.
	 */
	public function renderPacketsExportResult(): void {
		$get = $this->httpRequest->query;
		if ( empty( $get['submit_to_api'] ) ) {
			return;
		}

		$latteParams = [
			'success' => (int) ( $get['success'] ?? 0 ),
			'ignored' => (int) ( $get['ignored'] ?? 0 ),
			'errors'  => (int) ( $get['errors'] ?? 0 ),
		];
		$this->latteEngine->render( PACKETERY_PLUGIN_DIR . '/template/order/export-result.latte', $latteParams );
	}
}
