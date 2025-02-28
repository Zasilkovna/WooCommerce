<?php
/**
 * Class Repository.
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use DateTimeImmutable;
use Exception;
use Packetery\Core\CoreHelper;
use Packetery\Core\Entity;
use Packetery\Core\Entity\Order;
use Packetery\Core\Entity\PickupPoint;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\CustomsDeclaration;
use Packetery\Module\Exception\InvalidCarrierException;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Shipping\ShippingProvider;
use Packetery\Module\WpdbAdapter;
use WC_Order;
use WP_Post;

/**
 * Class Repository.
 *
 * @package Packetery\Module\Order
 */
class Repository {

	/**
	 * WpdbAdapter.
	 *
	 * @var WpdbAdapter
	 */
	private $wpdbAdapter;

	/**
	 * Order factory.
	 *
	 * @var Builder
	 */
	private $builder;

	/**
	 * CoreHelper.
	 *
	 * @var CoreHelper
	 */
	private $coreHelper;

	/**
	 * Internal pickup points config.
	 *
	 * @var PacketaPickupPointsConfig
	 */
	private $pickupPointsConfig;

	/**
	 * Carrier repository.
	 *
	 * @var Carrier\EntityRepository
	 */
	private $carrierRepository;

	/**
	 * Customs declaration repository.
	 *
	 * @var CustomsDeclaration\Repository
	 */
	private $customsDeclarationRepository;

	/**
	 * Repository constructor.
	 *
	 * @param WpdbAdapter                   $wpdbAdapter                  WpdbAdapter.
	 * @param Builder                       $orderFactory                 Order factory.
	 * @param CoreHelper                    $coreHelper                   CoreHelper.
	 * @param PacketaPickupPointsConfig     $pickupPointsConfig           Internal pickup points config.
	 * @param Carrier\EntityRepository      $carrierRepository            Carrier repository.
	 * @param CustomsDeclaration\Repository $customsDeclarationRepository Customs declaration repository.
	 */
	public function __construct(
		WpdbAdapter $wpdbAdapter,
		Builder $orderFactory,
		CoreHelper $coreHelper,
		PacketaPickupPointsConfig $pickupPointsConfig,
		Carrier\EntityRepository $carrierRepository,
		CustomsDeclaration\Repository $customsDeclarationRepository
	) {
		$this->wpdbAdapter                  = $wpdbAdapter;
		$this->builder                      = $orderFactory;
		$this->coreHelper                   = $coreHelper;
		$this->pickupPointsConfig           = $pickupPointsConfig;
		$this->carrierRepository            = $carrierRepository;
		$this->customsDeclarationRepository = $customsDeclarationRepository;
	}

	/**
	 * Applies custom order status filter.
	 *
	 * @param array<string, string|null> $clauses     Query clauses.
	 * @param \WP_Query|null             $queryObject WP Query.
	 * @param array<string, string|null> $paramValues Param values.
	 *
	 * @return void
	 */
	private function applyCustomFilters( array &$clauses, ?\WP_Query $queryObject, array $paramValues ): void {
		/**
		 * Filters order statuses to exclude in Packeta order list filtering.
		 *
		 * @since 1.4
		 *
		 * @param array $orderStatusesToExclude Order statuses to exclude.
		 * @param \WP_Query|null $queryObject WP Query.
		 * @param array $paramValues Param values.
		 */
		$orderStatusesToExclude = (array) apply_filters( 'packetery_exclude_orders_with_status', [], $queryObject, $paramValues );
		if ( count( $orderStatusesToExclude ) === 0 ) {
			return;
		}

		if ( ModuleHelper::isHposEnabled() ) {
			$clauses['where'] .= sprintf( ' AND `%s`.`status` NOT IN (%s)', $this->wpdbAdapter->wcOrders, $this->wpdbAdapter->prepareInClause( $orderStatusesToExclude ) );
		} else {
			$clauses['where'] .= sprintf( ' AND `%s`.`post_status` NOT IN (%s)', $this->wpdbAdapter->posts, $this->wpdbAdapter->prepareInClause( $orderStatusesToExclude ) );
		}
	}

	/**
	 * Extends SQL query clauses.
	 *
	 * @param array<string, string>      $clauses Clauses.
	 * @param \WP_Query|null             $queryObject Query object.
	 * @param array<string, string|null> $paramValues Param values.
	 *
	 * @return array<string, string>
	 */
	public function processClauses( array $clauses, ?\WP_Query $queryObject, array $paramValues ): array {
		if ( ModuleHelper::isHposEnabled() ) {
			$clauses['join'] .= ' LEFT JOIN `' . $this->wpdbAdapter->packeteryOrder . '` ON `' . $this->wpdbAdapter->packeteryOrder . '`.`id` = `' . $this->wpdbAdapter->wcOrders . '`.`id`';
		} else {
			$clauses['join'] .= ' LEFT JOIN `' . $this->wpdbAdapter->packeteryOrder . '` ON `' . $this->wpdbAdapter->packeteryOrder . '`.`id` = `' . $this->wpdbAdapter->posts . '`.`id`';
		}

		if ( isset( $paramValues['packetery_carrier_id'] ) && $paramValues['packetery_carrier_id'] !== '' ) {
			$clauses['where'] .= ' AND `' . $this->wpdbAdapter->packeteryOrder . '`.`carrier_id` = "' . esc_sql( $paramValues['packetery_carrier_id'] ) . '"';
			$this->applyCustomFilters( $clauses, $queryObject, $paramValues );
		}
		if ( isset( $paramValues['packetery_to_submit'] ) && $paramValues['packetery_to_submit'] !== '' ) {
			$clauses['where'] .= ' AND `' . $this->wpdbAdapter->packeteryOrder . '`.`carrier_id` IS NOT NULL ';
			$clauses['where'] .= ' AND `' . $this->wpdbAdapter->packeteryOrder . '`.`is_exported` = false ';
			$this->applyCustomFilters( $clauses, $queryObject, $paramValues );
		}
		if ( isset( $paramValues['packetery_to_print'] ) && $paramValues['packetery_to_print'] !== '' ) {
			$clauses['where'] .= ' AND `' . $this->wpdbAdapter->packeteryOrder . '`.`packet_id` IS NOT NULL ';
			$clauses['where'] .= ' AND `' . $this->wpdbAdapter->packeteryOrder . '`.`is_label_printed` = false ';
			$this->applyCustomFilters( $clauses, $queryObject, $paramValues );
		}
		if ( isset( $paramValues['packetery_order_type'] ) && $paramValues['packetery_order_type'] !== '' ) {
			if ( $paramValues['packetery_order_type'] === Entity\Carrier::INTERNAL_PICKUP_POINTS_ID ) {
				$comparison = 'IN';
			} else {
				$comparison = 'NOT IN';
			}
			$internalCarriers   = array_keys( $this->carrierRepository->getNonFeedCarriers() );
			$internalCarriers[] = Entity\Carrier::INTERNAL_PICKUP_POINTS_ID;
			$clauses['where']  .= ' AND `' . $this->wpdbAdapter->packeteryOrder . '`.`carrier_id` ' . $comparison . ' (' . $this->wpdbAdapter->prepareInClause( $internalCarriers ) . ')';
			$this->applyCustomFilters( $clauses, $queryObject, $paramValues );
		}
		if ( isset( $paramValues['orderby'] ) && $paramValues['orderby'] === 'packetery_packet_stored_until' ) {
			if ( $paramValues['order'] === 'asc' ) {
				$clauses['orderby'] = '`' . $this->wpdbAdapter->packeteryOrder . '`.`stored_until` ASC';
			}
			if ( $paramValues['order'] === 'desc' ) {
				$clauses['orderby'] = '`' . $this->wpdbAdapter->packeteryOrder . '`.`stored_until` DESC';
			}
		}

		return $clauses;
	}

	/**
	 * Create table to store orders.
	 *
	 * @return bool
	 */
	public function createOrAlterTable(): bool {
		$createTableQuery = 'CREATE TABLE ' . $this->wpdbAdapter->packeteryOrder . ' (
			`id` bigint(20) unsigned NOT NULL,
			`carrier_id` varchar(15) NOT NULL,
			`is_exported` tinyint(1) NOT NULL,
			`packet_id` varchar(15) NULL,
			`packet_claim_id` varchar(15) NULL,
			`packet_claim_password` varchar(10) NULL,
			`is_label_printed` tinyint(1) NOT NULL,
			`point_id` varchar(50) NULL,
			`point_name` varchar(150) NULL,
			`point_url` varchar(255) NULL,
			`point_street` varchar(120) NULL,
			`point_zip` varchar(10) NULL,
			`point_city` varchar(70) NULL,
			`address_validated` tinyint(1) NOT NULL,
			`delivery_address` text NULL,
			`weight` float NULL,
			`length` float NULL,
			`width` float NULL,
			`height` float NULL,
			`adult_content` tinyint(1) NULL,
			`value` double NULL,
			`cod` double NULL,
			`api_error_message` text NULL,
			`api_error_date` datetime NULL,
			`carrier_number` varchar(50) NULL,
			`packet_status` varchar(30) NULL,
			`stored_until` date NULL,
			`deliver_on` date NULL,
			`car_delivery_id` varchar(15) NULL,
			PRIMARY KEY  (`id`)
		) ' . $this->wpdbAdapter->get_charset_collate();

		return $this->wpdbAdapter->dbDelta( $createTableQuery, $this->wpdbAdapter->packeteryOrder );
	}

	/**
	 * Drop table used to store orders.
	 */
	public function drop(): void {
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packeteryOrder . '`' );
	}

	/**
	 * @throws InvalidCarrierException InvalidCarrierException.
	 */
	public function getById( int $id ): ?Order {
		$wcOrder = $this->getWcOrderById( $id );
		if ( $wcOrder === null ) {
			return null;
		}

		return $this->getByWcOrder( $wcOrder );
	}

	/**
	 * Ignores InvalidCarrierException.
	 */
	public function getByIdWithValidCarrier( int $id ): ?Order {
		$wcOrder = $this->getWcOrderById( $id );
		if ( $wcOrder === null ) {
			return null;
		}

		return $this->getByWcOrderWithValidCarrier( $wcOrder );
	}

	/**
	 * Returns Packeta order, or null.
	 *
	 * @param int $id Order ID.
	 *
	 * @return Order|null
	 */
	public function findById( int $id ): ?Order {
		$wcOrder = $this->getWcOrderById( $id );
		if ( $wcOrder === null ) {
			return null;
		}

		$result = $this->getDataById( $wcOrder->get_id() );
		if ( $result === null ) {
			return null;
		}

		try {
			return $this->builder->build( $wcOrder, $result );
		} catch ( InvalidCarrierException $invalidCarrierException ) {
			return null;
		}
	}

	/**
	 * Gets Packeta order data by id.
	 *
	 * @param int $id Order id.
	 *
	 * @return object|null
	 */
	public function getDataById( int $id ): ?object {
		return $this->wpdbAdapter->get_row(
			$this->wpdbAdapter->prepare(
				'
				SELECT `o`.* FROM `' . $this->wpdbAdapter->packeteryOrder . '` `o`
				' . $this->getWcOrderJoinClause() . '
				WHERE `o`.`id` = %d',
				$id
			)
		);
	}

	private function getDataByWcOrder( WC_Order $wcOrder ): ?object {
		if ( ! ShippingProvider::wcOrderHasOurMethod( $wcOrder ) ) {
			return null;
		}

		$packeteryOrderData = $this->getDataById( $wcOrder->get_id() );
		if ( ! is_object( $packeteryOrderData ) ) {
			return null;
		}

		return $packeteryOrderData;
	}

	/**
	 * @throws InvalidCarrierException
	 */
	public function getByWcOrder( WC_Order $wcOrder ): ?Order {
		$packeteryOrderData = $this->getDataByWcOrder( $wcOrder );
		if ( $packeteryOrderData === null ) {
			return null;
		}

		return $this->builder->build( $wcOrder, $packeteryOrderData );
	}

	/**
	 * Ignores InvalidCarrierException.
	 */
	public function getByWcOrderWithValidCarrier( WC_Order $wcOrder ): ?Order {
		$packeteryOrderData = $this->getDataByWcOrder( $wcOrder );
		if ( $packeteryOrderData === null ) {
			return null;
		}

		try {
			return $this->builder->build( $wcOrder, $packeteryOrderData );
		} catch ( InvalidCarrierException $invalidCarrierException ) {
			return null;
		}
	}

	/**
	 * Transforms order to DB array.
	 *
	 * @param Order $order Order.
	 *
	 * @return array<string, bool|float|int|string|null>
	 */
	private function orderToDbArray( Order $order ): array {
		$point = $order->getPickupPoint();
		if ( $point === null ) {
			$point = new PickupPoint();
		}

		$deliveryAddress = null;
		if ( $order->isAddressValidated() && $order->getDeliveryAddress() !== null ) {
			$deliveryAddress = wp_json_encode( $order->getDeliveryAddress()->export() );
		}

		$apiErrorDateTime = $order->getLastApiErrorDateTime();
		if ( $apiErrorDateTime !== null ) {
			$apiErrorDateTime = $apiErrorDateTime->format( CoreHelper::MYSQL_DATETIME_FORMAT );
		}

		$data = [
			'id'                    => (int) $order->getNumber(),
			'carrier_id'            => $order->getCarrier()->getId(),
			'is_exported'           => (int) $order->isExported(),
			'packet_id'             => $order->getPacketId(),
			'packet_claim_id'       => $order->getPacketClaimId(),
			'packet_claim_password' => $order->getPacketClaimPassword(),
			'packet_status'         => $order->getPacketStatus(),
			'stored_until'          => $this->coreHelper->getStringFromDateTime( $order->getStoredUntil(), CoreHelper::DATEPICKER_FORMAT ),
			'is_label_printed'      => (int) $order->isLabelPrinted(),
			'carrier_number'        => $order->getCarrierNumber(),
			'weight'                => $order->getWeight(),
			'car_delivery_id'       => $order->getCarDeliveryId(),
			'point_id'              => $point->getId(),
			'point_name'            => $point->getName(),
			'point_url'             => $point->getUrl(),
			'point_street'          => $point->getStreet(),
			'point_zip'             => $point->getZip(),
			'point_city'            => $point->getCity(),
			'address_validated'     => (int) $order->isAddressValidated(),
			'delivery_address'      => $deliveryAddress,
			'length'                => $order->getLength(),
			'width'                 => $order->getWidth(),
			'height'                => $order->getHeight(),
			'adult_content'         => $order->containsAdultContent(),
			'cod'                   => $order->getManualCod(),
			'value'                 => $order->getManualValue(),
			'api_error_message'     => $order->getLastApiErrorMessage(),
			'api_error_date'        => $apiErrorDateTime,
			'deliver_on'            => $this->coreHelper->getStringFromDateTime( $order->getDeliverOn(), CoreHelper::DATEPICKER_FORMAT ),
		];

		return $data;
	}

	/**
	 * Saves order.
	 *
	 * @param Order $order Order.
	 *
	 * @return void
	 */
	public function save( Order $order ): void {
		$this->saveData( $this->orderToDbArray( $order ) );
	}

	/**
	 * Saves order data.
	 *
	 * @param array<string, int|string|null|DateTimeImmutable> $orderData Order data.
	 *
	 * @return void
	 */
	public function saveData( array $orderData ): void {
		$this->onBeforeDataInsertion( $orderData );
		$this->wpdbAdapter->insertReplaceHelper( $this->wpdbAdapter->packeteryOrder, $orderData, null, 'REPLACE' );
	}

	/**
	 * Calls logic before order data replace/insert.
	 *
	 * @param array<string, string|null|DateTimeImmutable> $orderData Order data.
	 * @return void
	 */
	private function onBeforeDataInsertion( array $orderData ): void {
		$pointId   = $orderData['point_id'] ?? null;
		$carrierId = $orderData['carrier_id'] ?? null;
		/**
		 * Tells if Packeta debug logs are enabled.
		 *
		 * @since 1.7.2
		 */
		$isLoggingActive = (bool) apply_filters( 'packeta_enable_debug_logs', false );

		if ( ( $pointId !== null && $pointId !== '' ) || ! $isLoggingActive || ! $this->pickupPointsConfig->isInternalPickupPointCarrier( $carrierId ) ) {
			return;
		}

		$wcLogger  = wc_get_logger();
		$dataToLog = [
			'order' => $orderData,
			'trace' => array_map(
				static function ( array $item ): array {
					return [
						'file'   => sprintf( '%s(%s)', $item['file'], $item['line'] ),
						'method' => sprintf( '%s%s%s(...)', $item['class'] ?? '', $item['type'] ?? '', $item['function'] ),
					];
				},
				( new Exception() )->getTrace()
			),
		];
		$wcLogger->warning(
			sprintf(
				'Required value for the pickup point order is not set: %s',
				wp_json_encode( $dataToLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
			),
			[ 'source' => 'packeta' ]
		);
	}

	/**
	 * Loads order entities by list of ids.
	 *
	 * @param string[] $orderIds Order ids.
	 *
	 * @return Order[]
	 */
	public function getByIds( array $orderIds ): array {
		if ( count( $orderIds ) === 0 ) {
			return [];
		}

		$wpdbAdapter           = $this->wpdbAdapter;
		$ordersIdsPlaceholder  = implode( ', ', array_fill( 0, count( $orderIds ), '%d' ) );
		$packeteryOrdersResult = $wpdbAdapter->get_results(
			$wpdbAdapter->prepare(
				'SELECT * FROM `' . $wpdbAdapter->packeteryOrder . '` 
				WHERE `id` IN (' . $ordersIdsPlaceholder . ')',
				$orderIds
			),
			OBJECT_K
		);

		$orderEntities = [];
		foreach ( $orderIds as $orderId ) {
			if ( ! isset( $packeteryOrdersResult[ $orderId ] ) ) {
				continue;
			}

			$wcOrder = $this->getWcOrderById( (int) $orderId );
			assert( $wcOrder !== null, 'WC order has to be present' );

			try {
				$orderEntities[ $orderId ] = $this->builder->build( $wcOrder, $packeteryOrdersResult[ $orderId ] );
			} catch ( InvalidCarrierException $exception ) {
				continue;
			}
		}

		return $orderEntities;
	}

	/**
	 * Finds orders.
	 *
	 * @param string[] $allowedPacketStatuses Allowed packet statuses.
	 * @param string[] $allowedOrderStatuses  Allowed order statuses.
	 * @param int      $maxDays               Max number of days of single packet sync.
	 * @param int      $limit                 Number of records.
	 *
	 * @return \Generator<int>
	 * @throws Exception Exception.
	 */
	public function findStatusSyncingOrderIds( array $allowedPacketStatuses, array $allowedOrderStatuses, int $maxDays, int $limit ): \Generator {
		$dateLimit = CoreHelper::now()->modify( '- ' . $maxDays . ' days' )->format( 'Y-m-d H:i:s' );

		$andWhere    = [ '`o`.`packet_id` IS NOT NULL' ];
		$hposEnabled = ModuleHelper::isHposEnabled();

		if ( $hposEnabled ) {
			$andWhere[] = $this->wpdbAdapter->prepare( '`wc_o`.`date_created_gmt` >= %s', $dateLimit );
		} else {
			$andWhere[] = $this->wpdbAdapter->prepare( '`wp_p`.`post_date_gmt` >= %s', $dateLimit );
		}

		$orPacketStatus   = [];
		$orPacketStatus[] = '`o`.`packet_status` IS NULL';

		if ( count( $allowedPacketStatuses ) > 0 ) {
			$orPacketStatus[] = '`o`.`packet_status` IN (' . $this->wpdbAdapter->prepareInClause( $allowedPacketStatuses ) . ')';
		}

		if ( count( $orPacketStatus ) > 0 ) {
			$andWhere[] = '(' . implode( ' OR ', $orPacketStatus ) . ')';
		}

		if ( count( $allowedOrderStatuses ) > 0 && $hposEnabled ) {
			$andWhere[] = '`wc_o`.`status` IN (' . $this->wpdbAdapter->prepareInClause( $allowedOrderStatuses ) . ')';
		} elseif ( count( $allowedOrderStatuses ) > 0 && $hposEnabled === false ) {
			$andWhere[] = '`wp_p`.`post_status` IN (' . $this->wpdbAdapter->prepareInClause( $allowedOrderStatuses ) . ')';
		} else {
			$andWhere[] = '1 = 0';
		}

		$where = '';
		if ( count( $andWhere ) > 0 ) {
			$where = ' WHERE ' . implode( ' AND ', $andWhere );
		}

		if ( $hposEnabled ) {
			$orderBy = ' ORDER BY `wc_o`.`date_created_gmt` ';
		} else {
			$orderBy = ' ORDER BY `wp_p`.`post_date_gmt` ';
		}

		$sql = $this->wpdbAdapter->prepare(
			'
			SELECT `o`.`id` FROM `' . $this->wpdbAdapter->packeteryOrder . '` `o` 
			' . $this->getWcOrderJoinClause() . '
			' . $where . '
			' . $orderBy . '
			LIMIT %d',
			$limit
		);

		$rows = $this->wpdbAdapter->get_results( $sql );

		foreach ( $rows as $row ) {
			$wcOrder = $this->getWcOrderById( (int) $row->id );
			if ( $wcOrder === null || ! ShippingProvider::wcOrderHasOurMethod( $wcOrder ) ) {
				continue;
			}

			yield (int) $row->id;
		}
	}

	/**
	 * Counts order to be submitted.
	 *
	 * @return int
	 */
	public function countOrdersToSubmit(): int {
		return $this->countOrders( [ 'packetery_to_submit' => '1' ] );
	}

	/**
	 * Counts order to be printed.
	 *
	 * @return int
	 */
	public function countOrdersToPrint(): int {
		return $this->countOrders( [ 'packetery_to_print' => '1' ] );
	}

	/**
	 * Counts orders by given params.
	 *
	 * @param array<string, string> $params Params.
	 *
	 * @return int
	 */
	private function countOrders( array $params ): int {
		$clauses = [ 'join' => '' ];

		if ( ModuleHelper::isHposEnabled() ) {
			$clauses['select'] = ' SELECT COUNT(DISTINCT `' . $this->wpdbAdapter->wcOrders . '`.`id`) ';
			$clauses['from']   = ' FROM `' . $this->wpdbAdapter->wcOrders . '` ';
		} else {
			$clauses['select'] = ' SELECT COUNT(DISTINCT `' . $this->wpdbAdapter->posts . '`.`ID`) ';
			$clauses['from']   = ' FROM `' . $this->wpdbAdapter->posts . '` ';
		}

		$clauses['where'] = ' WHERE `' . $this->wpdbAdapter->packeteryOrder . '`.`id` IS NOT NULL ';
		$clauses          = $this->processClauses( $clauses, null, $params );

		return (int) $this->wpdbAdapter->get_var(
			$clauses['select'] .
			$clauses['from'] .
			$clauses['join'] .
			$clauses['where']
		);
	}

	/**
	 * Gets WC Order join clause.
	 *
	 * @return string
	 */
	public function getWcOrderJoinClause(): string {
		$packeteryTableAlias = 'o';

		if ( ModuleHelper::isHposEnabled() ) {
			$sourceTableAlias = 'wc_o';

			return sprintf(
				'JOIN `%s` `%s` ON `%s`.`id` = `%s`.`id`',
				$this->wpdbAdapter->wcOrders,
				$sourceTableAlias,
				$sourceTableAlias,
				$packeteryTableAlias
			);
		}

		$sourceTableAlias = 'wp_p';

		return sprintf(
			'JOIN `%s` `%s` ON `%s`.`ID` = `%s`.`id`',
			$this->wpdbAdapter->posts,
			$sourceTableAlias,
			$sourceTableAlias,
			$packeteryTableAlias
		);
	}

	/**
	 * Deletes all custom table records linked to permanently deleted orders.
	 *
	 * @return void
	 */
	public function deleteOrphans(): void {
		if ( ModuleHelper::isHposEnabled() ) {
			$this->wpdbAdapter->query(
				'DELETE `' . $this->wpdbAdapter->packeteryOrder . '` FROM `' . $this->wpdbAdapter->packeteryOrder . '`
			LEFT JOIN `' . $this->wpdbAdapter->wcOrders . '` ON `' . $this->wpdbAdapter->wcOrders . '`.`id` = `' . $this->wpdbAdapter->packeteryOrder . '`.`id`
			WHERE `' . $this->wpdbAdapter->wcOrders . '`.`id` IS NULL'
			);

			return;
		}

		$this->wpdbAdapter->query(
			'DELETE `' . $this->wpdbAdapter->packeteryOrder . '` FROM `' . $this->wpdbAdapter->packeteryOrder . '`
			LEFT JOIN `' . $this->wpdbAdapter->posts . '` ON `' . $this->wpdbAdapter->posts . '`.`ID` = `' . $this->wpdbAdapter->packeteryOrder . '`.`id`
			WHERE `' . $this->wpdbAdapter->posts . '`.`ID` IS NULL'
		);
	}

	/**
	 * Deletes order data including customs declaration and its items from custom tables.
	 *
	 * @param int $orderId Order id.
	 *
	 * @return void
	 */
	public function delete( int $orderId ): void {
		$this->customsDeclarationRepository->delete( (string) $orderId );
		$this->wpdbAdapter->delete( $this->wpdbAdapter->packeteryOrder, [ 'id' => $orderId ], '%d' );
	}

	/**
	 * Fires after post deletion.
	 *
	 * @param int     $postId Post id.
	 * @param WP_Post $post Post object.
	 *
	 * @return void
	 */
	public function deletedPostHook( int $postId, WP_Post $post ): void {
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		if ( $post->post_type === 'shop_order' ) {
			$this->delete( $postId );
		}
	}

	/**
	 * Gets WC_Order object.
	 *
	 * @param int $id Order id.
	 *
	 * @return WC_Order|null
	 */
	public function getWcOrderById( int $id ): ?WC_Order {
		$result = wc_get_order( $id );
		if ( $result instanceof WC_Order ) {
			return $result;
		}

		return null;
	}
}
