<?php
/**
 * Class LabelPrint.
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api\Soap\Client;
use Packetery\Core\Api\Soap\ILabelResponse;
use Packetery\Core\Api\Soap\Request;
use Packetery\Core\Api\Soap\Response;
use Packetery\Core\Log;
use Packetery\Core\Entity\Order;
use Packetery\Module\FormFactory;
use Packetery\Module\MessageManager;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Plugin;
use Packetery\Latte\Engine;
use Packetery\Nette\Forms\Form;
use Packetery\Nette\Http;

/**
 * Class LabelPrint.
 *
 * @package Packetery\Order
 */
class LabelPrint {
	public const ACTION_PACKETA_LABELS = 'print_packeta_labels';
	public const ACTION_CARRIER_LABELS = 'print_carrier_labels';
	public const LABEL_TYPE_PARAM      = 'label_type';
	public const MENU_SLUG             = 'label-print';

	/**
	 * PacketeryLatte Engine.
	 *
	 * @var Engine PacketeryLatte engine.
	 */
	private $latteEngine;

	/**
	 * Options Provider
	 *
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * Form factory.
	 *
	 * @var FormFactory Form factory.
	 */
	private $formFactory;

	/**
	 * Http Request.
	 *
	 * @var Http\Request
	 */
	private $httpRequest;

	/**
	 * SOAP API Client.
	 *
	 * @var Client SOAP API Client.
	 */
	private $soapApiClient;

	/**
	 * Message Manager.
	 *
	 * @var MessageManager
	 */
	private $messageManager;

	/**
	 * Logger.
	 *
	 * @var Log\ILogger
	 */
	private $logger;

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * Packet actions common logic.
	 *
	 * @var PacketActionsCommonLogic
	 */
	private $packetActionsCommonLogic;

	/**
	 * ModuleHelper.
	 *
	 * @var ModuleHelper
	 */
	private $moduleHelper;

	/**
	 * LabelPrint constructor.
	 *
	 * @param Engine                   $latteEngine              Latte Engine.
	 * @param OptionsProvider          $optionsProvider          Options provider.
	 * @param FormFactory              $formFactory              Form factory.
	 * @param Http\Request             $httpRequest              Http Request.
	 * @param Client                   $soapApiClient            SOAP API Client.
	 * @param MessageManager           $messageManager           Message Manager.
	 * @param Log\ILogger              $logger                   Logger.
	 * @param Repository               $orderRepository          Order repository.
	 * @param PacketActionsCommonLogic $packetActionsCommonLogic Packet actions common logic.
	 * @param ModuleHelper             $moduleHelper             ModuleHelper.
	 */
	public function __construct(
		Engine $latteEngine,
		OptionsProvider $optionsProvider,
		FormFactory $formFactory,
		Http\Request $httpRequest,
		Client $soapApiClient,
		MessageManager $messageManager,
		Log\ILogger $logger,
		Repository $orderRepository,
		PacketActionsCommonLogic $packetActionsCommonLogic,
		ModuleHelper $moduleHelper
	) {
		$this->latteEngine              = $latteEngine;
		$this->optionsProvider          = $optionsProvider;
		$this->formFactory              = $formFactory;
		$this->httpRequest              = $httpRequest;
		$this->soapApiClient            = $soapApiClient;
		$this->messageManager           = $messageManager;
		$this->logger                   = $logger;
		$this->orderRepository          = $orderRepository;
		$this->packetActionsCommonLogic = $packetActionsCommonLogic;
		$this->moduleHelper             = $moduleHelper;
	}

	/**
	 * Generates id of transient to store order ids.
	 *
	 * @return string
	 */
	public static function getOrderIdsTransientName(): string {
		return 'packetery_label_print_order_ids_' . wp_get_session_token();
	}

	/**
	 * Generates id of transient to store back link.
	 *
	 * @return string
	 */
	public static function getBackLinkTransientName(): string {
		return 'packetery_label_print_back_link_' . wp_get_session_token();
	}

	/**
	 * Gets label type.
	 *
	 * @param Order $order Order.
	 *
	 * @return string
	 */
	public function getLabelFormatByOrder( Order $order ): string {
		if ( $order->isExternalCarrier() ) {
			return $this->optionsProvider->get_carrier_label_format();
		}

		return $this->optionsProvider->get_packeta_label_format();
	}

	/**
	 * Prepares form and renders template.
	 */
	public function render(): void {
		$form = $this->createForm( $this->optionsProvider->getLabelMaxOffset( $this->getLabelFormat() ) );

		$count           = 0;
		$isCarrierLabels = ( $this->httpRequest->getQuery( self::LABEL_TYPE_PARAM ) === self::ACTION_CARRIER_LABELS );

		$orderIdsTransient = $this->getOrderIdsTransient();
		if ( $orderIdsTransient ) {
			$orderIds = $this->getPacketIdsFromTransient( $orderIdsTransient, $isCarrierLabels );
			$count    = count( $orderIds );
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

	/**
	 * Gets label format for current job.
	 *
	 * @return string
	 */
	private function getLabelFormat(): string {
		$packetaLabelFormat = $this->optionsProvider->get_packeta_label_format();
		$carrierLabelFormat = $this->optionsProvider->get_carrier_label_format();

		return ( $this->httpRequest->getQuery( self::LABEL_TYPE_PARAM ) === self::ACTION_CARRIER_LABELS ? $carrierLabelFormat : $packetaLabelFormat );
	}

	/**
	 * Outputs pdf.
	 */
	public function outputLabelsPdf(): void {
		if ( $this->httpRequest->getQuery( 'page' ) !== self::MENU_SLUG ) {
			return;
		}

		$fallbackToPacketaLabel = false;
		$isCarrierLabels        = ( $this->httpRequest->getQuery( self::LABEL_TYPE_PARAM ) === self::ACTION_CARRIER_LABELS );
		$idParam                = $this->httpRequest->getQuery( 'id' ) ? (int) $this->httpRequest->getQuery( 'id' ) : null;
		$packetIdParam          = $this->httpRequest->getQuery( 'packet_id' );
		if ( null !== $idParam && null !== $packetIdParam ) {
			$fallbackToPacketaLabel = true;
			$packetIds              = [ $idParam => $packetIdParam ];
		} else {
			$orderIdsTransient = $this->getOrderIdsTransient();
			if ( ! $orderIdsTransient ) {
				return;
			}

			$packetIds = $this->getPacketIdsFromTransient( $orderIdsTransient, $isCarrierLabels );
		}
		if ( ! $packetIds ) {
			$this->messageManager->flash_message( __( 'No suitable orders were selected', 'packeta' ), 'info' );
			$this->packetActionsCommonLogic->redirectTo( PacketActionsCommonLogic::REDIRECT_TO_ORDER_GRID );
			return;
		}

		$maxOffset   = $this->optionsProvider->getLabelMaxOffset( $this->getLabelFormat() );
		$form        = $this->createForm( $maxOffset );
		$offsetParam = $this->httpRequest->getQuery( 'offset' );
		if ( 0 === $maxOffset ) {
			$offset = 0;
		} elseif ( null !== $offsetParam ) {
			$offset = (int) $offsetParam;
		} elseif ( $form->isSubmitted() ) {
			$data   = $form->getValues( 'array' );
			$offset = $data['offset'];
		} else {
			return;
		}

		if ( $isCarrierLabels ) {
			$response = $this->requestCarrierLabels( $offset, $packetIds );
			if ( $fallbackToPacketaLabel && $response->hasFault() ) {
				$response = $this->requestPacketaLabels( $offset, $packetIds );
			}
		} else {
			$response = $this->requestPacketaLabels( $offset, $packetIds );
		}
		if ( ! $response || $response->hasFault() ) {
			$message = ( null !== $response && $response->hasFault() ) ?
				__( 'Label printing failed, you can find more information in the Packeta log.', 'packeta' ) :
				__( 'You selected orders that were not submitted yet', 'packeta' );
			$this->messageManager->flash_message( $message, MessageManager::TYPE_ERROR );

			$redirectTo = $this->httpRequest->getQuery( PacketActionsCommonLogic::PARAM_REDIRECT_TO );
			if ( PacketActionsCommonLogic::REDIRECT_TO_ORDER_DETAIL === $redirectTo && null !== $idParam ) {
				$this->packetActionsCommonLogic->redirectTo( $redirectTo, $this->orderRepository->findById( $idParam ) );
			}
			$this->packetActionsCommonLogic->redirectTo( PacketActionsCommonLogic::REDIRECT_TO_ORDER_GRID );
			return;
		}

		foreach ( $packetIds as $orderId => $packetId ) {
			$this->addLabelCreationInfoToWcOrderNote( $orderId, $packetId, $response );
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
	 * Creates offset setting form.
	 *
	 * @param int         $maxOffset Maximal offset.
	 * @param string|null $name      Form name.
	 *
	 * @return Form
	 */
	public function createForm( int $maxOffset, string $name = null ): Form {
		$form = $this->formFactory->create( $name );

		$availableOffsets = [];
		for ( $i = 0; $i <= $maxOffset; $i ++ ) {
			$availableOffsets[ $i ] = ( 0 === $i ?
				__( "don't skip any field on a print sheet", 'packeta' ) :
				// translators: %s is offset.
				sprintf( __( 'skip %s fields on first sheet', 'packeta' ), $i )
			);
		}
		$form->addSelect(
			'offset',
			__( 'Skip fields', 'packeta' ),
			$availableOffsets
		)->checkDefaultValue( false );

		return $form;
	}

	/**
	 * Registers submenu item.
	 */
	public function register(): void {
		add_submenu_page(
			\Packetery\Module\Options\Page::SLUG,
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

	/**
	 * Prepares labels.
	 *
	 * @param int   $offset Offset value.
	 * @param array $packetIds Packet ids.
	 *
	 * @return Response\PacketsLabelsPdf|null
	 */
	private function requestPacketaLabels( int $offset, array $packetIds ): ?Response\PacketsLabelsPdf {
		$request  = new Request\PacketsLabelsPdf( array_values( $packetIds ), $this->getLabelFormat(), $offset );
		$response = $this->soapApiClient->packetsLabelsPdf( $request );
		// TODO: is possible to merge following part of requestPacketaLabels and requestCarrierLabels?

		foreach ( $packetIds as $orderId => $packetId ) {
			$record          = new Log\Record();
			$record->action  = Log\Record::ACTION_LABEL_PRINT;
			$record->orderId = $orderId;
			$order           = $this->orderRepository->getById( $orderId, true );

			if ( ! $response->hasFault() ) {
				if ( null !== $order ) {
					$order->setIsLabelPrinted( true );
				}

				$record->status = Log\Record::STATUS_SUCCESS;
				$record->title  = __( 'Label has been printed successfully.', 'packeta' );
			} else {
				$record->status = Log\Record::STATUS_ERROR;
				$record->title  = __( 'Label could not be printed.', 'packeta' );
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

			if ( null !== $order ) {
				$order->updateApiErrorMessage( $response->getFaultString() );
				$this->orderRepository->save( $order );
			}
		}

		return $response;
	}

	/**
	 * Prepares carrier labels.
	 *
	 * @param int   $offset Offset value.
	 * @param array $packetIds Packet ids.
	 *
	 * @return Response\PacketsCourierLabelsPdf
	 */
	private function requestCarrierLabels( int $offset, array $packetIds ): Response\PacketsCourierLabelsPdf {
		$packetIdsWithCourierNumbers = $this->getPacketIdsWithCourierNumbers( $packetIds );
		$request                     = new Request\PacketsCourierLabelsPdf( array_values( $packetIdsWithCourierNumbers ), $this->getLabelFormat(), $offset );
		$response                    = $this->soapApiClient->packetsCarrierLabelsPdf( $request );

		foreach ( $packetIdsWithCourierNumbers as $orderId => $pairItem ) {
			$record          = new Log\Record();
			$record->action  = Log\Record::ACTION_CARRIER_LABEL_PRINT;
			$record->orderId = $orderId;
			$order           = $this->orderRepository->getById( $orderId, true );

			if ( ! $response->hasFault() ) {
				if ( null !== $order ) {
					$order->setIsLabelPrinted( true );
					$order->setCarrierNumber( $pairItem['courierNumber'] );
				}

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

			if ( null !== $order ) {
				$order->updateApiErrorMessage( $response->getFaultString() );
				$this->orderRepository->save( $order );
			}
		}

		return $response;
	}

	/**
	 * Gets filename for label pdf.
	 *
	 * @return string
	 */
	private function getFilename(): string {
		return 'packeta_labels_' . strtolower( str_replace( ' ', '_', $this->getLabelFormat() ) ) . '.pdf';
	}

	/**
	 * Gets saved packet ids.
	 *
	 * @param array $orderIds Order IDs from transient.
	 * @param bool  $isCarrierLabels Are carrier labels requested?.
	 *
	 * @return string[]
	 */
	private function getPacketIdsFromTransient( array $orderIds, bool $isCarrierLabels ): array {
		$orders    = $this->orderRepository->getByIds( $orderIds );
		$packetIds = [];
		foreach ( $orders as $order ) {
			if ( null === $order->getPacketId() ) {
				continue;
			}
			if ( ! $isCarrierLabels || $order->isExternalCarrier() ) {
				$packetIds[ $order->getNumber() ] = $order->getPacketId();
			}
		}

		return $packetIds;
	}

	/**
	 * Gets order IDs transient.
	 *
	 * @return array|null
	 */
	private function getOrderIdsTransient(): ?array {
		$orderIds = get_transient( self::getOrderIdsTransientName() );
		if ( is_array( $orderIds ) ) {
			return $orderIds;
		}

		return null;
	}

	/**
	 * Gets carrier packet numbers from API.
	 *
	 * @param string[] $packetIds List of packet ids.
	 *
	 * @return array[]
	 */
	private function getPacketIdsWithCourierNumbers( array $packetIds ): array {
		$pairs = [];
		foreach ( $packetIds as $orderId => $packetId ) {
			$request  = new Request\PacketCourierNumber( $packetId );
			$response = $this->soapApiClient->packetCourierNumber( $request );
			if ( $response->hasFault() ) {
				if ( $response->hasWrongPassword() ) {
					$this->messageManager->flash_message( __( 'Please set a proper API password.', 'packeta' ), MessageManager::TYPE_ERROR );

					return [];
				}

				$record          = new Log\Record();
				$record->action  = Log\Record::ACTION_CARRIER_NUMBER_RETRIEVING;
				$record->status  = Log\Record::STATUS_ERROR;
				$record->title   = __( 'Carrier number could not be retrieved.', 'packeta' );
				$record->params  = [
					'packetId'     => $request->getPacketId(),
					'errorMessage' => $response->getFaultString(),
				];
				$record->orderId = $orderId;
				$this->logger->add( $record );
				$order = $this->orderRepository->getById( $orderId, true );
				if ( null !== $order ) {
					$order->updateApiErrorMessage( $response->getFaultString() );
					$this->orderRepository->save( $order );
				}
				continue;
			}
			$pairs[ $orderId ] = [
				'packetId'      => $packetId,
				'courierNumber' => $response->getNumber(),
			];
		}

		return $pairs;
	}

	/**
	 * Saves information about the creation of the label in the order note.
	 *
	 * @param int            $orderId  Order id.
	 * @param string         $packetId Packet.
	 * @param ILabelResponse $response Response.
	 *
	 * @return void
	 */
	private function addLabelCreationInfoToWcOrderNote( int $orderId, string $packetId, ILabelResponse $response ): void {
		$order   = $this->orderRepository->findById( $orderId );
		$wcOrder = $this->orderRepository->getWcOrderById( $orderId );
		if ( null === $wcOrder || null === $order ) {
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
			// Response is of type Response\PacketsCourierLabelsPdf.
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

}
