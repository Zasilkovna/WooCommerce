<?php
/**
 * Class ApiExtender.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Entity\Order;
use Packetery\Module\Shipping\ShippingProvider;
use WC_Data;
use WC_Order;
use WP_REST_Response;

/**
 * Class ApiExtender.
 */
class ApiExtender {

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * Constructor.
	 *
	 * @param Repository $orderRepository Order repository.
	 */
	public function __construct( Repository $orderRepository ) {
		$this->orderRepository = $orderRepository;
	}

	/**
	 * Registers service.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'woocommerce_rest_prepare_shop_order_object', [ $this, 'extendResponse' ], 20, 2 );
	}

	/**
	 * Extends WooCommerce API response with plugin data.
	 *
	 * @param WP_REST_Response $response Response.
	 * @param WC_Data          $wcData   Object.
	 *
	 * @return object
	 */
	public function extendResponse( WP_REST_Response $response, WC_Data $wcData ): object {
		if ( ! $wcData instanceof WC_Order ) {
			return $response;
		}

		$responseData = $response->get_data();
		if ( ! isset( $responseData['shipping_lines'] ) ) {
			return $response;
		}

		$order = $this->orderRepository->getByWcOrderWithValidCarrier( $wcData );
		if ( $order === null ) {
			return $response;
		}

		foreach ( $responseData['shipping_lines'] as $key => $shippingLine ) {
			if ( ! ShippingProvider::isPacketaMethod( $shippingLine['method_id'] ) ) {
				continue;
			}
			$response->data['shipping_lines'][ $key ]['packeta'] = $this->getPacketaItemsToShippingLines( $order );
		}

		return $response;
	}

	/**
	 * Add Items to Shipping Lines.
	 *
	 * @param Order $order Packetery Order.
	 *
	 * @return array<string, string|null>
	 */
	private function getPacketaItemsToShippingLines( Order $order ): array {
		$items = [
			'carrier_id' => $order->getCarrier()->getId(),
			'point_id'   => null,
			'point_name' => null,
		];

		if ( $order->getPickupPoint() !== null ) {
			$items['point_id']   = $order->getPickupPoint()->getId();
			$items['point_name'] = $order->getPickupPoint()->getName();
		}

		$items['carrier_number'] = $order->getCarrierNumber();
		$items['packet_id']      = $order->getPacketId();

		return $items;
	}
}
