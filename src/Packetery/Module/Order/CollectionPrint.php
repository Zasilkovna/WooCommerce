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
use Packetery\Module\EntityFactory;
use Packetery\Module\MessageManager;
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
	 * LabelPrint constructor.
	 *
	 * @param Engine                $latteEngine     Latte Engine.
	 * @param Http\Request          $httpRequest     Http Request.
	 * @param Client                $soapApiClient   SOAP API Client.
	 * @param MessageManager        $messageManager  Message Manager.
	 * @param EntityFactory\Address $addressFactory  Address factory.
	 * @param Repository            $orderRepository Order repository.
	 */
	public function __construct(
		Engine $latteEngine,
		Http\Request $httpRequest,
		Client $soapApiClient,
		MessageManager $messageManager,
		EntityFactory\Address $addressFactory,
		Repository $orderRepository
	) {
		$this->latteEngine     = $latteEngine;
		$this->httpRequest     = $httpRequest;
		$this->soapApiClient   = $soapApiClient;
		$this->messageManager  = $messageManager;
		$this->addressFactory  = $addressFactory;
		$this->orderRepository = $orderRepository;
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
			$this->messageManager->flash_message( __( 'No orders were selected', PACKETERY_LANG_DOMAIN ), 'info' );
			if ( wp_safe_redirect( 'edit.php?post_type=shop_order' ) ) {
				exit;
			}
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
			$wpOrders[ $orderNumber ]  = wc_get_order( $orderNumber );
		}

		if ( ! $packetIds ) {
			delete_transient( self::getOrderIdsTransientName() );
			$this->messageManager->flash_message( __( 'Selected orders have no packet id', PACKETERY_LANG_DOMAIN ), 'info' );
			if ( wp_safe_redirect( 'edit.php?post_type=shop_order' ) ) {
				exit;
			}
		}

		$shipmentResult = $this->requestShipment( $packetIds );
		if ( $shipmentResult->hasFault() && $shipmentResult->getInvalidPacketIds() ) {
			$packetIds      = array_diff( $packetIds, $shipmentResult->getInvalidPacketIds() );
			$shipmentResult = $this->requestShipment( $packetIds );
		}

		if ( $shipmentResult->hasFault() ) {
			delete_transient( self::getOrderIdsTransientName() );
			$this->messageManager->flash_message( __( 'Unexpected error', PACKETERY_LANG_DOMAIN ), MessageManager::TYPE_ERROR );
			if ( wp_safe_redirect( 'edit.php?post_type=shop_order' ) ) {
				exit;
			}
		}

		$shipmentBarcodeResult = $this->requestBarcodePng( $shipmentResult->getBarcode() );
		delete_transient( self::getOrderIdsTransientName() );
		if ( $shipmentBarcodeResult->hasFault() ) {
			$this->messageManager->flash_message( __( 'Unexpected error', PACKETERY_LANG_DOMAIN ), MessageManager::TYPE_ERROR );
			if ( wp_safe_redirect( 'edit.php?post_type=shop_order' ) ) {
				exit;
			}
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
				'stylesheet'          => Plugin::buildAssetUrl( 'public/order-collection-print.css' ),
				'translations' => [
					'handoverPacketsHeading' => __( 'Handover packets', PACKETERY_LANG_DOMAIN ),
					'packetCount'            => __( 'Packet count', PACKETERY_LANG_DOMAIN ),
					'printedAt'              => __( 'Printed at', PACKETERY_LANG_DOMAIN ),
					'sender'                 => __( 'Sender', PACKETERY_LANG_DOMAIN ),
					'recipient'              => __( 'Recipient', PACKETERY_LANG_DOMAIN ),
					'orderNumber'            => __( 'Order number', PACKETERY_LANG_DOMAIN ),
					'barcode'                => __( 'Barcode', PACKETERY_LANG_DOMAIN ),
					'created'                => __( 'Created', PACKETERY_LANG_DOMAIN ),
					'nameAndSurname'         => __( 'Name and surname', PACKETERY_LANG_DOMAIN ),
					'cod'                    => __( 'C.O.D.', PACKETERY_LANG_DOMAIN ),
					'pickUpPointOrCarrier'   => __( 'Pick up point or carrier', PACKETERY_LANG_DOMAIN ),
					'end'                    => __( 'END', PACKETERY_LANG_DOMAIN ),
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
			__( 'Print AWB', 'packetery' ),
			__( 'Print AWB', 'packetery' ),
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
}
