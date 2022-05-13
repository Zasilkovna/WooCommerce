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
		$actions['submit_to_api']                                  = __( 'Submit orders to Packeta', 'packeta' );
		$actions[ LabelPrint::ACTION_PACKETA_LABELS ]              = __( 'Print labels', 'packeta' );
		$actions[ LabelPrint::ACTION_CARRIER_LABELS ]              = __( 'Print carrier labels', 'packeta' );
		$actions[ CollectionPrint::ACTION_PRINT_ORDER_COLLECTION ] = __( 'Print AWB', 'packeta' );

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
					'page' => CollectionPrint::PAGE_SLUG,
				],
				admin_url( 'admin.php' )
			);
		}

		if ( in_array( $action, [ LabelPrint::ACTION_PACKETA_LABELS, LabelPrint::ACTION_CARRIER_LABELS ], true ) ) {
			set_transient( LabelPrint::getOrderIdsTransientName(), $postIds, 60 * 60 );
			set_transient( LabelPrint::getBackLinkTransientName(), $redirectTo, 60 * 60 );

			return add_query_arg(
				[
					'page'                       => LabelPrint::MENU_SLUG,
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
		$get = $this->httpRequest->getQuery();
		if ( empty( $get['submit_to_api'] ) ) {
			return;
		}

		$success = null;
		if ( is_numeric( $get['success'] ) && $get['success'] > 0 ) {
			$success = __( 'Shipments were submitted successfully.', 'packeta' );
		}
		$ignored = null;
		if ( is_numeric( $get['ignored'] ) && $get['ignored'] > 0 ) {
			$ignored = sprintf(
			// translators: %s is count.
				__( 'Some shipments (%s in total) were not submitted (these were submitted already or are not Packeta orders).', 'packeta' ),
				$get['ignored']
			);
		}
		$errors = null;
		if ( is_numeric( $get['errors'] ) && $get['errors'] > 0 ) {
			// translators: %s is count.
			$errors = sprintf( __( 'Some shipments (%s in total) failed to be submitted to Packeta.', 'packeta' ), $get['errors'] );
		} elseif ( isset( $get['errors'] ) ) {
			$errors = $get['errors'];
		}

		$latteParams = [
			'success' => $success,
			'ignored' => $ignored,
			'errors'  => $errors,
		];
		$this->latteEngine->render( PACKETERY_PLUGIN_DIR . '/template/order/export-result.latte', $latteParams );
	}
}
