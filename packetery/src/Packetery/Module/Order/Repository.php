<?php
/**
 * Class Repository.
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Entity\Order;
use Packetery\Core\Entity\PickupPoint;
use Packetery\Core\Entity\Size;
use Packetery\Module\Carrier;
use Packetery\Module\EntityFactory;
use Packetery\Module\Options;
use Packetery\Module\Product;
use Packetery\Module\ShippingMethod;
use PacketeryNette\Http;

/**
 * Class Repository.
 *
 * @package Packetery\Module\Order
 */
class Repository {

	/**
	 * Wpdb.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Nette Request.
	 *
	 * @var Http\Request
	 */
	private $httpRequest;

	/**
	 * Order factory.
	 *
	 * @var EntityFactory\Order
	 */
	private $orderFactory;

	/**
	 * Options provider.
	 *
	 * @var Options\Provider
	 */
	private $optionsProvider;

	/**
	 * Repository constructor.
	 *
	 * @param \wpdb               $wpdb            Wpdb.
	 * @param Http\Request        $httpRequest     Nette Request.
	 * @param EntityFactory\Order $orderFactory    Order factory.
	 * @param Options\Provider    $optionsProvider Options provider.
	 */
	public function __construct( \wpdb $wpdb, Http\Request $httpRequest, EntityFactory\Order $orderFactory, Options\Provider $optionsProvider ) {
		$this->wpdb            = $wpdb;
		$this->httpRequest     = $httpRequest;
		$this->orderFactory    = $orderFactory;
		$this->optionsProvider = $optionsProvider;
	}

	/**
	 * Extends WP_Query to include custom table.
	 *
	 * @link https://wordpress.stackexchange.com/questions/50305/how-to-extend-wp-query-to-include-custom-table-in-query
	 *
	 * @param array     $clauses Clauses.
	 * @param \WP_Query $queryObject WP_Query.
	 *
	 * @return array
	 */
	public function postClausesFilter( array $clauses, \WP_Query $queryObject ): array {
		if ( isset( $queryObject->query['post_type'] ) &&
			(
				'shop_order' === $queryObject->query['post_type'] ||
				( is_array( $queryObject->query['post_type'] ) && in_array( 'shop_order', $queryObject->query['post_type'], true ) )
			)
		) {
			$clauses['join'] .= ' LEFT JOIN `' . $this->wpdb->packetery_order . '` ON `' . $this->wpdb->packetery_order . '`.`id` = `' . $this->wpdb->posts . '`.`id`';

			if ( $this->getParamValue( $queryObject, Entity::META_CARRIER_ID ) ) {
				$clauses['where'] .= ' AND `' . $this->wpdb->packetery_order . '`.`carrier_id` = "' . $this->wpdb->_real_escape( $this->getParamValue( $queryObject, Entity::META_CARRIER_ID ) ) . '"';
			}
			if ( $this->getParamValue( $queryObject, 'packetery_to_submit' ) ) {
				$clauses['where'] .= ' AND `' . $this->wpdb->packetery_order . '`.`carrier_id` IS NOT NULL ';
				$clauses['where'] .= ' AND `' . $this->wpdb->packetery_order . '`.`is_exported` = false ';
			}
			if ( $this->getParamValue( $queryObject, 'packetery_to_print' ) ) {
				$clauses['where'] .= ' AND `' . $this->wpdb->packetery_order . '`.`packet_id` IS NOT NULL ';
				$clauses['where'] .= ' AND `' . $this->wpdb->packetery_order . '`.`is_label_printed` = false ';
			}
			if ( $this->getParamValue( $queryObject, 'packetery_order_type' ) ) {
				if ( Carrier\Repository::INTERNAL_PICKUP_POINTS_ID === $this->getParamValue( $queryObject, 'packetery_order_type' ) ) {
					$clauses['where'] .= ' AND `' . $this->wpdb->packetery_order . '`.`carrier_id` = "' . $this->wpdb->_real_escape( Carrier\Repository::INTERNAL_PICKUP_POINTS_ID ) . '"';
				} else {
					$clauses['where'] .= ' AND `' . $this->wpdb->packetery_order . '`.`carrier_id` != "' . $this->wpdb->_real_escape( Carrier\Repository::INTERNAL_PICKUP_POINTS_ID ) . '"';
				}
			}
		}

		return $clauses;
	}

	/**
	 * Gets parameter value from GET data or WP_Query.
	 *
	 * @param \WP_Query $queryObject WP_Query.
	 * @param string    $key Key.
	 *
	 * @return mixed|null
	 */
	private function getParamValue( \WP_Query $queryObject, $key ) {
		$get = $this->httpRequest->getQuery();
		if ( isset( $get[ $key ] ) && '' !== (string) $get[ $key ] ) {
			return $get[ $key ];
		}
		if ( isset( $queryObject->query[ $key ] ) && '' !== (string) $queryObject->query[ $key ] ) {
			return $queryObject->query[ $key ];
		}

		return null;
	}

	/**
	 * Gets wpdb object from global variable with custom tablename set.
	 *
	 * @return \wpdb
	 */
	private function get_wpdb(): \wpdb {
		return $this->wpdb;
	}

	/**
	 * Create table to store orders.
	 *
	 * @return bool
	 */
	public function createTable(): bool {
		$wpdb = $this->get_wpdb();

		return $wpdb->query(
			'CREATE TABLE IF NOT EXISTS `' . $wpdb->packetery_order . '` (
				`id` bigint(20) unsigned NOT NULL,
				`carrier_id` varchar(255) NOT NULL,
				`is_exported` boolean NOT NULL,
				`packet_id` varchar(255) NULL,
				`is_label_printed` boolean NOT NULL,
				`point_id` varchar(255) NULL,
				`point_name` varchar(255) NULL,
				`point_url` varchar(255) NULL,
				`point_street` varchar(255) NULL,
				`point_zip` varchar(255) NULL,
				`point_city` varchar(255) NULL,
				`weight` float NULL,
				`length` float NULL,
				`width` float NULL,
				`height` float NULL,
				`carrier_number` varchar(255) NULL,
				`packet_status` varchar(255) NULL,
				PRIMARY KEY (`id`)
			) ' . $wpdb->get_charset_collate()
		);
	}

	/**
	 * Insert order data into db.
	 *
	 * @param array $data Order data.
	 *
	 * @return void
	 */
	public function insert( array $data ): void {
		$wpdb = $this->get_wpdb();
		$data = $this->removePrefixes( $data );
		$wpdb->insert( $wpdb->packetery_order, $data );
	}

	/**
	 * Updates order data in db.
	 *
	 * @param array $data Order data.
	 * @param int   $orderId Order id.
	 */
	public function update( array $data, int $orderId ): void {
		$wpdb = $this->get_wpdb();
		$data = $this->removePrefixes( $data );
		$wpdb->update( $wpdb->packetery_order, $data, [ 'id' => $orderId ] );
	}

	/**
	 * Drop table used to store orders.
	 */
	public function drop(): void {
		$wpdb = $this->get_wpdb();
		$wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->packetery_order . '`' );
	}

	/**
	 * Gets order data.
	 *
	 * @param int $id Order id.
	 *
	 * @return Order|null
	 */
	public function getById( int $id ): ?Order {
		$wcOrder = wc_get_order( $id );
		if ( ! $wcOrder instanceof \WC_Order ) {
			return null;
		}

		return $this->getByWcOrder( $wcOrder );
	}

	/**
	 * Gets order by wc order.
	 *
	 * @param \WC_Order $wcOrder WC Order.
	 *
	 * @return Order|null
	 */
	public function getByWcOrder( \WC_Order $wcOrder ): ?Order {
		if ( ! $wcOrder->has_shipping_method( ShippingMethod::PACKETERY_METHOD_ID ) ) {
			return null;
		}

		$wpdb = $this->get_wpdb();

		$result = $wpdb->get_row(
			$wpdb->prepare(
				'
			SELECT o.* FROM `' . $wpdb->packetery_order . '` o 
			JOIN `' . $wpdb->posts . '` as wp_p ON wp_p.ID = o.id 
			WHERE o.`id` = %d',
				$wcOrder->get_id()
			)
		);

		if ( ! $result ) {
			return null;
		}

		$partialOrder = $this->createPartialOrder( $wcOrder, $result );
		return $this->orderFactory->create( $wcOrder, $partialOrder );
	}

	/**
	 * Creates partial order.
	 *
	 * @param \WC_Order $wcOrder WC Order.
	 * @param \stdClass $result DB result.
	 *
	 * @return Order
	 */
	private function createPartialOrder( \WC_Order $wcOrder, \stdClass $result ): Order {
		$orderWeight = $this->parseFloat( $result->weight );
		if ( null === $orderWeight ) {
			$orderWeight = $this->calculateOrderWeight( $wcOrder );
		}

		$partialOrder = new Order(
			$result->id,
			null,
			null,
			$this->getTotalPrice( $wcOrder ),
			$orderWeight,
			null,
			$result->carrier_id
		);

		$partialOrder->setPacketId( $result->packet_id );
		$partialOrder->setSize( new Size( $this->parseFloat( $result->length ), $this->parseFloat( $result->width ), $this->parseFloat( $result->height ) ) );
		$partialOrder->setIsExported( (bool) $result->is_exported );
		$partialOrder->setIsLabelPrinted( (bool) $result->is_label_printed );
		$partialOrder->setCarrierNumber( $result->carrier_number );
		$partialOrder->setPacketStatus( $result->packet_status );
		$partialOrder->setAdultContent( $this->containsAdultContent( $wcOrder ) );

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
	 * Calculates order weight ignoring user specified weight.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return float
	 */
	public function calculateOrderWeight( \WC_Order $order ): float {
		$weight = 0;
		foreach ( $order->get_items() as $item ) {
			$quantity      = $item->get_quantity();
			$product       = $item->get_product();
			$productWeight = (float) $product->get_weight();
			$weight       += ( $productWeight * $quantity );
		}

		$weightKg = wc_get_weight( $weight, 'kg' );
		if ( $weightKg ) {
			$weightKg += $this->optionsProvider->getPackagingWeight();
		}

		return $weightKg;
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
			$point = new PickupPoint(
				null,
				null,
				null,
				null,
				null,
				null
			);
		}

		$data = [
			'id'               => (int) $order->getNumber(),
			'carrier_id'       => $order->getCarrierId(),
			'is_exported'      => (int) $order->isExported(),
			'packet_id'        => $order->getPacketId(),
			'packet_status'    => $order->getPacketStatus(),
			'is_label_printed' => (int) $order->isLabelPrinted(),
			'carrier_number'   => $order->getCarrierNumber(),
			'weight'           => $order->getWeight(),
			'point_id'         => $point->getId(),
			'point_name'       => $point->getName(),
			'point_url'        => $point->getUrl(),
			'point_street'     => $point->getStreet(),
			'point_zip'        => $point->getZip(),
			'point_city'       => $point->getCity(),
			'length'           => $order->getLength(),
			'width'            => $order->getWidth(),
			'height'           => $order->getHeight(),
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
		$this->wpdb->_insert_replace_helper( $this->wpdb->packetery_order, $this->orderToDbArray( $order ), null, 'REPLACE' );
	}

	/**
	 * Loads order entities by list of ids.
	 *
	 * @param array $orderIds Order ids.
	 *
	 * @return Order[]
	 */
	public function getByIds( array $orderIds ): array {
		$orderEntities = [];
		$posts         = get_posts(
			[
				'post_type'   => 'shop_order',
				'post__in'    => $orderIds,
				'post_status' => [ 'any', 'trash' ],
				'nopaging'    => true,
			]
		);
		foreach ( $posts as $post ) {
			$wcOrder = wc_get_order( $post );
			$order   = $this->getByWcOrder( $wcOrder );
			if ( $wcOrder && $order ) {
				$orderEntities[ $order->getNumber() ] = $order;
			}
		}

		return $orderEntities;
	}

	/**
	 * Finds orders.
	 *
	 * @param int $limit Number of records.
	 *
	 * @return iterable|Order[]
	 */
	public function findStatusSyncingPackets( int $limit ): iterable {
		$wpdb = $this->wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'
			SELECT o.* FROM `' . $wpdb->packetery_order . '` o 
			JOIN `' . $wpdb->posts . '` wp_p ON wp_p.`ID` = o.`id`
			WHERE ( o.`packet_status` IS NULL OR o.`packet_status` NOT IN ("delivered", "returned", "cancelled") ) AND o.`packet_id` IS NOT NULL
			ORDER BY wp_p.`post_date` 
			LIMIT %d',
				$limit
			)
		);

		foreach ( $rows as $row ) {
			$wcOrder = wc_get_order( $row->id );
			if ( ! $wcOrder->has_shipping_method( ShippingMethod::PACKETERY_METHOD_ID ) ) {
				continue;
			}

			$partial = $this->createPartialOrder( $wcOrder, $row );
			yield $this->orderFactory->create( $wcOrder, $partial );
		}
	}

	/**
	 * Removes packetery prefixes from data being saved to db.
	 *
	 * @param array $data Order data.
	 *
	 * @return array
	 */
	private function removePrefixes( array $data ): array {
		$newData = [];
		foreach ( $data as $key => $value ) {
			$newData[ $this->removePrefix( $key ) ] = $value;
		}

		return $newData;
	}

	/**
	 * Removes prefix if needed.
	 *
	 * @param string $string Key.
	 *
	 * @return string
	 */
	public function removePrefix( string $string ): string {
		$prefix = 'packetery_';
		if ( 0 === strpos( $string, $prefix ) ) {
			return substr( $string, strlen( $prefix ) );
		}

		return $string;
	}

	/**
	 * Finds out if adult content is present.
	 *
	 * @param \WC_Order $wcOrder WC Order.
	 *
	 * @return bool
	 */
	public function containsAdultContent( \WC_Order $wcOrder ): bool {
		foreach ( $wcOrder->get_items() as $item ) {
			$itemData      = $item->get_data();
			$productEntity = Product\Entity::fromPostId( $itemData['product_id'] );
			if ( $productEntity->isAgeVerification18PlusRequired() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Type cast of get_total result is needed, PHPDoc is wrong.
	 *
	 * @param \WC_Order $wcOrder WC Order.
	 *
	 * @return float
	 */
	public function getTotalPrice( \WC_Order $wcOrder ): float {
		return (float) $wcOrder->get_total( 'raw' );
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
				return __( 'packetStatusReceivedData', 'packetery' );
			case 'arrived':
				return __( 'packetStatusArrived', 'packetery' );
			case 'prepared for departure':
				return __( 'packetStatusPreparedForDeparture', 'packetery' );
			case 'departed':
				return __( 'packetStatusDeparted', 'packetery' );
			case 'ready for pickup':
				return __( 'packetStatusReadyForPickup', 'packetery' );
			case 'handed to carrier':
				return __( 'packetStatusHandedToCarrier', 'packetery' );
			case 'delivered':
				return __( 'packetStatusDelivered', 'packetery' );
			case 'posted back':
				return __( 'packetStatusPostedBack', 'packetery' );
			case 'returned':
				return __( 'packetStatusReturned', 'packetery' );
			case 'cancelled':
				return __( 'packetStatusCancelled', 'packetery' );
			case 'collected':
				return __( 'packetStatusCollected', 'packetery' );
			case 'unknown':
				return __( 'packetStatusUnknown', 'packetery' );
		}

		return (string) $packetStatus;
	}
}
