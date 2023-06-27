<?php
/**
 * Class GridExtender.
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core;
use Packetery\Module;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\ContextResolver;
use Packetery\Module\Exception\InvalidCarrierException;
use Packetery\Module\Log\Purger;
use Packetery\Latte\Engine;
use Packetery\Nette\Http\Request;
use Packetery\Module\Plugin;
use WC_Order;

/**
 * Class GridExtender.
 *
 * @package Packetery\Order
 */
class GridExtender {

	const TEMPLATE_GRID_COLUMN_WEIGHT = PACKETERY_PLUGIN_DIR . '/template/order/grid-column-weight.latte';

	/**
	 * Generic Helper.
	 *
	 * @var Core\Helper
	 */
	private $helper;

	/**
	 * Carrier repository.
	 *
	 * @var Carrier\EntityRepository
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
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * Order Validator.
	 *
	 * @var Core\Validator\Order
	 */
	private $orderValidator;

	/**
	 * Internal pickup points config.
	 *
	 * @var PacketaPickupPointsConfig
	 */
	private $pickupPointsConfig;

	/**
	 * Context resolver.
	 *
	 * @var ContextResolver
	 */
	private $contextResolver;

	/**
	 * GridExtender constructor.
	 *
	 * @param Core\Helper               $helper             Helper.
	 * @param Carrier\EntityRepository  $carrierRepository  Carrier repository.
	 * @param Engine                    $latteEngine        Latte Engine.
	 * @param Request                   $httpRequest        Http Request.
	 * @param Repository                $orderRepository    Order repository.
	 * @param Core\Validator\Order      $orderValidator     Order validator.
	 * @param PacketaPickupPointsConfig $pickupPointsConfig Internal pickup points config.
	 * @param ContextResolver           $contextResolver    Context resolver.
	 */
	public function __construct(
		Core\Helper $helper,
		Carrier\EntityRepository $carrierRepository,
		Engine $latteEngine,
		Request $httpRequest,
		Repository $orderRepository,
		Core\Validator\Order $orderValidator,
		PacketaPickupPointsConfig $pickupPointsConfig,
		ContextResolver $contextResolver
	) {
		$this->helper             = $helper;
		$this->carrierRepository  = $carrierRepository;
		$this->latteEngine        = $latteEngine;
		$this->httpRequest        = $httpRequest;
		$this->orderRepository    = $orderRepository;
		$this->orderValidator     = $orderValidator;
		$this->pickupPointsConfig = $pickupPointsConfig;
		$this->contextResolver    = $contextResolver;
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
			'title'      => __( 'Packeta orders to submit', 'packeta' ),
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
			'title'      => __( 'Packeta orders to print', 'packeta' ),
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
		if ( false === $this->contextResolver->isOrderGridPage() ) {
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
				'translations'       => [
					'packetaMethodType'         => __( 'Packeta shipping method', 'packeta' ),
					'carrierPackets'            => __( 'Carrier packets', 'packeta' ),
					'packetaPickupPointPackets' => __( 'Packeta pickup points packets', 'packeta' ),
				],
			]
		);
	}

	/**
	 * Gets weight cell content.
	 *
	 * @param Core\Entity\Order $order Order.
	 *
	 * @return string
	 */
	public function getWeightCellContent( Core\Entity\Order $order ): string {
		return $this->latteEngine->renderToString(
			self::TEMPLATE_GRID_COLUMN_WEIGHT,
			$this->getWeightCellContentParams( $order )
		);
	}

	/**
	 * Gets weight cell content.
	 *
	 * @param Core\Entity\Order $order Order.
	 *
	 * @return array
	 */
	private function getWeightCellContentParams( Core\Entity\Order $order ): array {
		return [
			'orderNumber' => $order->getNumber(),
			'weight'      => $order->getFinalWeight(),
		];
	}

	/**
	 * Get Order Entity from cache
	 *
	 * @param int $orderId Order ID.
	 *
	 * @return Core\Entity\Order|null
	 * @throws InvalidCarrierException InvalidCarrierException.
	 */
	private function getOrderByIdCached( int $orderId ): ?Core\Entity\Order {
		static $ordersCache;

		if ( ! isset( $ordersCache[ $orderId ] ) ) {
			$ordersCache[ $orderId ] = $this->orderRepository->getById( $orderId );
		}

		return $ordersCache[ $orderId ] ?? null;
	}

	/**
	 * Fills custom order list columns.
	 *
	 * @param string|mixed        $column Current order column name.
	 * @param \WC_Order|int|mixed $wcOrder WC Order.
	 */
	public function fillCustomOrderListColumns( $column, $wcOrder ): void {
		if ( false === is_string( $column ) ) {
			return;
		}

		if ( $wcOrder instanceof WC_Order ) {
			$orderId = $wcOrder->get_id();
		} elseif ( is_numeric( $wcOrder ) ) {
			$orderId = (int) $wcOrder;
		} else {
			return;
		}

		try {
			$order = $this->getOrderByIdCached( $orderId );
		} catch ( InvalidCarrierException $exception ) {
			if ( 'packetery' === $column ) {
				Module\Helper::renderString( $exception->getMessage() );
			}

			return;
		}
		if ( null === $order ) {
			return;
		}

		switch ( $column ) {
			case 'packetery_weight':
				$this->latteEngine->render(
					self::TEMPLATE_GRID_COLUMN_WEIGHT,
					$this->getWeightCellContentParams( $order )
				);
				break;
			case 'packetery_destination':
				$pickupPoint = $order->getPickupPoint();
				if ( null !== $pickupPoint ) {
					$pointName = $pickupPoint->getName();
					$pointId   = $pickupPoint->getId();
					if ( ! $order->isExternalCarrier() ) {
						echo esc_html( "$pointName ($pointId)" );
					} else {
						echo esc_html( $pointName );
					}
					break;
				}

				$homeDeliveryCarrierOptions = Carrier\Options::createByCarrierId( $order->getCarrier()->getId() );
				echo esc_html( $homeDeliveryCarrierOptions->getName() );
				break;
			case 'packetery_packet_id':
				$packetId = $order->getPacketId();
				if ( $packetId ) {
					echo '<a href="' . esc_attr( $this->helper->get_tracking_url( $packetId ) ) . '" target="_blank">Z' . esc_html( $packetId ) . '</a>';
				}
				break;
			case 'packetery':
				$encodedOrderGridParams = rawurlencode( $this->httpRequest->getUrl()->getQuery() );
				$packetSubmitUrl        = add_query_arg(
					[
						PacketActionsCommonLogic::PARAM_ORDER_ID => $order->getNumber(),
						Plugin::PARAM_PACKETERY_ACTION => PacketActionsCommonLogic::ACTION_SUBMIT_PACKET,
						PacketActionsCommonLogic::PARAM_REDIRECT_TO => PacketActionsCommonLogic::REDIRECT_TO_ORDER_GRID,
						Plugin::PARAM_NONCE            => wp_create_nonce( PacketActionsCommonLogic::createNonceAction( PacketActionsCommonLogic::ACTION_SUBMIT_PACKET, $order->getNumber() ) ),
						PacketActionsCommonLogic::PARAM_ORDER_GRID_PARAMS => $encodedOrderGridParams,
					],
					admin_url( 'admin.php' )
				);
				$printLink              = add_query_arg(
					[
						'page'                       => LabelPrint::MENU_SLUG,
						LabelPrint::LABEL_TYPE_PARAM => ( $order->isExternalCarrier() ? LabelPrint::ACTION_CARRIER_LABELS : LabelPrint::ACTION_PACKETA_LABELS ),
						'id'                         => $order->getNumber(),
						'packet_id'                  => $order->getPacketId(),
						'offset'                     => 0,
						PacketActionsCommonLogic::PARAM_ORDER_GRID_PARAMS => $encodedOrderGridParams,
					],
					admin_url( 'admin.php' )
				);
				$packetCancelLink       = add_query_arg(
					[
						PacketActionsCommonLogic::PARAM_ORDER_ID => $order->getNumber(),
						Plugin::PARAM_PACKETERY_ACTION => PacketActionsCommonLogic::ACTION_CANCEL_PACKET,
						PacketActionsCommonLogic::PARAM_REDIRECT_TO => PacketActionsCommonLogic::REDIRECT_TO_ORDER_GRID,
						Plugin::PARAM_NONCE            => wp_create_nonce( PacketActionsCommonLogic::createNonceAction( PacketActionsCommonLogic::ACTION_CANCEL_PACKET, $order->getNumber() ) ),
						PacketActionsCommonLogic::PARAM_ORDER_GRID_PARAMS => $encodedOrderGridParams,
					],
					admin_url( 'admin.php' )
				);

				$this->latteEngine->render(
					PACKETERY_PLUGIN_DIR . '/template/order/grid-column-packetery.latte',
					[
						'order'                     => $order,
						'orderIsSubmittable'        => $this->orderValidator->isValid( $order ),
						'packetSubmitUrl'           => $packetSubmitUrl,
						'packetCancelLink'          => $packetCancelLink,
						'printLink'                 => $printLink,
						'helper'                    => new Core\Helper(),
						'datePickerFormat'          => Core\Helper::DATEPICKER_FORMAT,
						'logPurgerDatetimeModifier' => get_option( Purger::PURGER_OPTION_NAME, Purger::PURGER_MODIFIER_DEFAULT ),
						'packetDeliverOn'           => $this->helper->getStringFromDateTime( $order->getDeliverOn(), Core\Helper::DATEPICKER_FORMAT ),
						'translations'              => [
							'printLabel'                => __( 'Print label', 'packeta' ),
							'setAdditionalPacketInfo'   => __( 'Set additional packet information', 'packeta' ),
							'submitToPacketa'           => __( 'Submit to packeta', 'packeta' ),
							// translators: %s: Order number.
							'reallyCancelPacketHeading' => sprintf( __( 'Order #%s', 'packeta' ), $order->getCustomNumber() ),
							// translators: %s: Packet number.
							'reallyCancelPacket'        => sprintf( __( 'Do you really wish to cancel parcel number %s?', 'packeta' ), (string) $order->getPacketId() ),
							'cancelPacket'              => __( 'Cancel packet', 'packeta' ),
							'lastErrorFromApi'          => __( 'Last error from Packeta API', 'packeta' ),
						],
					]
				);
				break;
			case 'packetery_packet_status':
				$statuses = PacketSynchronizer::getPacketStatuses();
				echo esc_html( isset( $statuses[ $order->getPacketStatus() ] ) ? $statuses[ $order->getPacketStatus() ]->getTranslatedName() : $order->getPacketStatus() );
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
				$new_columns['packetery_weight']        = __( 'Weight', 'packeta' );
				$new_columns['packetery']               = __( 'Packeta', 'packeta' );
				$new_columns['packetery_packet_id']     = __( 'Barcode', 'packeta' );
				$new_columns['packetery_packet_status'] = __( 'Packeta packet status', 'packeta' );
				$new_columns['packetery_destination']   = __( 'Pickup point or carrier', 'packeta' );
			}
		}

		return $new_columns;
	}
}
