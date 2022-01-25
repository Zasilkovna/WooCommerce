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
	 * GridExtender constructor.
	 *
	 * @param Helper     $helper Helper.
	 * @param Repository $carrierRepository Carrier repository.
	 * @param Engine     $latteEngine Latte Engine.
	 * @param Request    $httpRequest Http Request.
	 */
	public function __construct(
		Helper $helper,
		Repository $carrierRepository,
		Engine $latteEngine,
		Request $httpRequest
	) {
		$this->helper            = $helper;
		$this->carrierRepository = $carrierRepository;
		$this->latteEngine       = $latteEngine;
		$this->httpRequest       = $httpRequest;
	}

	/**
	 * Adds custom filtering links to order grid.
	 *
	 * @param array $var Array of html links.
	 *
	 * @return array
	 */
	public function addFilterLinks( array $var ): array {
		$orders      = wc_get_orders(
			[
				'packetery_to_submit' => '1',
				'nopaging'            => true,
			]
		);
		$latteParams = [
			'link'       => add_query_arg(
				[
					'post_type'           => 'shop_order',
					'filter_action'       => 'packetery_filter_link',
					'packetery_to_submit' => '1',
					'packetery_to_print'  => false,
				],
				admin_url( 'edit.php' )
			),
			'title'      => __( 'packetaOrdersToSubmit', 'packetery' ),
			'orderCount' => count( $orders ),
			'active'     => ( $this->httpRequest->getQuery( 'packetery_to_submit' ) === '1' ),
		];
		$var[]       = $this->latteEngine->renderToString( PACKETERY_PLUGIN_DIR . '/template/order/filter-link.latte', $latteParams );

		$orders      = wc_get_orders(
			[
				'packetery_to_print' => '1',
				'nopaging'           => true,
			]
		);
		$latteParams = [
			'link'       => add_query_arg(
				[
					'post_type'           => 'shop_order',
					'filter_action'       => 'packetery_filter_link',
					'packetery_to_submit' => false,
					'packetery_to_print'  => '1',
				],
				admin_url( 'edit.php' )
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
		$linkFilters = [];

		if ( null !== $this->httpRequest->getQuery( 'packetery_to_submit' ) ) {
			$linkFilters['packetery_to_submit'] = '1';
		}

		if ( null !== $this->httpRequest->getQuery( 'packetery_to_print' ) ) {
			$linkFilters['packetery_to_print'] = '1';
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/type-select.latte',
			[
				'packeteryOrderType' => $this->httpRequest->getQuery( 'packetery_order_type' ),
				'linkFilters'        => $linkFilters,
			]
		);
	}

	/**
	 * Adds query vars to order grid search query.
	 *
	 * @param \WP_Query $query WP uery.
	 *
	 * @return void
	 */
	public function addQueryVarsToSearchQuery( \WP_Query $query ) {
		global $pagenow;
		$get = $this->httpRequest->getQuery();
		$filterAction = ( $get['filter_action'] ?? null );
		$queryPostType = ( $query->query['post_type'] ?? null );

		if (
			$query->is_admin &&
			'shop_order' === $queryPostType &&
			null !== $filterAction &&
			'edit.php' === $pagenow &&
			'shop_order' === $get['post_type'] &&
			!isset( $query->query['packetery_to_submit'] ) &&
			!isset( $query->query['packetery_to_print'] )
		) {
			$queryVars = $query->get( 'meta_query', [] );
			$queryVars = $this->addQueryVars( $queryVars, $get );
			$query->set( 'meta_query', $queryVars );
		}
	}

	/**
	 * Transforms custom query var. There are two custom variables: "packetery_to_submit" and "packetery_to_print".
	 *
	 * @param array $queryVars Query vars.
	 * @param array $get       Input values.
	 *
	 * @return array
	 */
	public function handleCustomQueryVar( array $queryVars, array $get ): array {
		$metaQuery = $this->addQueryVars( ( $queryVars['meta_query'] ?? [] ), $get );
		if ( $metaQuery ) {
			// @codingStandardsIgnoreStart
			$queryVars['meta_query'] = $metaQuery;
			// @codingStandardsIgnoreEnd
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
		if ( ! empty( $get[ Entity::META_CARRIER_ID ] ) ) {
			$queryVars[] = [
				[
					'key'     => Entity::META_CARRIER_ID,
					'value'   => $get[ Entity::META_CARRIER_ID ],
					'compare' => '=',
				],
			];
		}

		if ( ! empty( $get['packetery_to_submit'] ) ) {
			$queryVars[] = [
				'key'     => Entity::META_CARRIER_ID,
				'value'   => '',
				'compare' => '!=',
			];

			$queryVars[] = [
				'key'     => Entity::META_IS_EXPORTED,
				'compare' => 'NOT EXISTS',
			];
		}

		if ( ! empty( $get['packetery_to_print'] ) ) {
			$queryVars[] = [
				'key'     => Entity::META_PACKET_ID,
				'compare' => 'EXISTS',
			];

			$queryVars[] = [
				'key'     => Entity::META_IS_LABEL_PRINTED,
				'compare' => 'NOT EXISTS',
			];
		}

		if ( ! empty( $get['packetery_order_type'] ) ) {
			$queryVars[] = [
				'key'     => Entity::META_CARRIER_ID,
				'value'   => Repository::INTERNAL_PICKUP_POINTS_ID,
				'compare' => ( Repository::INTERNAL_PICKUP_POINTS_ID === $get['packetery_order_type'] ? '=' : '!=' ),
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
			}
		}

		return $new_columns;
	}
}
