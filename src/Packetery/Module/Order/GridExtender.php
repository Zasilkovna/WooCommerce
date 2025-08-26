<?php
/**
 * Class GridExtender.
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core;
use Packetery\Core\CoreHelper;
use Packetery\Latte\Engine;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\ContextResolver;
use Packetery\Module\EntityFactory\SizeFactory;
use Packetery\Module\Exception\InvalidCarrierException;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Log\Purger;
use Packetery\Module\WcLogger;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Plugin;
use Packetery\Nette\Http\Request;
use WC_Order;

use function esc_html;

/**
 * Class GridExtender.
 *
 * @package Packetery\Order
 */
class GridExtender {

	private const TEMPLATE_GRID_COLUMN_WEIGHT = PACKETERY_PLUGIN_DIR . '/template/order/grid-column-weight.latte';

	/**
	 * Generic CoreHelper.
	 *
	 * @var CoreHelper
	 */
	private $coreHelper;

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
	 * Context resolver.
	 *
	 * @var ContextResolver
	 */
	private $contextResolver;

	/**
	 * Carrier options factory.
	 *
	 * @var CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/**
	 * WordPress Adapter.
	 *
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * Module helper
	 *
	 * @var ModuleHelper
	 */
	private $moduleHelper;

	/**
	 * @var SizeFactory
	 */
	private $sizeFactory;

	/**
	 * @var PacketStatusResolver
	 */
	private $packetStatusResolver;

	/**
	 * GridExtender constructor.
	 *
	 * @param CoreHelper            $coreHelper            CoreHelper.
	 * @param Engine                $latteEngine           Latte Engine.
	 * @param Request               $httpRequest           Http Request.
	 * @param Repository            $orderRepository       Order repository.
	 * @param OrderValidatorFactory $orderValidatorFactory Order validator.
	 * @param ContextResolver       $contextResolver       Context resolver.
	 * @param CarrierOptionsFactory $carrierOptionsFactory Carrier options factory.
	 * @param WpAdapter             $wpAdapter             WordPress adapter.
	 * @param ModuleHelper          $moduleHelper          Module helper.
	 * @param SizeFactory           $sizeFactory           Size factory.
	 * @param PacketStatusResolver  $packetStatusResolver  Packet status resolver.
	 */
	public function __construct(
		CoreHelper $coreHelper,
		Engine $latteEngine,
		Request $httpRequest,
		Repository $orderRepository,
		OrderValidatorFactory $orderValidatorFactory,
		ContextResolver $contextResolver,
		CarrierOptionsFactory $carrierOptionsFactory,
		WpAdapter $wpAdapter,
		ModuleHelper $moduleHelper,
		SizeFactory $sizeFactory,
		PacketStatusResolver $packetStatusResolver
	) {
		$this->coreHelper            = $coreHelper;
		$this->latteEngine           = $latteEngine;
		$this->httpRequest           = $httpRequest;
		$this->orderRepository       = $orderRepository;
		$this->orderValidator        = $orderValidatorFactory->create();
		$this->contextResolver       = $contextResolver;
		$this->carrierOptionsFactory = $carrierOptionsFactory;
		$this->wpAdapter             = $wpAdapter;
		$this->moduleHelper          = $moduleHelper;
		$this->sizeFactory           = $sizeFactory;
		$this->packetStatusResolver  = $packetStatusResolver;
	}

	/**
	 * Adds custom filtering links to order grid.
	 *
	 * @param string[]|mixed $htmlLinks Array of html links.
	 *
	 * @return string[]|mixed
	 */
	public function addFilterLinks( array $htmlLinks ): array {
        if ( ! is_array( $htmlLinks ) ) {
            WcLogger::logArgumentTypeError( __METHOD__, 'var', 'array', $htmlLinks );
            return $var;
        }
        $linkConfig         = new GridLinksConfig(
			$this->wpAdapter->__( 'Packeta orders to submit', 'packeta' ),
			$this->wpAdapter->__( 'Packeta orders to print', 'packeta' ),
			$this->wpAdapter->__( 'Run Packeta wizard', 'packeta' )
		);
		$originalLinkConfig = clone $linkConfig;
		$linkConfig         = $this->wpAdapter->applyFilters( 'packeta_order_grid_links_settings', $linkConfig );
		if ( ! $linkConfig instanceof GridLinksConfig ) {
			$linkConfig = $originalLinkConfig;
		}

		if ( $linkConfig->isFilterOrdersToSubmitEnabled() === true ) {
			$latteParams = [
				'link'       => ModuleHelper::getOrderGridUrl(
					[
						'filter_action'       => 'packetery_filter_link',
						'packetery_to_submit' => '1',
						'packetery_to_print'  => false,
					]
				),
				'title'      => $linkConfig->getFilterOrdersToSubmitTitle(),
				'orderCount' => $this->orderRepository->countOrdersToSubmit(),
				'active'     => ( $this->httpRequest->getQuery( 'packetery_to_submit' ) === '1' ),
			];
			$htmlLinks['js-wizard-packetery-filter-orders-to-submit'] = $this->latteEngine->renderToString(
				PACKETERY_PLUGIN_DIR . '/template/order/filter-link.latte',
				$latteParams
			);
		}

		if ( $linkConfig->isFilterOrdersToPrintEnabled() === true ) {
			$latteParams = [
				'link'       => ModuleHelper::getOrderGridUrl(
					[
						'filter_action'       => 'packetery_filter_link',
						'packetery_to_submit' => false,
						'packetery_to_print'  => '1',
					]
				),
				'title'      => $linkConfig->getFilterOrdersToPrintTitle(),
				'orderCount' => $this->orderRepository->countOrdersToPrint(),
				'active'     => ( $this->httpRequest->getQuery( 'packetery_to_print' ) === '1' ),
			];
			$htmlLinks['js-wizard-packetery-filter-orders-to-print'] = $this->latteEngine->renderToString(
				PACKETERY_PLUGIN_DIR . '/template/order/filter-link.latte',
				$latteParams
			);
		}

		if ( $linkConfig->isOrderGridRunWizardEnabled() === true ) {
			$latteParams                                     = [
				'link'        => $this->wpAdapter->adminUrl( 'admin.php?page=wc-orders&wizard-enabled=true&wizard-order-grid-enabled=true' ),
				'title'       => $this->wpAdapter->__( 'Use this link to start interactive tutorial', 'packeta' ),
				'escapedText' => '&#9654; ' . htmlspecialchars( $linkConfig->getOrderGridRunWizardTitle() ),
			];
			$htmlLinks['js-wizard-packetery-order-grid-run'] = $this->latteEngine->renderToString(
				PACKETERY_PLUGIN_DIR . '/template/order/unescaped-link.latte',
				$latteParams
			);
		}

		return $htmlLinks;
	}

	/**
	 * Adds select to order grid.
	 */
	public function renderOrderTypeSelect(): void {
		if ( $this->contextResolver->isOrderGridPage() === false ) {
			return;
		}

		$linkFilters = [];

		if ( $this->httpRequest->getQuery( 'packetery_to_submit' ) !== null ) {
			$linkFilters['packetery_to_submit'] = '1';
		}

		if ( $this->httpRequest->getQuery( 'packetery_to_print' ) !== null ) {
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
	 * @return array{orderNumber: null|string, weight: null|float}
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
	 * @param string|mixed       $column Current order column name.
	 * @param WC_Order|int|mixed $wcOrder WC Order.
	 */
	public function fillCustomOrderListColumns( $column, $wcOrder ): void {
		if ( is_string( $column ) === false ) {
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
			if ( $column === 'packetery' ) {
				echo esc_html( $exception->getMessage() );
			}

			return;
		}
		if ( $order === null ) {
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
				if ( $pickupPoint !== null ) {
					$pointName = $pickupPoint->getName();
					$pointId   = $pickupPoint->getId();
					if ( ! $order->isExternalCarrier() ) {
						echo esc_html( "$pointName ($pointId)" );
					} else {
						echo esc_html( $pointName );
					}

					break;
				}

				$homeDeliveryCarrierOptions = $this->carrierOptionsFactory->createByCarrierId( $order->getCarrier()->getId() );
				echo esc_html( $homeDeliveryCarrierOptions->getName() );

				break;
			case 'packetery_packet_id':
				$packetId      = $order->getPacketId();
				$packetClaimId = $order->getPacketClaimId();
				$latteParams   = [
					'order'                    => $order,
					'packetIdTrackingUrl'      => null,
					'packetClaimIdTrackingUrl' => null,
					'translations'             => [
						'packetClaimTracking' => __( 'Packet claim tracking', 'packeta' ),
					],
				];

				if ( $packetId !== null ) {
					$latteParams['packetIdTrackingUrl'] = $this->coreHelper->getTrackingUrl( $packetId );
				}

				if ( $packetClaimId !== null ) {
					$latteParams['packetClaimIdTrackingUrl'] = $this->coreHelper->getTrackingUrl( $packetClaimId );
				}

				$this->latteEngine->render(
					PACKETERY_PLUGIN_DIR . '/template/order/grid-column-tracking.latte',
					$latteParams
				);

				break;
			case 'packetery':
				$orderGridParams = $this->httpRequest->getUrl()->getQueryParameters();
				unset( $orderGridParams['wizard-enabled'], $orderGridParams['wizard-order-grid-enabled'] );
				$encodedOrderGridParams = rawurlencode( http_build_query( $orderGridParams ) );
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
						PacketActionsCommonLogic::PARAM_PACKET_ID => $order->getPacketId(),
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
						'order'                            => $order,
						'dimensions'                       => $this->sizeFactory->createSizeInSetDimensionUnit( $order ),
						'orderIsSubmittable'               => $this->orderValidator->isValid( $order ),
						'isPossibleExtendPacketPickUpDate' => $order->isPossibleExtendPacketPickUpDate(),
						'storedUntil'                      => $this->coreHelper->getStringFromDateTime( $order->getStoredUntil(), CoreHelper::DATEPICKER_FORMAT ),
						'orderWarningFields'               => Form::getInvalidFieldsFromValidationResult( $this->orderValidator->validate( $order ) ),
						'packetSubmitUrl'                  => $packetSubmitUrl,
						'packetCancelLink'                 => $packetCancelLink,
						'printLink'                        => $printLink,
						'helper'                           => $this->coreHelper,
						'datePickerFormat'                 => CoreHelper::DATEPICKER_FORMAT,
						'logPurgerDatetimeModifier'        => $this->wpAdapter->getOption( Purger::PURGER_OPTION_NAME, Purger::PURGER_MODIFIER_DEFAULT ),
						'packetDeliverOn'                  => $this->coreHelper->getStringFromDateTime( $order->getDeliverOn(), CoreHelper::DATEPICKER_FORMAT ),
						'translations'                     => [
							'printLabel'                  => __( 'Print label', 'packeta' ),
							'setAdditionalPacketInfo'     => __( 'Set additional packet information', 'packeta' ),
							'setStoredUntil'              => __( 'Set the pickup date extension', 'packeta' ),
							'packetSubmissionNotPossible' => __( 'It is not possible to submit the shipment because all the information required for this shipment is not filled.', 'packeta' ),
							'submitToPacketa'             => __( 'Submit to Packeta', 'packeta' ),
							// translators: %s: Order number.
							'reallyCancelPacketHeading'   => sprintf( __( 'Order #%s', 'packeta' ), $order->getCustomNumber() ),
							// translators: %s: Packet number.
							'reallyCancelPacket'          => sprintf( __( 'Do you really wish to cancel parcel number %s?', 'packeta' ), (string) $order->getPacketId() ),

							'cancelPacket'                => __( 'Cancel packet', 'packeta' ),
							'lastErrorFromApi'            => __( 'Last error from Packeta API', 'packeta' ),
						],
					]
				);

				break;
			case 'packetery_packet_status':
				echo esc_html( $this->packetStatusResolver->getTranslatedName( $order->getPacketStatus() ) );

				break;
			case 'packetery_packet_stored_until':
				$this->latteEngine->render(
					PACKETERY_PLUGIN_DIR . '/template/order/grid-column-stored-until.latte',
					[
						'orderNumber' => $order->getNumber(),
						'storedUntil' => $this->moduleHelper->getTranslatedStringFromDateTime( $order->getStoredUntil() ) ?? '',
					]
				);

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

		$newColumns = array();

		foreach ( $columns as $columnName => $columnInfo ) {
			$newColumns[ $columnName ] = $columnInfo;

			if ( $columnName === 'order_total' ) {
				$newColumns['packetery_weight']              = __( 'Weight', 'packeta' );
				$newColumns['packetery']                     = __( 'Packeta', 'packeta' );
				$newColumns['packetery_packet_id']           = __( 'Tracking No.', 'packeta' );
				$newColumns['packetery_packet_status']       = __( 'Packeta packet status', 'packeta' );
				$newColumns['packetery_packet_stored_until'] = __( 'Stored until', 'packeta' );
				$newColumns['packetery_destination']         = __( 'Pickup point or carrier', 'packeta' );
			}
		}

		return $newColumns;
	}

	/**
	 * Add order list sortable columns.
	 *
	 * @param string[] $columns Order list columns.
	 *
	 * @return string[] All columns.
	 */
	public function makeOrderListSpecificColumnsSortable( array $columns ): array {
		$metaKey = 'packetery_packet_stored_until';

		return wp_parse_args( [ 'packetery_packet_stored_until' => $metaKey ], $columns );
	}
}
