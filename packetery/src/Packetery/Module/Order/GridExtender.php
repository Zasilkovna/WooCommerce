<?php
/**
 * Class GridExtender.
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Helper;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\Repository;
use Packetery\Module\Plugin;
use PacketeryLatte\Engine;
use PacketeryNette\Http\Request;

/**
 * Class GridExtender.
 *
 * @package Packetery\Order
 */
class GridExtender {
	/**
	 * Generic Helper.
	 *
	 * @var Helper
	 */
	private $helper;

	/**
	 * Carrier repository.
	 *
	 * @var Repository
	 */
	private $carrierRepository;

	/**
	 * Latte Engine.
	 *
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * Http Request.
	 *
	 * @var Request
	 */
	private $httpRequest;

	/**
	 * @var Controller
	 */
	private $orderController;

	/**
	 * GridExtender constructor.
	 *
	 * @param Helper     $helper            Helper.
	 * @param Repository $carrierRepository Carrier repository.
	 * @param Engine     $latteEngine       Latte Engine.
	 * @param Request    $httpRequest       Http Request.
	 * @param Controller $orderController
	 */
	public function __construct(
		Helper $helper,
		Repository $carrierRepository,
		Engine $latteEngine,
		Request $httpRequest, Controller $orderController
	) {
		$this->helper            = $helper;
		$this->carrierRepository = $carrierRepository;
		$this->latteEngine       = $latteEngine;
		$this->httpRequest       = $httpRequest;
		$this->orderController   = $orderController;
	}

	/**
	 * Adds custom filtering links to order grid.
	 *
	 * @param array $var Array of html links.
	 *
	 * @return array
	 */
	public function addFilterLinks( array $var ): array {
		$orders      = wc_get_orders( [ 'packetery_to_submit' => '1' ] );
		$latteParams = [
			'link'       => add_query_arg(
				[
					'post_status'         => false,
					'packetery_to_submit' => '1',
					'packetery_to_print'  => false,
				]
			),
			'title'      => __( 'packetaOrdersToSubmit', 'packetery' ),
			'orderCount' => count( $orders ),
			'active'     => ( $this->httpRequest->getQuery( 'packetery_to_submit' ) === '1' ),
		];
		$var[]       = $this->latteEngine->renderToString( PACKETERY_PLUGIN_DIR . '/template/order/filter-link.latte', $latteParams );

		$orders      = wc_get_orders( [ 'packetery_to_print' => '1' ] );
		$latteParams = [
			'link'       => add_query_arg(
				[
					'post_status'         => false,
					'packetery_to_submit' => false,
					'packetery_to_print'  => '1',
				]
			),
			'title'      => __( 'packetaOrdersToPrint', 'packetery' ),
			'orderCount' => count( $orders ),
			'active'     => ( $this->httpRequest->getQuery( 'packetery_to_print' ) === '1' ),
		];
		$var[]       = $this->latteEngine->renderToString( PACKETERY_PLUGIN_DIR . '/template/order/filter-link.latte', $latteParams );

		return $var;
	}

	/**
	 * Adds select to order grid.
	 */
	public function renderOrderTypeSelect(): void {
		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/type-select.latte',
			[
				'packeteryOrderType' => $this->httpRequest->getQuery( 'packetery_order_type' ),
			]
		);
	}

	/**
	 * Adds query vars to order list request.
	 *
	 * @param array $queryVars Query vars.
	 *
	 * @return array
	 */
	public function addQueryVarsToRequest( array $queryVars ): array {
		global $typenow;

		if ( in_array( $typenow, wc_get_order_types( 'order-meta-boxes' ), true ) ) {
			$queryVars = $this->addQueryVars( $queryVars, $this->httpRequest->getQuery() );
		}

		return $queryVars;
	}

	/**
	 * Adds query vars to fetch order list.
	 *
	 * @param array $queryVars Query vars.
	 * @param array $get Get parameters.
	 *
	 * @return array
	 */
	public function addQueryVars( array $queryVars, array $get ): array {
		if ( ! empty( $get['packetery_to_submit'] ) ) {
			$queryVars['meta_query'] = [
				'relation' => 'AND',
				[
					'key'     => Entity::META_CARRIER_ID,
					'value'   => '',
					'compare' => '!=',
				],
				[
					'key'     => Entity::META_IS_EXPORTED,
					'compare' => 'NOT EXISTS',
				],
			];
		}

		if ( ! empty( $get['packetery_to_print'] ) ) {
			$queryVars['meta_query'] = [
				'relation' => 'AND',
				[
					'key'     => Entity::META_PACKET_ID,
					'compare' => 'EXISTS',
				],
				[
					'key'     => Entity::META_IS_LABEL_PRINTED,
					'compare' => 'NOT EXISTS',
				],
			];
		}

		if ( ! empty( $get['packetery_order_type'] ) ) {
			$queryVars['meta_query'] = [
				[
					'key'     => Entity::META_CARRIER_ID,
					'value'   => Repository::INTERNAL_PICKUP_POINTS_ID,
					'compare' => ( Repository::INTERNAL_PICKUP_POINTS_ID === $get['packetery_order_type'] ? '=' : '!=' ),
				],
			];
		}

		return $queryVars;
	}

	/**
	 * Fills custom order list columns.
	 *
	 * @param string $column Current order column name.
	 */
	public function fillCustomOrderListColumns( string $column ): void {
		global $post;
		$order  = wc_get_order( $post->ID );
		$entity = new Entity( $order );

		switch ( $column ) {
			case 'packetery_destination':
				$pointName = $entity->getPointName();
				$pointId   = $entity->getPointId();

				$country = strtolower( $order->get_shipping_country() );

				if ( $entity->isHomeDelivery() ) {
					$homeDeliveryCarrier = $this->carrierRepository->getById( (int) $entity->getCarrierId() );
					if ( $homeDeliveryCarrier ) {
						$homeDeliveryCarrierEntity = new Carrier\Entity( $homeDeliveryCarrier );
						echo esc_html( $homeDeliveryCarrierEntity->getFinalName() );
					}
					break;
				}

				$internalCountries = array_keys( $this->carrierRepository->getZpointCarriers() );
				if ( $pointName && $pointId && in_array( $country, $internalCountries, true ) ) {
					echo esc_html( "$pointName ($pointId)" );
				} elseif ( $pointName ) {
					echo esc_html( $pointName );
				}
				break;
			case Entity::META_PACKET_ID:
				$packetId = $entity->getPacketId();
				if ( $packetId ) {
					echo '<a href="' . esc_attr( $this->helper->get_tracking_url( $packetId ) ) . '" target="_blank">Z' . esc_html( $packetId ) . '</a>';
				}
				break;
			case 'packetery':
				$nonce        = Plugin::getApiNonce();
				$orderSaveUrl = $this->orderController->getRoute( '/save' );
				$this->latteEngine->render( PACKETERY_PLUGIN_DIR . '/template/order/grid-column-packetery.latte', [
					'order'        => $entity,
					'nonce'        => $nonce,
					'orderSaveUrl' => $orderSaveUrl,
				] );
				break;
		}
	}

	/**
	 * Add order list columns.
	 *
	 * @param string[] $columns Order list columns.
	 *
	 * @return string[] All columns.
	 */
	public function addOrderListColumns( array $columns ): array {
		$new_columns = array();

		foreach ( $columns as $column_name => $column_info ) {
			$new_columns[ $column_name ] = $column_info;

			if ( 'order_total' === $column_name ) {
				$new_columns[ Entity::META_PACKET_ID ] = __( 'Barcode', 'packetery' );
				$new_columns['packetery_destination']  = __( 'Pick up point or carrier', 'packetery' );
				$new_columns['packetery']  = __( 'Packeta', 'packetery' );
			}
		}

		return $new_columns;
	}
}
