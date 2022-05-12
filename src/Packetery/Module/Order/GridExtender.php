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
	 * @var Carrier\Repository
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
	 * Controller router.
	 *
	 * @var ControllerRouter
	 */
	private $orderControllerRouter;

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * GridExtender constructor.
	 *
	 * @param Helper             $helper                Helper.
	 * @param Carrier\Repository $carrierRepository     Carrier repository.
	 * @param Engine             $latteEngine           Latte Engine.
	 * @param Request            $httpRequest           Http Request.
	 * @param ControllerRouter   $orderControllerRouter Order controller router.
	 * @param Repository         $orderRepository       Order repository.
	 */
	public function __construct(
		Helper $helper,
		Carrier\Repository $carrierRepository,
		Engine $latteEngine,
		Request $httpRequest,
		ControllerRouter $orderControllerRouter,
		Repository $orderRepository
	) {
		$this->helper                = $helper;
		$this->carrierRepository     = $carrierRepository;
		$this->latteEngine           = $latteEngine;
		$this->httpRequest           = $httpRequest;
		$this->orderControllerRouter = $orderControllerRouter;
		$this->orderRepository       = $orderRepository;
	}

	/**
	 * Adds custom filtering links to order grid.
	 *
	 * @param array $var Array of html links.
	 *
	 * @return array
	 */
	public function addFilterLinks( array $var ): array {
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
			'title'      => __( 'Packeta orders to submit', PACKETERY_LANG_DOMAIN ),
			'orderCount' => $this->orderRepository->countOrdersToSubmit(),
			'active'     => ( $this->httpRequest->getQuery( 'packetery_to_submit' ) === '1' ),
		];
		$var[]       = $this->latteEngine->renderToString( PACKETERY_PLUGIN_DIR . '/template/order/filter-link.latte', $latteParams );

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
			'title'      => __( 'Packeta orders to print', PACKETERY_LANG_DOMAIN ),
			'orderCount' => $this->orderRepository->countOrdersToPrint(),
			'active'     => ( $this->httpRequest->getQuery( 'packetery_to_print' ) === '1' ),
		];
		$var[]       = $this->latteEngine->renderToString( PACKETERY_PLUGIN_DIR . '/template/order/filter-link.latte', $latteParams );

		return $var;
	}

	/**
	 * Adds select to order grid.
	 */
	public function renderOrderTypeSelect(): void {
		if ( ! $this->isOrderGridPage() ) {
			return;
		}

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
	 * Fills custom order list columns.
	 *
	 * @param string $column Current order column name.
	 */
	public function fillCustomOrderListColumns( string $column ): void {
		global $post;

		$order = $this->orderRepository->getById( $post->ID );
		if ( null === $order ) {
			return;
		}

		switch ( $column ) {
			case 'packetery_destination':
				$pickupPoint = $order->getPickupPoint();
				if ( null !== $pickupPoint ) {
					$pointName         = $pickupPoint->getName();
					$pointId           = $pickupPoint->getId();
					$country           = $order->getShippingCountry();
					$internalCountries = array_keys( $this->carrierRepository->getZpointCarriers() );
					if ( in_array( $country, $internalCountries, true ) ) {
						echo esc_html( "$pointName ($pointId)" );
					} else {
						echo esc_html( $pointName );
					}
					break;
				}
				$homeDeliveryCarrier = $this->carrierRepository->getById( (int) $order->getCarrierId() );
				if ( $homeDeliveryCarrier ) {
					$homeDeliveryCarrierEntity = new Carrier\Entity( $homeDeliveryCarrier );
					echo esc_html( $homeDeliveryCarrierEntity->getFinalName() );
				}
				break;
			case 'packetery_packet_id':
				$packetId = $order->getPacketId();
				if ( $packetId ) {
					echo '<a href="' . esc_attr( $this->helper->get_tracking_url( $packetId ) ) . '" target="_blank">Z' . esc_html( $packetId ) . '</a>';
				}
				break;
			case 'packetery':
				$packetSubmitUrl = add_query_arg( [], $this->orderControllerRouter->getRouteUrl( Controller::PATH_SUBMIT_TO_API ) );
				$printLink       = add_query_arg(
					[
						'page'                       => LabelPrint::MENU_SLUG,
						LabelPrint::LABEL_TYPE_PARAM => ( $order->isExternalCarrier() ? LabelPrint::ACTION_CARRIER_LABELS : LabelPrint::ACTION_PACKETA_LABELS ),
						'id'                         => $order->getNumber(),
						'packet_id'                  => $order->getPacketId(),
						'offset'                     => 0,
					],
					admin_url( 'admin.php' )
				);
				$this->latteEngine->render(
					PACKETERY_PLUGIN_DIR . '/template/order/grid-column-packetery.latte',
					[
						'order'           => $order,
						'hasOrderWeight'  => ( null !== $order->getWeight() && $order->getWeight() > 0 ),
						'packetSubmitUrl' => $packetSubmitUrl,
						'restNonce'       => wp_create_nonce( 'wp_rest' ),
						'printLink'       => $printLink,
					]
				);
				break;
				// TODO: Packet status sync.
		}
	}

	/**
	 * Gets code text translated.
	 *
	 * @param string|null $packetStatus Packet status.
	 *
	 * @return string|null
	 */
	public function getPacketStatusTranslated( ?string $packetStatus ): string {
		switch ( $packetStatus ) {
			case 'received data':
				return __( 'Data received', PACKETERY_LANG_DOMAIN );
			case 'arrived':
				return __( 'Arrived', PACKETERY_LANG_DOMAIN );
			case 'prepared for departure':
				return __( 'Prepared for departure', PACKETERY_LANG_DOMAIN );
			case 'departed':
				return __( 'Departed', PACKETERY_LANG_DOMAIN );
			case 'ready for pickup':
				return __( 'Ready for pickup', PACKETERY_LANG_DOMAIN );
			case 'handed to carrier':
				return __( 'Handed to carrier', PACKETERY_LANG_DOMAIN );
			case 'delivered':
				return __( 'Delivered', PACKETERY_LANG_DOMAIN );
			case 'posted back':
				return __( 'Posted back', PACKETERY_LANG_DOMAIN );
			case 'returned':
				return __( 'Returned', PACKETERY_LANG_DOMAIN );
			case 'cancelled':
				return __( 'Cancelled', PACKETERY_LANG_DOMAIN );
			case 'collected':
				return __( 'Collected', PACKETERY_LANG_DOMAIN );
			case 'unknown':
				return __( 'Unknown', PACKETERY_LANG_DOMAIN );
		}

		return (string) $packetStatus;
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
				// TODO: Packet status sync.
				$new_columns['packetery']             = __( 'Packeta', PACKETERY_LANG_DOMAIN );
				$new_columns['packetery_packet_id']   = __( 'Barcode', PACKETERY_LANG_DOMAIN );
				$new_columns['packetery_destination'] = __( 'Pick up point or carrier', PACKETERY_LANG_DOMAIN );
			}
		}

		return $new_columns;
	}

	/**
	 * Checks if current admin page is order grid using WP globals.
	 *
	 * @return bool
	 */
	public function isOrderGridPage(): bool {
		global $pagenow, $typenow;

		return ( 'edit.php' === $pagenow && 'shop_order' === $typenow );
	}
}
