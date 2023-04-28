<?php
/**
 * Class GridExtender.
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core;
use Packetery\Core\Helper;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Log\Purger;
use Packetery\Module\WcLogger;
use PacketeryLatte\Engine;
use PacketeryNette\Http\Request;
use Packetery\Module\Plugin;

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
	 * @var Helper
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
	 * GridExtender constructor.
	 *
	 * @param Helper                    $helper                Helper.
	 * @param Carrier\EntityRepository  $carrierRepository     Carrier repository.
	 * @param Engine                    $latteEngine           Latte Engine.
	 * @param Request                   $httpRequest           Http Request.
	 * @param Repository                $orderRepository       Order repository.
	 * @param Core\Validator\Order      $orderValidator        Order validator.
	 * @param PacketaPickupPointsConfig $pickupPointsConfig    Internal pickup points config.
	 */
	public function __construct(
		Helper $helper,
		Carrier\EntityRepository $carrierRepository,
		Engine $latteEngine,
		Request $httpRequest,
		Repository $orderRepository,
		Core\Validator\Order $orderValidator,
		PacketaPickupPointsConfig $pickupPointsConfig
	) {
		$this->helper             = $helper;
		$this->carrierRepository  = $carrierRepository;
		$this->latteEngine        = $latteEngine;
		$this->httpRequest        = $httpRequest;
		$this->orderRepository    = $orderRepository;
		$this->orderValidator     = $orderValidator;
		$this->pickupPointsConfig = $pickupPointsConfig;
	}

	/**
	 * Adds custom filtering links to order grid.
	 *
	 * @param array|mixed $htmlLinks Array of html links.
	 *
	 * @return array|mixed
	 */
	public function addFilterLinks( $htmlLinks ) {
		if ( ! is_array( $htmlLinks ) ) {
			WcLogger::logArgumentTypeError( __METHOD__, 'var', 'array', $htmlLinks );
			return $htmlLinks;
		}

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
		$htmlLinks[] = $this->latteEngine->renderToString( PACKETERY_PLUGIN_DIR . '/template/order/filter-link.latte', $latteParams );

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
		$htmlLinks[] = $this->latteEngine->renderToString( PACKETERY_PLUGIN_DIR . '/template/order/filter-link.latte', $latteParams );

		return $htmlLinks;
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
	 * @param int $postId Post ID.
	 *
	 * @return Core\Entity\Order|null
	 */
	private function getOrderByPostId( int $postId ): ?Core\Entity\Order {
		global $posts;
		static $ordersCache;

		if ( ! isset( $ordersCache ) ) {
			$orderIds = [];
			foreach ( $posts as $order ) {
				$orderIds[] = $order->ID;
			}
			$ordersCache = $this->orderRepository->getByIds( $orderIds );
		}

		return $ordersCache[ $postId ] ?? null;
	}

	/**
	 * Fills custom order list columns.
	 *
	 * @param string|mixed $column Current order column name.
	 */
	public function fillCustomOrderListColumns( $column ): void {
		if ( ! is_string( $column ) ) {
			WcLogger::logArgumentTypeError( __METHOD__, 'column', 'string', $column );
			return;
		}

		global $post;

		$order = $this->getOrderByPostId( $post->ID );

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
					$pointName         = $pickupPoint->getName();
					$pointId           = $pickupPoint->getId();
					$country           = $order->getShippingCountry();
					$internalCountries = $this->pickupPointsConfig->getInternalCountries();
					if ( in_array( $country, $internalCountries, true ) ) {
						echo esc_html( "$pointName ($pointId)" );
					} else {
						echo esc_html( $pointName );
					}
					break;
				}

				$homeDeliveryCarrierOptions = Carrier\Options::createByCarrierId( $order->getCarrierId() );
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
						'orderIsSubmittable'        => $this->orderValidator->validate( $order ),
						'packetSubmitUrl'           => $packetSubmitUrl,
						'packetCancelLink'          => $packetCancelLink,
						'printLink'                 => $printLink,
						'helper'                    => new Helper(),
						'datePickerFormat'          => Helper::DATEPICKER_FORMAT,
						'logPurgerDatetimeModifier' => get_option( Purger::PURGER_OPTION_NAME, Purger::PURGER_MODIFIER_DEFAULT ),
						'packetDeliverOn'           => $this->helper->getStringFromDateTime( $order->getDeliverOn(), Helper::DATEPICKER_FORMAT ),
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
	 * @param string[]|mixed $columns Order list columns.
	 *
	 * @return string[]|mixed All columns.
	 */
	public function addOrderListColumns( $columns ) {
		if ( ! is_array( $columns ) ) {
			WcLogger::logArgumentTypeError( __METHOD__, 'columns', 'array', $columns );
			return $columns;
		}

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
