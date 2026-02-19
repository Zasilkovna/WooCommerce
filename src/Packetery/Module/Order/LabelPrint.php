<?php

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api\Soap\Client;
use Packetery\Core\Api\Soap\ILabelResponse;
use Packetery\Core\Api\Soap\Request;
use Packetery\Core\Api\Soap\Response;
use Packetery\Core\Log;
use Packetery\Latte\Engine;
use Packetery\Module\Dashboard\DashboardPage;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Labels\CarrierLabelService;
use Packetery\Module\Labels\LabelPrintPacketData;
use Packetery\Module\Labels\LabelPrintParametersService;
use Packetery\Module\MessageManager;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Plugin;
use Packetery\Module\Transients;
use Packetery\Nette\Http;

class LabelPrint {

	public const ACTION_PACKETA_LABELS = 'print_packeta_labels';
	public const ACTION_CARRIER_LABELS = 'print_carrier_labels';
	public const LABEL_TYPE_PARAM      = 'label_type';
	public const MENU_SLUG             = 'label-print';

	/**
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * @var Http\Request
	 */
	private $httpRequest;

	/**
	 * @var Client
	 */
	private $soapApiClient;

	/**
	 * @var MessageManager
	 */
	private $messageManager;

	/**
	 * @var Log\ILogger
	 */
	private $logger;

	/**
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * @var PacketActionsCommonLogic
	 */
	private $packetActionsCommonLogic;

	/**
	 * @var ModuleHelper
	 */
	private $moduleHelper;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var CarrierLabelService
	 */
	private $carrierLabelService;

	/**
	 * @var LabelPrintParametersService
	 */
	private $labelPrintParametersService;

	public function __construct(
		Engine $latteEngine,
		OptionsProvider $optionsProvider,
		Http\Request $httpRequest,
		Client $soapApiClient,
		MessageManager $messageManager,
		Log\ILogger $logger,
		Repository $orderRepository,
		PacketActionsCommonLogic $packetActionsCommonLogic,
		ModuleHelper $moduleHelper,
		WpAdapter $wpAdapter,
		CarrierLabelService $carrierLabelService,
		LabelPrintParametersService $labelPrintParametersService
	) {
		$this->latteEngine                 = $latteEngine;
		$this->optionsProvider             = $optionsProvider;
		$this->httpRequest                 = $httpRequest;
		$this->soapApiClient               = $soapApiClient;
		$this->messageManager              = $messageManager;
		$this->logger                      = $logger;
		$this->orderRepository             = $orderRepository;
		$this->packetActionsCommonLogic    = $packetActionsCommonLogic;
		$this->moduleHelper                = $moduleHelper;
		$this->wpAdapter                   = $wpAdapter;
		$this->carrierLabelService         = $carrierLabelService;
		$this->labelPrintParametersService = $labelPrintParametersService;
	}

	/**
	 * Generates id of transient to store order ids.
	 *
	 * @return string
	 */
	public static function getOrderIdsTransientName(): string {
		return Transients::LABEL_PRINT_ORDER_IDS_PREFIX . wp_get_session_token();
	}

	/**
	 * Generates id of transient to store back link.
	 *
	 * @return string
	 */
	public static function getBackLinkTransientName(): string {
		return Transients::LABEL_PRINT_BACK_LINK_PREFIX . wp_get_session_token();
	}

	/**
	 * Prepares form and renders template.
	 */
	public function render(): void {
		$form = $this->labelPrintParametersService->createForm( $this->optionsProvider->getLabelMaxOffset( $this->labelPrintParametersService->getLabelFormat() ) );

		$count           = 0;
		$isCarrierLabels = ( $this->httpRequest->getQuery( self::LABEL_TYPE_PARAM ) === self::ACTION_CARRIER_LABELS );

		$orderIdsTransient = $this->getOrderIdsTransient();
		if ( $orderIdsTransient !== null ) {
			$labelPrintData = $this->getLabelPrintPacketDataFromTransient( $orderIdsTransient, $isCarrierLabels );
			$count          = $labelPrintData->getCount();
		} else {
			$this->messageManager->flash_message( __( 'No orders were selected', 'packeta' ), MessageManager::TYPE_INFO, MessageManager::RENDERER_PACKETERY, self::MENU_SLUG );
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/label-print.latte',
			[
				'form'          => $form,
				'count'         => $count,
				'backLink'      => get_transient( self::getBackLinkTransientName() ),
				'flashMessages' => $this->messageManager->renderToString( MessageManager::RENDERER_PACKETERY, self::MENU_SLUG ),
				'translations'  => [
					'packeta'               => __( 'Packeta', 'packeta' ),
					'labelPrinting'         => __( 'Print labels', 'packeta' ),
					// translators: %s is count.
					'numberOfLabelsToPrint' => __( 'Number of labels to print: %s', 'packeta' ),

					'back'                  => __( 'Back', 'packeta' ),
					'printLabels'           => __( 'Print labels', 'packeta' ),
				],
			]
		);
	}

	public function outputLabelsPdf(): void {
		if ( $this->httpRequest->getQuery( 'page' ) !== self::MENU_SLUG ) {
			return;
		}

		$fallbackToPacketaLabel = false;
		$isCarrierLabels        = ( $this->httpRequest->getQuery( self::LABEL_TYPE_PARAM ) === self::ACTION_CARRIER_LABELS );
		$orderIdParam           = $this->httpRequest->getQuery( 'id' ) !== null ? (int) $this->httpRequest->getQuery( 'id' ) : null;
		$packetIdParam          = $this->httpRequest->getQuery( 'packet_id' );
		if ( $orderIdParam !== null && is_string( $packetIdParam ) ) {
			$fallbackToPacketaLabel = true;
			$order                  = $this->orderRepository->getByIdWithValidCarrier( $orderIdParam );
			$labelPrintData         = new LabelPrintPacketData();
			if ( $order !== null ) {
				$labelPrintData->addItem( $order, $packetIdParam );
			}
		} else {
			$orderIdsTransient = $this->getOrderIdsTransient();
			if ( $orderIdsTransient === null ) {
				return;
			}

			$labelPrintData = $this->getLabelPrintPacketDataFromTransient( $orderIdsTransient, $isCarrierLabels );
		}
		$labelPrintData = $this->labelPrintParametersService->removeExternalCarriers( $labelPrintData, $isCarrierLabels, $fallbackToPacketaLabel );

		if ( $labelPrintData->isEmpty() ) {
			if ( $isCarrierLabels === true ) {
				$this->messageManager->flash_message(
					$this->wpAdapter->__( 'No orders have been selected for Packeta carriers', 'packeta' ),
					'info'
				);
			} else {
				$this->messageManager->flash_message(
					$this->wpAdapter->__( 'No orders have been selected for Packeta pick-up points', 'packeta' ),
					'info'
				);
			}

			$this->packetActionsCommonLogic->redirectTo( PacketActionsCommonLogic::REDIRECT_TO_ORDER_GRID );

			return;
		}

		$offset = $this->labelPrintParametersService->getOffset();
		if ( $offset === null ) {
			return;
		}

		$response = $this->getResponse( $isCarrierLabels, $labelPrintData, $fallbackToPacketaLabel, $orderIdParam, $offset );
		if ( $response === null ) {
			return;
		}

		if ( $response->hasFault() ) {
			if ( $fallbackToPacketaLabel === true && $labelPrintData->getCount() === 1 ) {
				$firstItem = $labelPrintData->getFirstItem();
				$message   = sprintf(
					// translators: %s represents shipment tracking number
					$this->wpAdapter->__( 'Label printing for shipment %s failed, you can find more information in the Packeta log.', 'packeta' ),
					$firstItem->getPacketId()
				);
			} elseif ( $isCarrierLabels === true ) {
				$message = sprintf(
					// translators: %s represents error message
					$this->wpAdapter->__( 'Carrier label printing failed, you can find more information in the Packeta log. Error: %s', 'packeta' ),
					$response->getFaultString()
				);
			} else {
				$message = sprintf(
					// translators: %s represents error message
					$this->wpAdapter->__( 'Label printing failed, you can find more information in the Packeta log. Error: %s', 'packeta' ),
					$response->getFaultString()
				);
			}

			$this->flashMessageAndRedirect( $message, $orderIdParam );

			return;
		}

		foreach ( $labelPrintData->getItems() as $item ) {
			$this->addLabelCreationInfoToWcOrderNote( (int) $item->getOrder()->getNumber(), $item->getPacketId(), $response );
		}

		header( 'Content-Type: application/pdf' );
		header( 'Content-Transfer-Encoding: Binary' );
		header( 'Content-Length: ' . strlen( $response->getPdfContents() ) );
		header( 'Content-Disposition: attachment; filename="' . $this->getFilename() . '"' );
		// @codingStandardsIgnoreStart
		echo $response->getPdfContents();
		// @codingStandardsIgnoreEnd
		exit;
	}

	/**
	 * Registers submenu item.
	 */
	public function register(): void {
		add_submenu_page(
			DashboardPage::SLUG,
			__( 'Print labels', 'packeta' ),
			__( 'Print labels', 'packeta' ),
			'manage_woocommerce',
			self::MENU_SLUG,
			array(
				$this,
				'render',
			),
			20
		);
	}

	/**
	 * Hides submenu item.
	 */
	public function hideFromMenus(): void {
		Plugin::hideSubmenuItem( self::MENU_SLUG );
	}

	private function requestPacketaLabels( int $offset, LabelPrintPacketData $labelPrintData ): Response\PacketsLabelsPdf {
		$request  = new Request\PacketsLabelsPdf( $labelPrintData->getPacketIds(), $this->labelPrintParametersService->getLabelFormat(), $offset );
		$response = $this->soapApiClient->packetsLabelsPdf( $request );

		foreach ( $labelPrintData->getItems() as $item ) {
			$order                 = $item->getOrder();
			$packetId              = $item->getPacketId();
			$isClaimAssistantLabel = $order->isPacketClaim( $packetId );

			$record          = new Log\Record();
			$record->action  = $isClaimAssistantLabel ? Log\Record::ACTION_CLAIM_LABEL_PRINT : Log\Record::ACTION_LABEL_PRINT;
			$record->orderId = (int) $order->getNumber();

			if ( ! $response->hasFault() ) {
				if ( $isClaimAssistantLabel === false ) {
					$order->setIsLabelPrinted( true );
				}

				$record->status = Log\Record::STATUS_SUCCESS;
				if ( $isClaimAssistantLabel === true ) {
					$record->title = $this->wpAdapter->__( 'Claim assistant label was printed successfully.', 'packeta' );
				} else {
					$record->title = $this->wpAdapter->__( 'Label was printed successfully.', 'packeta' );
				}
			} else {
				$record->status = Log\Record::STATUS_ERROR;
				if ( $isClaimAssistantLabel === true ) {
					$record->title = $this->wpAdapter->__( 'Claim assistant label could not be printed.', 'packeta' );
				} else {
					$record->title = $this->wpAdapter->__( 'Label could not be printed.', 'packeta' );
				}
				$record->params = [
					'packetId'          => $packetId,
					'isPacketIdInvalid' => $response->hasInvalidPacketId( (string) $packetId ),
					'request'           => [
						'packetIds' => $request->getPacketIds(),
						'format'    => $request->getFormat(),
						'offset'    => $request->getOffset(),
					],
					'errorMessage'      => $response->getFaultString(),
				];
			}

			$this->logger->add( $record );

			$order->updateApiErrorMessage( $response->getFaultString() );
			$this->orderRepository->save( $order );
		}

		return $response;
	}

	/**
	 * @param int                                                        $offset
	 * @param LabelPrintPacketData                                       $labelPrintPacketData
	 * @param array<int, array{packetId: string, courierNumber: string}> $packetIdsWithCourierNumbers
	 *
	 * @return Response\PacketsCourierLabelsPdf
	 */
	private function requestCarrierLabels( int $offset, LabelPrintPacketData $labelPrintPacketData, array $packetIdsWithCourierNumbers ): Response\PacketsCourierLabelsPdf {
		$request  = new Request\PacketsCourierLabelsPdf( array_values( $packetIdsWithCourierNumbers ), $this->labelPrintParametersService->getLabelFormat(), $offset );
		$response = $this->soapApiClient->packetsCarrierLabelsPdf( $request );

		foreach ( $packetIdsWithCourierNumbers as $orderId => $pairItem ) {
			$record          = new Log\Record();
			$record->action  = Log\Record::ACTION_CARRIER_LABEL_PRINT;
			$record->orderId = $orderId;
			$order           = $labelPrintPacketData->getOrderByOrderId( $orderId );

			if ( ! $response->hasFault() ) {
				$order->setIsLabelPrinted( true );

				$record->status = Log\Record::STATUS_SUCCESS;
				$record->title  = __( 'Carrier label has been printed successfully.', 'packeta' );
			} else {
				$record->status = Log\Record::STATUS_ERROR;
				$record->title  = __( 'Carrier label could not be printed.', 'packeta' );
				$record->params = [
					'packetId'               => $pairItem['packetId'],
					'courierNumber'          => $pairItem['courierNumber'],
					'isPacketIdInvalid'      => $response->hasInvalidPacketId( (string) $pairItem['packetId'] ),
					'isCourierNumberInvalid' => $response->hasInvalidCourierNumber( (string) $pairItem['courierNumber'] ),
					'request'                => [
						'packetIdsWithCourierNumbers' => $request->getPacketIdsWithCourierNumbers(),
						'format'                      => $request->getFormat(),
						'offset'                      => $request->getOffset(),
					],
					'errorMessage'           => $response->getFaultString(),
				];
			}
			$this->logger->add( $record );

			$order->updateApiErrorMessage( $response->getFaultString() );
			$this->orderRepository->save( $order );
		}

		return $response;
	}

	/**
	 * Gets filename for label pdf.
	 *
	 * @return string
	 */
	private function getFilename(): string {
		return 'packeta_labels_' . strtolower( str_replace( ' ', '_', $this->labelPrintParametersService->getLabelFormat() ) ) . '.pdf';
	}

	/**
	 * @param string[] $orderIds Order IDs from transient.
	 * @return LabelPrintPacketData
	 */
	private function getLabelPrintPacketDataFromTransient( array $orderIds, bool $isCarrierLabels ): LabelPrintPacketData {
		$orders               = $this->orderRepository->getByIds( $orderIds );
		$labelPrintPacketData = new LabelPrintPacketData();

		foreach ( $orders as $order ) {
			$packetId = $order->getPacketId();
			if ( $packetId === null ) {
				continue;
			}
			if ( ! $isCarrierLabels || $order->isExternalCarrier() ) {
				$labelPrintPacketData->addItem( $order, $packetId );
			}
		}

		return $labelPrintPacketData;
	}

	/**
	 * @return string[]|null
	 */
	private function getOrderIdsTransient(): ?array {
		$orderIds = get_transient( self::getOrderIdsTransientName() );
		if ( is_array( $orderIds ) ) {
			return $orderIds;
		}

		return null;
	}

	/**
	 * Saves information about the creation of the label in the order note.
	 */
	private function addLabelCreationInfoToWcOrderNote( int $orderId, string $packetId, ?ILabelResponse $response ): void {
		$order   = $this->orderRepository->findById( $orderId );
		$wcOrder = $this->orderRepository->getWcOrderById( $orderId );
		if ( $wcOrder === null || $order === null ) {
			return;
		}

		$linkText    = $order->getPacketBarcode();
		$trackingUrl = $order->getPacketTrackingUrl();
		if ( $response instanceof Response\PacketsLabelsPdf ) {
			if ( $order->isPacketClaim( $packetId ) ) {
				// translators: %s represents a packet tracking link.
				$message     = __( 'Packeta: Label for packet claim %s has been created', 'packeta' );
				$linkText    = $order->getPacketClaimBarcode();
				$trackingUrl = $order->getPacketClaimTrackingUrl();
			} else {
				// translators: %s represents a packet tracking link.
				$message = __( 'Packeta: Label for packet %s has been created', 'packeta' );
			}
		} else {
			// Response is of type Response\PacketsCourierLabelsPdf or null.
			// translators: %s represents a packet tracking link.
			$message = __( 'Packeta: Carrier label for packet %s has been created', 'packeta' );
		}

		$wcOrder->add_order_note(
			sprintf(
				$message,
				$this->moduleHelper->createHtmlLink( $trackingUrl, $linkText )
			)
		);
		$wcOrder->save();
	}

	public function flashMessageAndRedirect( string $message, ?int $orderId ): void {
		$this->messageManager->flash_message( $message, MessageManager::TYPE_ERROR );

		$redirectTo = $this->httpRequest->getQuery( PacketActionsCommonLogic::PARAM_REDIRECT_TO );
		if ( $redirectTo === PacketActionsCommonLogic::REDIRECT_TO_ORDER_DETAIL && $orderId !== null ) {
			$this->packetActionsCommonLogic->redirectTo( $redirectTo, $this->orderRepository->findById( $orderId ) );
		}
		$this->packetActionsCommonLogic->redirectTo( PacketActionsCommonLogic::REDIRECT_TO_ORDER_GRID );
	}

	/**
	 * @param bool                 $isCarrierLabels
	 * @param LabelPrintPacketData $labelPrintData
	 * @param bool                 $fallbackToPacketaLabel
	 * @param int|null             $orderId
	 * @param int                  $offset
	 *
	 * @return Response\PacketsCourierLabelsPdf|Response\PacketsLabelsPdf|null
	 */
	private function getResponse(
		bool $isCarrierLabels,
		LabelPrintPacketData $labelPrintData,
		bool $fallbackToPacketaLabel,
		?int $orderId,
		int $offset
	) {
		if ( $isCarrierLabels === false ) {
			return $this->requestPacketaLabels( $offset, $labelPrintData );
		}

		$packetIdsWithCourierNumbers = $this->carrierLabelService->getPacketaPacketIdsWithCourierNumbers( $labelPrintData );
		if ( $fallbackToPacketaLabel === false && $packetIdsWithCourierNumbers === [] ) {
			$this->flashMessageAndRedirect(
				(string) $this->wpAdapter->__( 'Carrier label printing failed, you can find more information in the Packeta log.', 'packeta' ),
				$orderId
			);

			return null;
		}

		$response = $this->requestCarrierLabels( $offset, $labelPrintData, $packetIdsWithCourierNumbers );
		if ( $fallbackToPacketaLabel === true && $response->hasFault() ) {
			return $this->requestPacketaLabels( $offset, $labelPrintData );
		}

		return $response;
	}
}
