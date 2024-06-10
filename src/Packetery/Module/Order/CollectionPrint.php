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
use Packetery\Core\Entity\Order;
use Packetery\Module\EntityFactory;
use Packetery\Module\MessageManager;
use Packetery\Module\Plugin;
use Packetery\Latte\Engine;
use Packetery\Nette\Http;

/**
 * Class LabelPrint.
 *
 * @package Packetery\Order
 */
class CollectionPrint {
	const ACTION_PRINT_ORDER_COLLECTION = 'packetery_print_order_collection';
	const PAGE_SLUG                     = 'packeta-order-collection-print';

	/**
	 * PacketeryLatte Engine.
	 *
	 * @var Engine PacketeryLatte engine.
	 */
	private $latteEngine;

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
	 * Address factory.
	 *
	 * @var \Packetery\Module\EntityFactory\Address
	 */
	private $addressFactory;

	/**
	 * Packet actions common logic.
	 *
	 * @var PacketActionsCommonLogic
	 */
	private $commonLogic;

	/**
	 * LabelPrint constructor.
	 *
	 * @param Engine                   $latteEngine              Latte Engine.
	 * @param Http\Request             $httpRequest              Http Request.
	 * @param Client                   $soapApiClient            SOAP API Client.
	 * @param MessageManager           $messageManager           Message Manager.
	 * @param EntityFactory\Address    $addressFactory           Address factory.
	 * @param Repository               $orderRepository          Order repository.
	 * @param PacketActionsCommonLogic $packetActionsCommonLogic Common logic.
	 */
	public function __construct(
		Engine $latteEngine,
		Http\Request $httpRequest,
		Client $soapApiClient,
		MessageManager $messageManager,
		EntityFactory\Address $addressFactory,
		Repository $orderRepository,
		PacketActionsCommonLogic $packetActionsCommonLogic
	) {
		$this->latteEngine     = $latteEngine;
		$this->httpRequest     = $httpRequest;
		$this->soapApiClient   = $soapApiClient;
		$this->messageManager  = $messageManager;
		$this->addressFactory  = $addressFactory;
		$this->orderRepository = $orderRepository;
		$this->commonLogic     = $packetActionsCommonLogic;
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
			$this->messageManager->flash_message( __( 'No orders were selected', 'packeta' ), 'info' );
			$this->commonLogic->redirectTo( PacketActionsCommonLogic::REDIRECT_TO_ORDER_GRID );
		}

		$orderIds  = get_transient( self::getOrderIdsTransientName() );
		$orders    = $this->orderRepository->getByIds( $orderIds );
		$packetIds = [];
		$wpOrders  = [];

		foreach ( $orders as $order ) {
			if ( null === $order->getPacketId() ) {
				continue;
			}

			$orderNumber               = $order->getNumber();
			$packetIds[ $orderNumber ] = $order->getPacketId();
			$wpOrders[ $orderNumber ]  = $this->orderRepository->getWcOrderById( (int) $orderNumber );
		}

		if ( ! $packetIds ) {
			delete_transient( self::getOrderIdsTransientName() );
			$this->messageManager->flash_message( __( 'Selected orders were not yet submitted to Packeta.', 'packeta' ), 'info' );
			$this->commonLogic->redirectTo( PacketActionsCommonLogic::REDIRECT_TO_ORDER_GRID );
		}

		$shipmentResult = $this->requestShipment( $packetIds );
		if ( $shipmentResult->hasFault() && $shipmentResult->getInvalidPacketIds() ) {
			$this->logApiErrorMessageFromCreateShipmentResponse( $shipmentResult, $orders );
			$packetIds      = array_diff( $packetIds, $shipmentResult->getInvalidPacketIds() );
			$shipmentResult = $this->requestShipment( $packetIds );
		}

		if ( $shipmentResult->hasFault() ) {
			delete_transient( self::getOrderIdsTransientName() );
			$this->messageManager->flash_message( __( 'Unexpected error', 'packeta' ), MessageManager::TYPE_ERROR );
			$this->commonLogic->redirectTo( PacketActionsCommonLogic::REDIRECT_TO_ORDER_GRID );
		}

		$shipmentBarcodeResult = $this->requestBarcodePng( $shipmentResult->getBarcode() );
		delete_transient( self::getOrderIdsTransientName() );
		if ( $shipmentBarcodeResult->hasFault() ) {
			$this->messageManager->flash_message( __( 'Unexpected error', 'packeta' ), MessageManager::TYPE_ERROR );
			$this->commonLogic->redirectTo( PacketActionsCommonLogic::REDIRECT_TO_ORDER_GRID );
		}

		$storeAddress = $this->addressFactory->fromWcStoreOptions();
		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/collection-print.latte',
			[
				'storeAddress'        => $storeAddress,
				'storeName'           => get_option( 'blogname', '' ),
				'shipmentBarcodeText' => $shipmentResult->getSimpleBarcodeText(),
				'shipmentBarcode'     => $shipmentBarcodeResult->getImageContent(),
				'orders'              => $orders,
				'packetIds'           => $packetIds,
				'wpOrders'            => $wpOrders,
				'orderCount'          => count( $packetIds ),
				'printedAt'           => ( new \DateTimeImmutable() )->setTimezone( wp_timezone() ),
				'stylesheet'          => Plugin::buildAssetUrl( 'public/css/order-collection-print.css' ),
				'translations'        => [
					'handoverPacketsHeading' => __( 'Handover packets', 'packeta' ),
					'packetCount'            => __( 'Packet count', 'packeta' ),
					'printedAt'              => __( 'Printed at', 'packeta' ),
					'sender'                 => __( 'Sender', 'packeta' ),
					'recipient'              => __( 'Recipient', 'packeta' ),
					'orderNumber'            => __( 'Order number', 'packeta' ),
					'barcode'                => __( 'Barcode', 'packeta' ),
					'created'                => __( 'Created', 'packeta' ),
					'nameAndSurname'         => __( 'Name and surname', 'packeta' ),
					'cod'                    => __( 'C.O.D.', 'packeta' ),
					'pickUpPointOrCarrier'   => __( 'Pickup point or carrier', 'packeta' ),
					'end'                    => __( 'END', 'packeta' ),
				],
			]
		);
		exit;
	}

	/**
	 * Registers submenu item.
	 */
	public function register(): void {
		add_submenu_page(
			\Packetery\Module\Options\Page::SLUG,
			__( 'Print AWB', 'packeta' ),
			__( 'Print AWB', 'packeta' ),
			'manage_woocommerce',
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
		Plugin::hideSubmenuItem( self::PAGE_SLUG );
	}

	/**
	 * Request shipment.
	 *
	 * @param array $packetIds Packet ids.
	 *
	 * @return Response\CreateShipment
	 */
	private function requestShipment( array $packetIds ): Response\CreateShipment {
		$request = new Request\CreateShipment( array_values( $packetIds ) );
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
		$request = new Request\BarcodePng( $barcode );
		return $this->soapApiClient->barcodePng( $request );
	}

	/**
	 * Saves API error message to order.
	 *
	 * @param Response\CreateShipment $createShipmentResponse CreateShipment response.
	 * @param Order[]                 $orders Indexed array of Order entities.
	 *
	 * @return void
	 */
	private function logApiErrorMessageFromCreateShipmentResponse( Response\CreateShipment $createShipmentResponse, array $orders ): void {
		$invalidPacketIds = $createShipmentResponse->getInvalidPacketIds();
		foreach ( $orders as $order ) {
			if ( in_array( $order->getPacketId(), $invalidPacketIds, true ) ) {
				$order->updateApiErrorMessage( $createShipmentResponse->getFaultString() );
				$this->orderRepository->save( $order );
			}
		}
	}
}
