<?php
/**
 * Class ApiExtender.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Module\Exception\InvalidCarrierException;
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
	 * @param WC_Data          $object   Object.
	 *
	 * @return object
	 */
	public function extendResponse( WP_REST_Response $response, WC_Data $object ): object {
		if ( ! $object instanceof WC_Order ) {
			return $response;
		}

		try {
			$order = $this->orderRepository->getByWcOrder( $object );
		} catch ( InvalidCarrierException $invalidCarrierException ) {
			return $response;
		}

		if ( null === $order ) {
			return $response;
		}

		$this->addMetaDataItem( $response, '_packetery_carrier_id', $order->getCarrier()->getId() );
		if ( null !== $order->getPickupPoint() && null !== $order->getPickupPoint()->getId() ) {
			$this->addMetaDataItem( $response, '_packetery_point_id', $order->getPickupPoint()->getId() );
		}
		if ( null !== $order->getCarrierNumber() ) {
			$this->addMetaDataItem( $response, '_packetery_carrier_number', $order->getCarrierNumber() );
		}
		if ( null !== $order->getPacketId() ) {
			$this->addMetaDataItem( $response, '_packetery_packet_id', $order->getPacketId() );
		}

		return $response;
	}

	/**
	 * Adds metadata item to response.
	 *
	 * @param WP_REST_Response $response Response.
	 * @param string           $key Key.
	 * @param string           $value Value.
	 *
	 * @return void
	 */
	private function addMetaDataItem( WP_REST_Response $response, string $key, string $value ): void {
		$response->data['meta_data'][] = [
			'key'   => $key,
			'value' => $value,
		];
	}
}
