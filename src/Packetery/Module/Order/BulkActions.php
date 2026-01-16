<?php

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Latte\Engine;
use Packetery\Module\Log\ArgumentTypeErrorLogger;
use Packetery\Nette\Http\Request;
use WC_Order;

class BulkActions {
	const ACTION_SUBMIT_TO_API = 'submit_to_api';

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
	private ArgumentTypeErrorLogger $argumentTypeErrorLogger;

	public function __construct(
		Engine $latteEngine,
		Request $httpRequest,
		PacketSubmitter $packetSubmitter,
		Repository $orderRepository,
		ArgumentTypeErrorLogger $argumentTypeErrorLogger
	) {
		$this->latteEngine             = $latteEngine;
		$this->httpRequest             = $httpRequest;
		$this->packetSubmitter         = $packetSubmitter;
		$this->orderRepository         = $orderRepository;
		$this->argumentTypeErrorLogger = $argumentTypeErrorLogger;
	}

	/**
	 * Adds custom actions to dropdown in admin order list.
	 *
	 * @param array<string, string>|mixed $actions Array of action.
	 *
	 * @return array<string, string>|mixed
	 */
	public function addActions( $actions ) {
		if ( ! is_array( $actions ) ) {
			$this->argumentTypeErrorLogger->log( __METHOD__, 'actions', 'array', $actions );

			return $actions;
		}

		$actions[ self::ACTION_SUBMIT_TO_API ]                     = __( 'Packeta export', 'packeta' );
		$actions[ LabelPrint::ACTION_PACKETA_LABELS ]              = __( 'Packeta download labels', 'packeta' );
		$actions[ LabelPrint::ACTION_CARRIER_LABELS ]              = __( 'Packeta download carrier labels', 'packeta' );
		$actions[ CollectionPrint::ACTION_PRINT_ORDER_COLLECTION ] = __( 'Packeta AWB (delivery note)', 'packeta' );

		return $actions;
	}

	/**
	 * Executes the action for selected orders and returns url to redirect to.
	 *
	 * @param string|mixed $redirectTo Url.
	 * @param string|mixed $action Action id.
	 * @param int[]|mixed  $postIds Order ids.
	 *
	 * @return string|mixed
	 */
	public function handleActions( $redirectTo, $action, $postIds ) {
		if ( ! is_string( $redirectTo ) ) {
			$this->argumentTypeErrorLogger->log( __METHOD__, 'redirectTo', 'string', $redirectTo );

			return $redirectTo;
		}

		if ( ! is_string( $action ) ) {
			$this->argumentTypeErrorLogger->log( __METHOD__, 'action', 'string', $action );

			return $redirectTo;
		}

		if ( ! is_array( $postIds ) ) {
			$this->argumentTypeErrorLogger->log( __METHOD__, 'postIds', 'array', $postIds );

			return $redirectTo;
		}

		if ( $action === CollectionPrint::ACTION_PRINT_ORDER_COLLECTION ) {
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

		if ( $action === self::ACTION_SUBMIT_TO_API ) {
			$finalSubmissionResult = new PacketSubmissionResult();
			foreach ( $postIds as $postId ) {
				if ( ! is_numeric( $postId ) ) {
					$this->argumentTypeErrorLogger->log( __METHOD__, 'postId', 'is_numeric', $postId );

					continue;
				}

				$wcOrder = $this->orderRepository->getWcOrderById( (int) $postId );

				if ( ! $wcOrder instanceof WC_Order ) {
					$this->argumentTypeErrorLogger->log( __METHOD__, 'wcOrder', 'WC_Order', $wcOrder );

					continue;
				}

				$submissionResult = $this->packetSubmitter->submitPacket(
					$wcOrder,
					null,
					true
				);
				$finalSubmissionResult->merge( $submissionResult );
			}

			$queryArgs                               = $finalSubmissionResult->getCounter();
			$queryArgs[ self::ACTION_SUBMIT_TO_API ] = true;

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
		if ( ! isset( $get[ self::ACTION_SUBMIT_TO_API ] ) ) {
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
