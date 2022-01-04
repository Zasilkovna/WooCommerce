<?php
/**
 * Class LabelPrint.
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api\Soap\Client;
use Packetery\Core\Api\Soap\Request;
use Packetery\Core\Api\Soap\Response;
use Packetery\Core\Log;
use Packetery\Module\FormFactory;
use Packetery\Module\MessageManager;
use Packetery\Module\Options\Provider;
use Packetery\Module\Plugin;
use PacketeryLatte\Engine;
use PacketeryNette\Forms\Form;
use PacketeryNette\Http;

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
	 * @var Provider
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
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * Logger.
	 *
	 * @var Log\ILogger
	 */
	private $logger;

	/**
	 * LabelPrint constructor.
	 *
	 * @param Engine         $latteEngine     Latte Engine.
	 * @param Provider       $optionsProvider Options provider.
	 * @param FormFactory    $formFactory     Form factory.
	 * @param Http\Request   $httpRequest     Http Request.
	 * @param Client         $soapApiClient   SOAP API Client.
	 * @param MessageManager $messageManager  Message Manager.
	 * @param Repository     $orderRepository Order repository.
	 * @param Log\ILogger    $logger          Logger.
	 */
	public function __construct(
		Engine $latteEngine,
		Provider $optionsProvider,
		FormFactory $formFactory,
		Http\Request $httpRequest,
		Client $soapApiClient,
		MessageManager $messageManager,
		Repository $orderRepository,
		Log\ILogger $logger
	) {
		$this->latteEngine     = $latteEngine;
		$this->optionsProvider = $optionsProvider;
		$this->formFactory     = $formFactory;
		$this->httpRequest     = $httpRequest;
		$this->soapApiClient   = $soapApiClient;
		$this->messageManager  = $messageManager;
		$this->orderRepository = $orderRepository;
		$this->logger          = $logger;
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
	 * Prepares form and renders template.
	 */
	public function render(): void {
		$form = $this->createForm( $this->optionsProvider->getLabelMaxOffset( $this->getLabelFormat() ) );

		$count    = 0;
		$orderIds = get_transient( self::getOrderIdsTransientName() );
		if ( $orderIds ) {
			$count = count( $orderIds );
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/label-print.latte',
			[
				'form'          => $form,
				'count'         => $count,
				'backLink'      => get_transient( self::getBackLinkTransientName() ),
				'flashMessages' => $this->messageManager->renderToString( MessageManager::RENDERER_PACKETERY, self::MENU_SLUG ),
			]
		);
	}

	/**
	 * Gets label format for current job.
	 *
	 * @return string
	 */
	private function getLabelFormat(): string {
		$packetaLabelFormat = ( $this->optionsProvider->get_packeta_label_format() ?? '' );
		$carrierLabelFormat = ( $this->optionsProvider->get_carrier_label_format() ?? '' );

		return ( $this->httpRequest->getQuery( self::LABEL_TYPE_PARAM ) === self::ACTION_CARRIER_LABELS ? $carrierLabelFormat : $packetaLabelFormat );
	}

	/**
	 * Outputs pdf.
	 */
	public function outputLabelsPdf(): void {
		if ( $this->httpRequest->getQuery( 'page' ) !== self::MENU_SLUG ) {
			return;
		}

		$isCarrierLabels = ( $this->httpRequest->getQuery( self::LABEL_TYPE_PARAM ) === self::ACTION_CARRIER_LABELS );
		$idParam         = $this->httpRequest->getQuery( 'id' );
		$packetIdParam   = $this->httpRequest->getQuery( 'packet_id' );
		if ( null !== $idParam && null !== $packetIdParam ) {
			$packetIds = [ $idParam => $packetIdParam ];
		} else {
			if ( ! get_transient( self::getOrderIdsTransientName() ) ) {
				$this->messageManager->flash_message( __( 'noOrdersSelected', 'packetery' ), MessageManager::TYPE_INFO, MessageManager::RENDERER_PACKETERY, self::MENU_SLUG );

				return;
			}

			$packetIds = $this->getPacketIdsFromTransient( $isCarrierLabels );
		}
		if ( ! $packetIds ) {
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
		} else {
			$response = $this->requestPacketaLabels( $offset, $packetIds );
		}
		if ( ! $response || $response->hasFault() ) {
			$message = ( null !== $response && $response->hasFault() ) ?
				__( 'labelPrintFailedMoreInfoInLog', 'packetery' ) :
				__( 'youSelectedOrdersThatWereNotSubmitted', 'packetery' );
			$this->messageManager->flash_message( $message, MessageManager::TYPE_ERROR );
			if ( wp_safe_redirect( 'edit.php?post_type=shop_order' ) ) {
				exit;
			}
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
	 * @param int $maxOffset Maximal offset.
	 *
	 * @return Form
	 */
	public function createForm( int $maxOffset ): Form {
		$form = $this->formFactory->create();

		$availableOffsets = [];
		for ( $i = 0; $i <= $maxOffset; $i ++ ) {
			// translators: %s is offset.
			$availableOffsets[ $i ] = ( 0 === $i ? __( 'dontSkipAnyField', 'packetery' ) : sprintf( __( 'skip%sFields', 'packetery' ), $i ) );
		}
		$form->addSelect(
			'offset',
			__( 'labelsOffset', 'packetery' ),
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
			__( 'printLabels', 'packetery' ),
			__( 'printLabels', 'packetery' ),
			'manage_options',
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
		if ( ! $response->hasFault() ) {
			foreach ( array_keys( $packetIds ) as $orderId ) {
				update_post_meta( $orderId, Entity::META_IS_LABEL_PRINTED, true );
			}

			$record         = new Log\Record();
			$record->action = Log\Record::ACTION_LABEL_PRINT;
			$record->status = Log\Record::STATUS_SUCCESS;
			$record->title  = 'Akce “Tisk štítků” proběhla úspěšně.'; // todo translate.
			$this->logger->add( $record );
		} else {
			$record         = new Log\Record();
			$record->action = Log\Record::ACTION_LABEL_PRINT;
			$record->status = Log\Record::STATUS_ERROR;
			$record->title  = 'Akce “Tisk štítků” skončila chybou.'; // todo translate.
			$record->params = [
				'request'      => [
					'packetIds' => implode( ',', $request->getPacketIds() ),
					'format'    => $request->getFormat(),
					'offset'    => $request->getOffset(),
				],
				'errorMessage' => $response->getFaultString(),
			];
			$this->logger->add( $record );
		}

		return $response;
	}

	/**
	 * Prepares carrier labels.
	 *
	 * @param int   $offset Offset value.
	 * @param array $packetIds Packet ids.
	 *
	 * @return Response\PacketsCourierLabelsPdf|null
	 */
	private function requestCarrierLabels( int $offset, array $packetIds ): ?Response\PacketsCourierLabelsPdf {
		$packetIdsWithCourierNumbers = $this->getPacketIdsWithCourierNumbers( $packetIds );
		$request                     = new Request\PacketsCourierLabelsPdf( array_values( $packetIdsWithCourierNumbers ), $this->getLabelFormat(), $offset );
		$response                    = $this->soapApiClient->packetsCarrierLabelsPdf( $request );
		if ( ! $response->hasFault() ) {
			foreach ( array_keys( $packetIdsWithCourierNumbers ) as $orderId ) {
				update_post_meta( $orderId, Entity::META_IS_LABEL_PRINTED, true );
				update_post_meta( $orderId, Entity::META_CARRIER_NUMBER, $packetIdsWithCourierNumbers[ $orderId ]['courierNumber'] );
			}

			$record         = new Log\Record();
			$record->action = Log\Record::ACTION_CARRIER_LABEL_PRINT;
			$record->status = Log\Record::STATUS_SUCCESS;
			$record->title  = 'Akce “Tisk štítků externích dopravců” proběhla úspěšně.'; // todo translate.
			$this->logger->add( $record );
		} else {
			$record         = new Log\Record();
			$record->action = Log\Record::ACTION_CARRIER_LABEL_PRINT;
			$record->status = Log\Record::STATUS_ERROR;
			$record->title  = 'Akce “Tisk štítků externích dopravců” skončila chybou.'; // todo translate.
			$record->params = [
				'request'      => [
					'packetIdsWithCourierNumbers' => $request->getPacketIdsWithCourierNumbers(),
					'format'                      => $request->getFormat(),
					'offset'                      => $request->getOffset(),
				],
				'errorMessage' => $response->getFaultString(),
			];
			$this->logger->add( $record );
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
	 * @param bool $isCarrierLabels Are carrier labels requested?.
	 *
	 * @return string[]
	 */
	private function getPacketIdsFromTransient( bool $isCarrierLabels ): array {
		$orderIds  = get_transient( self::getOrderIdsTransientName() );
		$orders    = $this->orderRepository->getOrdersByIds( $orderIds );
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
					$this->messageManager->flash_message( __( 'pleaseSetProperPassword', 'packetery' ), MessageManager::TYPE_ERROR );

					return [];
				}

				$record         = new Log\Record();
				$record->action = Log\Record::ACTION_CARRIER_NUMBER_RETRIEVING;
				$record->status = Log\Record::STATUS_ERROR;
				$record->title  = 'Akce “Získání trasovacího čísla externího dopravce” byla neúspěšná.';
				$record->params = [
					'packetId'     => $request->getPacketId(),
					'errorMessage' => $response->getFaultString(),
				];
				$this->logger->add( $record );
				continue;
			}
			$pairs[ $orderId ] = [
				'packetId'      => $packetId,
				'courierNumber' => $response->getNumber(),
			];
		}

		return $pairs;
	}

}
