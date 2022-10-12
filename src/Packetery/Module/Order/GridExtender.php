<?php
/**
 * Class GridExtender.
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core;
use Packetery\Core\Entity;
use Packetery\Core\Helper;
use Packetery\Module\Carrier;
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
	 * Order entity cache.
	 *
	 * @var Entity\Order|null
	 */
	public static $orderCache;

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
	 * Order Validator.
	 *
	 * @var Core\Validator\Order
	 */
	private $orderValidator;

	/**
	 * GridExtender constructor.
	 *
	 * @param Helper               $helper                Helper.
	 * @param Carrier\Repository   $carrierRepository     Carrier repository.
	 * @param Engine               $latteEngine           Latte Engine.
	 * @param Request              $httpRequest           Http Request.
	 * @param ControllerRouter     $orderControllerRouter Order controller router.
	 * @param Repository           $orderRepository       Order repository.
	 * @param Core\Validator\Order $orderValidator        Order validator.
	 */
	public function __construct(
		Helper $helper,
		Carrier\Repository $carrierRepository,
		Engine $latteEngine,
		Request $httpRequest,
		ControllerRouter $orderControllerRouter,
		Repository $orderRepository,
		Core\Validator\Order $orderValidator
	) {
		$this->helper                = $helper;
		$this->carrierRepository     = $carrierRepository;
		$this->latteEngine           = $latteEngine;
		$this->httpRequest           = $httpRequest;
		$this->orderControllerRouter = $orderControllerRouter;
		$this->orderRepository       = $orderRepository;
		$this->orderValidator        = $orderValidator;
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
	 * Fills custom order list columns.
	 *
	 * @param string $column Current order column name.
	 */
	public function fillCustomOrderListColumns( string $column ): void {
		global $post;

		if ( ! isset( self::$orderCache ) || (int) self::$orderCache->getNumber() !== $post->ID ) {
			self::$orderCache = $this->orderRepository->getById( $post->ID );
			if ( null === self::$orderCache ) {
				return;
			}
		}

		$order = self::$orderCache;

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
				$packetSubmitUrl  = add_query_arg(
					[
						PacketActionsCommonLogic::PARAM_ORDER_ID => $order->getNumber(),
						Plugin::PARAM_PACKETERY_ACTION => PacketActionsCommonLogic::ACTION_SUBMIT_PACKET,
						PacketActionsCommonLogic::PARAM_REDIRECT_TO => PacketActionsCommonLogic::REDIRECT_TO_ORDER_GRID,
						Plugin::PARAM_NONCE            => wp_create_nonce( PacketActionsCommonLogic::createNonceAction( PacketActionsCommonLogic::ACTION_SUBMIT_PACKET, $order->getNumber() ) ),
					],
					admin_url( 'admin.php' )
				);
				$printLink        = add_query_arg(
					[
						'page'                       => LabelPrint::MENU_SLUG,
						LabelPrint::LABEL_TYPE_PARAM => ( $order->isExternalCarrier() ? LabelPrint::ACTION_CARRIER_LABELS : LabelPrint::ACTION_PACKETA_LABELS ),
						'id'                         => $order->getNumber(),
						'packet_id'                  => $order->getPacketId(),
						'offset'                     => 0,
					],
					admin_url( 'admin.php' )
				);
				$packetCancelLink = add_query_arg(
					[
						PacketActionsCommonLogic::PARAM_ORDER_ID => $order->getNumber(),
						Plugin::PARAM_PACKETERY_ACTION => PacketActionsCommonLogic::ACTION_CANCEL_PACKET,
						PacketActionsCommonLogic::PARAM_REDIRECT_TO => PacketActionsCommonLogic::REDIRECT_TO_ORDER_GRID,
						Plugin::PARAM_NONCE            => wp_create_nonce( PacketActionsCommonLogic::createNonceAction( PacketActionsCommonLogic::ACTION_CANCEL_PACKET, $order->getNumber() ) ),
					],
					admin_url( 'admin.php' )
				);

				$this->latteEngine->render(
					PACKETERY_PLUGIN_DIR . '/template/order/grid-column-packetery.latte',
					[
						'order'              => $order,
						'orderIsSubmittable' => $this->orderValidator->validateForSubmission( $order ),
						'packetSubmitUrl'    => $packetSubmitUrl,
						'packetCancelLink'   => $packetCancelLink,
						'printLink'          => $printLink,
						'translations'       => [
							'printLabel'                => __( 'Print label', 'packeta' ),
							'setAdditionalPacketInfo'   => __( 'Set additional packet information', 'packeta' ),
							'submitToPacketa'           => __( 'Submit to packeta', 'packeta' ),
							// translators: %s: Order number.
							'reallyCancelPacketHeading' => sprintf( __( 'Order #%s', 'packeta' ), $order->getCustomNumber() ),
							// translators: %s: Packet number.
							'reallyCancelPacket'        => sprintf( __( 'Do you really wish to cancel parcel number %s?', 'packeta' ), (string) $order->getPacketId() ),
							'cancelPacket'              => __( 'Cancel packet', 'packeta' ),
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
				$new_columns['packetery_destination']   = __( 'Pick up point or carrier', 'packeta' );
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
