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
use PacketeryNette\Http;

/**
 * Class LabelPrint.
 *
 * @package Packetery\Order
 */
class CollectionPrint {
	const ACTION_PRINT_ORDER_COLLECTION = 'packetery_print_order_collection';
	const PAGE_SLUG = 'packeta-order-collection-print';

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
	public function print(): void {
		if ( $this->httpRequest->getQuery( 'page' ) !== self::PAGE_SLUG ) {
			return;
		}

		if ( ! get_transient( self::getOrderIdsTransientName() ) ) {
			$this->messageManager->flash_message( __( 'noOrdersSelected', 'packetery' ), 'info' );
			if ( wp_safe_redirect( 'edit.php?post_type=shop_order' ) ) {
				exit;
			}
		}

		$orderIds  = get_transient( self::getOrderIdsTransientName() );
		$orders    = $this->orderRepository->getOrdersByIds( $orderIds );
		$packetIds = [];
		foreach ( $orders as $order ) {
			if ( null === $order->getPacketId() ) {
				continue;
			}
			$packetIds[ $order->getNumber() ] = $order->getPacketId();
		}

		if ( ! $packetIds ) {
			return;
		}

		$shipmentResult = $this->requestShipment( $packetIds );
		$shipmentBarcodeResult = $this->requestBarcodePng( $shipmentResult->getBarcode() );
		delete_transient( self::getOrderIdsTransientName() );
		if ( $shipmentResult->hasFault() ) {
			$this->messageManager->flash_message( __( 'unexpectedErrorPleaseTryAgain', 'packetery' ), MessageManager::TYPE_ERROR );
			if ( wp_safe_redirect( 'edit.php?post_type=shop_order' ) ) {
				exit;
			}
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/collection-print.latte',
			[
//				'shipmentBarcode' => \PacketeryNette\Utils\Image::fromFile(PACKETERY_PLUGIN_DIR . '/public/packeta-symbol.png'),
//				'orders'          => $orders,
				'shipmentBarcode' => $shipmentBarcodeResult->getImage(),
				'orders'          => $orders,
			]
		);
		exit;
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
			self::PAGE_SLUG,
			[
				$this,
				'print',
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
	 * Request barcode image.
	 *
	 * @param string $barcode Barcode.
	 *
	 * @return Response\BarcodePng
	 */
	private function requestBarcodePng( string $barcode ): Response\BarcodePng {
		$request  = new Request\BarcodePng( $barcode );
		return $this->soapApiClient->barcodePng( $request );
	}
}
