<?php
/**
 * Class Repository.
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core;
use Packetery\Core\Entity;
use Packetery\Core\Entity\Order;
use Packetery\Core\Entity\PickupPoint;
use Packetery\Core\Entity\Size;
use Packetery\Core\Entity\Address;
use Packetery\Module;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Exception\InvalidCarrierException;
use Packetery\Module\ShippingMethod;
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
	 * Helper.
	 *
	 * @var Core\Helper
	 */
	private $helper;

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
	 * Repository constructor.
	 *
	 * @param WpdbAdapter               $wpdbAdapter        WpdbAdapter.
	 * @param Builder                   $orderFactory       Order factory.
	 * @param Core\Helper               $helper             Helper.
	 * @param PacketaPickupPointsConfig $pickupPointsConfig Internal pickup points config.
	 * @param Carrier\EntityRepository  $carrierRepository  Carrier repository.
	 */
	public function __construct(
		WpdbAdapter $wpdbAdapter,
		Builder $orderFactory,
		Core\Helper $helper,
		PacketaPickupPointsConfig $pickupPointsConfig,
		Carrier\EntityRepository $carrierRepository
	) {
		$this->wpdbAdapter        = $wpdbAdapter;
		$this->builder            = $orderFactory;
		$this->helper             = $helper;
		$this->pickupPointsConfig = $pickupPointsConfig;
		$this->carrierRepository  = $carrierRepository;
	}

	/**
	 * Applies custom order status filter.
	 *
	 * @param array     $clauses Query clauses.
	 * @param \WP_Query $queryObject WP Query.
	 * @param array     $paramValues Param values.
	 *
	 * @return void
	 */
	private function applyCustomFilters( array &$clauses, \WP_Query $queryObject, array $paramValues ): void {
		/**
		 * Filters order statuses to exclude in Packeta order list filtering.
		 *
		 * @since 1.4
		 *
		 * @param array $orderStatusesToExclude Order statuses to exclude.
		 * @param \WP_Query $queryObject WP Query.
		 * @param array $paramValues Param values.
		 */
		$orderStatusesToExclude = (array) apply_filters( 'packetery_exclude_orders_with_status', [], $queryObject, $paramValues );
		if ( ! $orderStatusesToExclude ) {
			return;
		}

		$clauses['where'] .= sprintf( ' AND `%s`.`post_status` NOT IN (%s)', $this->wpdbAdapter->posts, $this->wpdbAdapter->prepareInClause( $orderStatusesToExclude ) );
	}

	/**
	 * Extends WP_Query to include custom table.
	 *
	 * @link https://wordpress.stackexchange.com/questions/50305/how-to-extend-wp-query-to-include-custom-table-in-query
	 *
	 * @param array     $clauses     Clauses.
	 * @param \WP_Query $queryObject WP_Query.
	 * @param array     $paramValues Param values.
	 *
	 * @return array
	 */
	public function processPostClauses( array $clauses, \WP_Query $queryObject, array $paramValues ): array {
		if ( isset( $queryObject->query['post_type'] ) &&
			(
				'shop_order' === $queryObject->query['post_type'] ||
				( is_array( $queryObject->query['post_type'] ) && in_array( 'shop_order', $queryObject->query['post_type'], true ) )
			)
		) {
			// TODO: Introduce variable.
			$clauses['join'] .= ' LEFT JOIN `' . $this->wpdbAdapter->packetery_order . '` ON `' . $this->wpdbAdapter->packetery_order . '`.`id` = `' . $this->wpdbAdapter->posts . '`.`id`';

			if ( $paramValues['packetery_carrier_id'] ) {
				$clauses['where'] .= ' AND `' . $this->wpdbAdapter->packetery_order . '`.`carrier_id` = "' . esc_sql( $paramValues['packetery_carrier_id'] ) . '"';
				$this->applyCustomFilters( $clauses, $queryObject, $paramValues );
			}
			if ( $paramValues['packetery_to_submit'] ) {
				$clauses['where'] .= ' AND `' . $this->wpdbAdapter->packetery_order . '`.`carrier_id` IS NOT NULL ';
				$clauses['where'] .= ' AND `' . $this->wpdbAdapter->packetery_order . '`.`is_exported` = false ';
				$this->applyCustomFilters( $clauses, $queryObject, $paramValues );
			}
			if ( $paramValues['packetery_to_print'] ) {
				$clauses['where'] .= ' AND `' . $this->wpdbAdapter->packetery_order . '`.`packet_id` IS NOT NULL ';
				$clauses['where'] .= ' AND `' . $this->wpdbAdapter->packetery_order . '`.`is_label_printed` = false ';
				$this->applyCustomFilters( $clauses, $queryObject, $paramValues );
			}
			if ( $paramValues['packetery_order_type'] ) {
				if ( Entity\Carrier::INTERNAL_PICKUP_POINTS_ID === $paramValues['packetery_order_type'] ) {
					$comparison = 'IN';
				} else {
					$comparison = 'NOT IN';
				}
				$internalCarriers   = array_keys( $this->carrierRepository->getNonFeedCarriers() );
				$internalCarriers[] = Entity\Carrier::INTERNAL_PICKUP_POINTS_ID;
				$clauses['where']  .= ' AND `' . $this->wpdbAdapter->packetery_order . '`.`carrier_id` ' . $comparison . ' (' . $this->wpdbAdapter->prepareInClause( $internalCarriers ) . ')';
				$this->applyCustomFilters( $clauses, $queryObject, $paramValues );
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
		$createTableQuery = 'CREATE TABLE ' . $this->wpdbAdapter->packetery_order . ' (
			`id` bigint(20) unsigned NOT NULL,
			`carrier_id` varchar(255) NOT NULL,
			`is_exported` tinyint(1) NOT NULL,
			`packet_id` varchar(255) NULL,
			`is_label_printed` tinyint(1) NOT NULL,
			`point_id` varchar(255) NULL,
			`point_name` varchar(255) NULL,
			`point_url` varchar(255) NULL,
			`point_street` varchar(255) NULL,
			`point_zip` varchar(255) NULL,
			`point_city` varchar(255) NULL,
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
			`carrier_number` varchar(255) NULL,
			`packet_status` varchar(255) NULL,
			`deliver_on` date NULL,
			PRIMARY KEY  (`id`)
		) ' . $this->wpdbAdapter->get_charset_collate();

		return $this->wpdbAdapter->dbDelta( $createTableQuery, $this->wpdbAdapter->packetery_order );
	}

	/**
	 * Drop table used to store orders.
	 */
	public function drop(): void {
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packetery_order . '`' );
	}

	/**
	 * Gets order data.
	 *
	 * @param int $id Order id.
	 *
	 * @return Order|null
	 * @throws InvalidCarrierException InvalidCarrierException.
	 */
	public function getById( int $id ): ?Order {
		$wcOrder = $this->getWcOrderById( $id );
		if ( null === $wcOrder ) {
			return null;
		}

		return $this->getByWcOrder( $wcOrder );
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
			SELECT `o`.* FROM `' . $this->wpdbAdapter->packetery_order . '` `o`
			JOIN `' . $this->wpdbAdapter->posts . '` AS `wp_p` ON `wp_p`.`ID` = `o`.`id` 
			WHERE `o`.`id` = %d',
				$id
			)
		);
	}

	/**
	 * Gets order by wc order.
	 *
	 * @param WC_Order $wcOrder WC Order.
	 *
	 * @return Order|null
	 * @throws InvalidCarrierException InvalidCarrierException.
	 */
	public function getByWcOrder( WC_Order $wcOrder ): ?Order {
		if ( ! $wcOrder->has_shipping_method( ShippingMethod::PACKETERY_METHOD_ID ) ) {
			return null;
		}

		$result = $this->getDataById( $wcOrder->get_id() );
		if ( ! $result ) {
			return null;
		}

		$partialOrder = $this->createPartialOrder( $result, Module\Helper::getWcOrderCountry( $wcOrder ) );
		return $this->builder->finalize( $wcOrder, $partialOrder );
	}

	/**
	 * Creates partial order.
	 *
	 * @param \stdClass $result  DB result.
	 * @param string    $country Lowercase country.
	 *
	 * @return Order
	 * @throws InvalidCarrierException InvalidCarrierException.
	 */
	private function createPartialOrder( \stdClass $result, string $country ): Order {
		if ( empty( $country ) ) {
			throw new InvalidCarrierException( __( 'Please set the country of the delivery address first.', 'packeta' ) );
		}
		$carrierId = $this->pickupPointsConfig->getFixedCarrierId( $result->carrier_id, $country );
		$carrier   = $this->carrierRepository->getAnyById( $carrierId );
		if ( null === $carrier ) {
			throw new InvalidCarrierException(
				sprintf(
				// translators: %s is carrier id.
					__( 'Order carrier is invalid (%s). Please contact Packeta support.', 'packeta' ),
					$carrierId
				)
			);
		}
		$partialOrder = new Order( $result->id, $carrier );
		$orderWeight  = $this->parseFloat( $result->weight );
		if ( null !== $orderWeight ) {
			$partialOrder->setWeight( $orderWeight );
		}
		$partialOrder->setPacketId( $result->packet_id );
		$partialOrder->setSize( new Size( $this->parseFloat( $result->length ), $this->parseFloat( $result->width ), $this->parseFloat( $result->height ) ) );
		$partialOrder->setIsExported( (bool) $result->is_exported );
		$partialOrder->setIsLabelPrinted( (bool) $result->is_label_printed );
		$partialOrder->setCarrierNumber( $result->carrier_number );
		$partialOrder->setPacketStatus( $result->packet_status );
		$partialOrder->setAddressValidated( (bool) $result->address_validated );
		$partialOrder->setAdultContent( $this->parseBool( $result->adult_content ) );
		$partialOrder->setValue( $this->parseFloat( $result->value ) );
		$partialOrder->setCod( $this->parseFloat( $result->cod ) );
		$partialOrder->setDeliverOn( $this->helper->getDateTimeFromString( $result->deliver_on ) );
		$partialOrder->setLastApiErrorMessage( $result->api_error_message );
		$partialOrder->setLastApiErrorDateTime(
			( null === $result->api_error_date )
				? null
				: \DateTimeImmutable::createFromFormat(
					Core\Helper::MYSQL_DATETIME_FORMAT,
					$result->api_error_date,
					new \DateTimeZone( 'UTC' )
				)->setTimezone( wp_timezone() )
		);

		if ( $result->delivery_address ) {
			$deliveryAddressDecoded = json_decode( $result->delivery_address, false );
			$deliveryAddress        = new Address(
				$deliveryAddressDecoded->street,
				$deliveryAddressDecoded->city,
				$deliveryAddressDecoded->zip
			);

			$deliveryAddress->setHouseNumber( $deliveryAddressDecoded->houseNumber );
			$deliveryAddress->setLongitude( $deliveryAddressDecoded->longitude );
			$deliveryAddress->setLatitude( $deliveryAddressDecoded->latitude );
			$deliveryAddress->setCounty( $deliveryAddressDecoded->county );

			$partialOrder->setDeliveryAddress( $deliveryAddress );
		}

		if ( null !== $result->point_id ) {
			$pickUpPoint = new PickupPoint(
				$result->point_id,
				$result->point_name,
				$result->point_city,
				$result->point_zip,
				$result->point_street,
				$result->point_url
			);

			$partialOrder->setPickupPoint( $pickUpPoint );
		}

		return $partialOrder;
	}

	/**
	 * Parses string value as float.
	 *
	 * @param string|float|null $value Value.
	 *
	 * @return float|null
	 */
	private function parseFloat( $value ): ?float {
		if ( null === $value || '' === $value ) {
			return null;
		}

		return (float) $value;
	}

	/**
	 * Parses string value as float.
	 *
	 * @param string|int|null $value Value.
	 *
	 * @return bool|null
	 */
	private function parseBool( $value ): ?bool {
		if ( null === $value || '' === $value ) {
			return null;
		}

		return (bool) $value;
	}

	/**
	 * Transforms order to DB array.
	 *
	 * @param Order $order Order.
	 *
	 * @return array
	 */
	private function orderToDbArray( Order $order ): array {
		$point = $order->getPickupPoint();
		if ( null === $point ) {
			$point = new PickupPoint();
		}

		$deliveryAddress = null;
		if ( $order->isAddressValidated() && $order->getDeliveryAddress() ) {
			$deliveryAddress = wp_json_encode( $order->getDeliveryAddress()->export() );
		}

		$apiErrorDateTime = $order->getLastApiErrorDateTime();
		if ( null !== $apiErrorDateTime ) {
			$apiErrorDateTime = $apiErrorDateTime->format( Core\Helper::MYSQL_DATETIME_FORMAT );
		}

		$data = [
			'id'                => (int) $order->getNumber(),
			'carrier_id'        => $order->getCarrier()->getId(),
			'is_exported'       => (int) $order->isExported(),
			'packet_id'         => $order->getPacketId(),
			'packet_status'     => $order->getPacketStatus(),
			'is_label_printed'  => (int) $order->isLabelPrinted(),
			'carrier_number'    => $order->getCarrierNumber(),
			'weight'            => $order->getWeight(),
			'point_id'          => $point->getId(),
			'point_name'        => $point->getName(),
			'point_url'         => $point->getUrl(),
			'point_street'      => $point->getStreet(),
			'point_zip'         => $point->getZip(),
			'point_city'        => $point->getCity(),
			'address_validated' => (int) $order->isAddressValidated(),
			'delivery_address'  => $deliveryAddress,
			'length'            => $order->getLength(),
			'width'             => $order->getWidth(),
			'height'            => $order->getHeight(),
			'adult_content'     => $order->containsAdultContent(),
			'cod'               => $order->getCod(),
			'value'             => $order->getValue(),
			'api_error_message' => $order->getLastApiErrorMessage(),
			'api_error_date'    => $apiErrorDateTime,
			'deliver_on'        => $this->helper->getStringFromDateTime( $order->getDeliverOn(), $this->helper::DATEPICKER_FORMAT ),
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
		$this->wpdbAdapter->insertReplaceHelper( $this->wpdbAdapter->packetery_order, $this->orderToDbArray( $order ), null, 'REPLACE' );
	}

	/**
	 * Loads order entities by list of ids.
	 *
	 * @param array $orderIds Order ids.
	 *
	 * @return Order[]
	 */
	public function getByIds( array $orderIds ): array {
		if ( empty( $orderIds ) ) {
			return [];
		}

		$wpdbAdapter           = $this->wpdbAdapter;
		$ordersIdsPlaceholder  = implode( ', ', array_fill( 0, count( $orderIds ), '%d' ) );
		$packeteryOrdersResult = $wpdbAdapter->get_results(
			$wpdbAdapter->prepare(
				'SELECT * FROM `' . $wpdbAdapter->packetery_order . '` 
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
			assert( null !== $wcOrder, 'WC order has to be present' );

			try {
				$partialOrder              = $this->createPartialOrder(
					$packeteryOrdersResult[ $orderId ],
					Module\Helper::getWcOrderCountry( $wcOrder )
				);
				$orderEntities[ $orderId ] = $this->builder->finalize( $wcOrder, $partialOrder );
			} catch ( InvalidCarrierException $exception ) {
				continue;
			}
		}

		return $orderEntities;
	}

	/**
	 * Finds orders.
	 *
	 * @param array $allowedPacketStatuses Allowed packet statuses.
	 * @param array $allowedOrderStatuses  Allowed order statuses.
	 * @param int   $maxDays               Max number of days of single packet sync.
	 * @param int   $limit                 Number of records.
	 *
	 * @return iterable|Order[]
	 */
	public function findStatusSyncingOrders( array $allowedPacketStatuses, array $allowedOrderStatuses, int $maxDays, int $limit ): iterable {
		$dateLimit = Core\Helper::now()->modify( '- ' . $maxDays . ' days' )->format( 'Y-m-d H:i:s' );

		$andWhere = [];

		$andWhere[] = 'o.`packet_id` IS NOT NULL';
		$andWhere[] = $this->wpdbAdapter->prepare( 'wp_p.`post_date_gmt` >= %s', $dateLimit );

		$orPacketStatus   = [];
		$orPacketStatus[] = 'o.`packet_status` IS NULL';

		if ( $allowedPacketStatuses ) {
			$orPacketStatus[] = '`o`.`packet_status` IN (' . $this->wpdbAdapter->prepareInClause( $allowedOrderStatuses ) . ')';
		}

		if ( $orPacketStatus ) {
			$andWhere[] = '(' . implode( ' OR ', $orPacketStatus ) . ')';
		}

		if ( $allowedOrderStatuses ) {
			$andWhere[] = '`wp_p`.`post_status` IN (' . $this->wpdbAdapter->prepareInClause( $allowedOrderStatuses ) . ')';
		} else {
			$andWhere[] = '1 = 0';
		}

		$where = '';
		if ( $andWhere ) {
			$where = ' WHERE ' . implode( ' AND ', $andWhere );
		}

		$sql = $this->wpdbAdapter->prepare(
			'
			SELECT o.* FROM `' . $this->wpdbAdapter->packetery_order . '` o 
			JOIN `' . $this->wpdbAdapter->posts . '` wp_p ON wp_p.`ID` = o.`id`
			' . $where . '
			ORDER BY wp_p.`post_date` 
			LIMIT %d',
			$limit
		);

		$rows = $this->wpdbAdapter->get_results( $sql );

		foreach ( $rows as $row ) {
			$wcOrder = $this->getWcOrderById( (int) $row->id );
			if ( null === $wcOrder || ! $wcOrder->has_shipping_method( ShippingMethod::PACKETERY_METHOD_ID ) ) {
				continue;
			}

			try {
				$partial = $this->createPartialOrder( $row, Module\Helper::getWcOrderCountry( $wcOrder ) );
				yield $this->builder->finalize( $wcOrder, $partial );
			} catch ( InvalidCarrierException $exception ) {
				continue;
			}
		}
	}

	/**
	 * Counts order to be submitted.
	 *
	 * @return int
	 */
	public function countOrdersToSubmit(): int {
		return (int) $this->wpdbAdapter->get_var(
			'SELECT COUNT(DISTINCT o.id) FROM `' . $this->wpdbAdapter->packetery_order . '` o 
			JOIN `' . $this->wpdbAdapter->posts . '` wp_p ON wp_p.`ID` = o.`id`
			WHERE o.`carrier_id` IS NOT NULL AND o.`is_exported` = false'
		);
	}

	/**
	 * Counts order to be printed.
	 *
	 * @return int
	 */
	public function countOrdersToPrint(): int {
		return (int) $this->wpdbAdapter->get_var(
			'SELECT COUNT(DISTINCT o.id) FROM `' . $this->wpdbAdapter->packetery_order . '` o 
			JOIN `' . $this->wpdbAdapter->posts . '` wp_p ON wp_p.`ID` = o.`id`
			WHERE o.`packet_id` IS NOT NULL AND o.`is_label_printed` = false'
		);
	}

	/**
	 * Deletes all custom table records linked to permanently deleted orders.
	 *
	 * @return void
	 */
	public function deleteOrphans(): void {
		$this->wpdbAdapter->query(
			'DELETE `' . $this->wpdbAdapter->packetery_order . '` FROM `' . $this->wpdbAdapter->packetery_order . '`
			LEFT JOIN `' . $this->wpdbAdapter->posts . '` ON `' . $this->wpdbAdapter->posts . '`.`ID` = `' . $this->wpdbAdapter->packetery_order . '`.`id`
			WHERE `' . $this->wpdbAdapter->posts . '`.`ID` IS NULL'
		);
	}

	/**
	 * Deletes data from custom table.
	 *
	 * @param int $orderId Order id.
	 *
	 * @return void
	 */
	private function delete( int $orderId ): void {
		$this->wpdbAdapter->delete( $this->wpdbAdapter->packetery_order, [ 'id' => $orderId ], '%d' );
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
		if ( 'shop_order' === $post->post_type ) {
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
