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
class CollectionPrint {
	const ACTION_PRINT_ORDER_COLLECTION = 'print_order_collection';

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
		return 'packetery_order_collection_print_order_ids_' . wp_get_session_token();
	}

	/**
	 * Prepares form and renders template.
	 */
	public function render(): void {
		$form = $this->createForm( $this->optionsProvider->getLabelMaxOffset( $this->getLabelFormat() ) );
		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/collection-print.latte',
			[ 'form' => $form ]
		);
	}

	/**
	 * Outputs pdf.
	 */
	public function outputLabelsPdf(): void {
		if ( $this->httpRequest->getQuery( 'page' ) !== 'order-collection-print' ) {
			return;
		}
		if ( ! get_transient( self::getOrderIdsTransientName() ) ) {
			$this->messageManager->flash_message( __( 'noOrdersSelected', 'packetery' ), 'info' );

			return;
		}

		$packetIds       = $this->getPacketIdsFromTransient();
		if ( ! $packetIds ) {
			return;
		}

		$response = $this->requestShipment( $packetIds );
		delete_transient( self::getOrderIdsTransientName() );
		if ( $response->hasFault() ) {
			$this->messageManager->flash_message( __( 'youSelectedOrdersThatWereNotSubmitted', 'packetery' ), MessageManager::TYPE_ERROR );
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
		$form->setAction( $this->httpRequest->getUrl()->getRelativeUrl() );

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
			'packeta-options',
			__( 'orderCollectionPrintMenuSlugLabel', 'packetery' ),
			__( 'orderCollectionPrintMenuSlugLabel', 'packetery' ),
			'manage_options',
			'packeta-order-collection-print',
			[
				$this,
				'render',
			],
			20
		);
	}

	/**
	 * Hides submenu item.
	 */
	public function hideFromMenus(): void {
		Plugin::hideSubmenuItem( 'packeta-order-collection-print' );
	}

	/**
	 * Request shipment.
	 *
	 * @param array $packetIds Packet ids.
	 *
	 * @return Response\CreateShipment
	 */
	private function requestShipment( array $packetIds ): Response\CreateShipment {
		$request  = new Request\CreateShipment( array_values( $packetIds ) );
		return $this->soapApiClient->createShipment( $request );
	}

	/**
	 * Gets saved packet ids.
	 *
	 * @return string[]
	 */
	private function getPacketIdsFromTransient(): array {
		$orderIds  = get_transient( self::getOrderIdsTransientName() );
		$orders    = $this->orderRepository->getOrdersByIds( $orderIds );
		$packetIds = [];
		foreach ( $orders as $order ) {
			if ( null === $order->getPacketId() ) {
				continue;
			}
			$packetIds[ $order->getPostId() ] = $order->getPacketId();
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
					$this->messageManager->flash_message( __( 'pleaseSetProperPassword', 'packetery' ), 'error' );

					return [];
				}

				$record = new Log\Record();
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
