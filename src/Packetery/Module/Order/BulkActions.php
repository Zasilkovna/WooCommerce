<?php
/**
 * Class BulkActions
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Latte\Engine;
use Packetery\Nette\Http\Request;

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
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * BulkActions constructor.
	 *
	 * @param Engine          $latteEngine     Latte engine.
	 * @param Request         $httpRequest     HTTP request.
	 * @param PacketSubmitter $packetSubmitter Order API Client.
	 * @param Repository      $orderRepository Order repository.
	 */
	public function __construct(
		Engine $latteEngine,
		Request $httpRequest,
		PacketSubmitter $packetSubmitter,
		Repository $orderRepository
	) {
		$this->latteEngine     = $latteEngine;
		$this->httpRequest     = $httpRequest;
		$this->packetSubmitter = $packetSubmitter;
		$this->orderRepository = $orderRepository;
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
			$finalSubmissionResult = new PacketSubmissionResult();
			foreach ( $postIds as $postId ) {
				$wcOrder = $this->orderRepository->getWcOrderById( $postId );
				if ( null !== $wcOrder ) {
					$submissionResult = $this->packetSubmitter->submitPacket(
						$wcOrder,
						null,
						true
					);
					$finalSubmissionResult->merge( $submissionResult );
				}
			}

			$queryArgs                  = $finalSubmissionResult->getCounter();
			$queryArgs['submit_to_api'] = true;

			if ( count( $postIds ) === 1 ) {
				$queryArgs['packetery_order_id'] = array_pop( $postIds );
			}

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

		$orderId = ( $get['packetery_order_id'] ?? null );
		if ( is_numeric( $orderId ) ) {
			$orderId = (int) $orderId;
		} else {
			$orderId = null;
		}

		$latteParams = $this->packetSubmitter->getTranslatedSubmissionMessages( $get, $orderId );
		$this->latteEngine->render( PACKETERY_PLUGIN_DIR . '/template/order/export-result.latte', $latteParams );
	}
}
