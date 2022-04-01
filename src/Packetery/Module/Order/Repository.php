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
use Packetery\Core\Entity\Address;
use Packetery\Module\Calculator;
use Packetery\Module\Carrier;
use Packetery\Module\Product;
use Packetery\Module\ShippingMethod;
use WP_Post;

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
	 * Order factory.
	 *
	 * @var Builder
	 */
	private $builder;

	/**
	 * Calculator.
	 *
	 * @var Calculator
	 */
	private $calculator;

	/**
	 * Repository constructor.
	 *
	 * @param \wpdb      $wpdb         Wpdb.
	 * @param Builder    $orderFactory Order factory.
	 * @param Calculator $calculator   Calculator.
	 */
	public function __construct( \wpdb $wpdb, Builder $orderFactory, Calculator $calculator ) {
		$this->wpdb       = $wpdb;
		$this->builder    = $orderFactory;
		$this->calculator = $calculator;
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
			$clauses['join'] .= ' LEFT JOIN `' . $this->wpdb->packetery_order . '` ON `' . $this->wpdb->packetery_order . '`.`id` = `' . $this->wpdb->posts . '`.`id`';

			if ( $paramValues['packetery_carrier_id'] ) {
				$clauses['where'] .= ' AND `' . $this->wpdb->packetery_order . '`.`carrier_id` = "' . $this->wpdb->_real_escape( $paramValues['packetery_carrier_id'] ) . '"';
			}
			if ( $paramValues['packetery_to_submit'] ) {
				$clauses['where'] .= ' AND `' . $this->wpdb->packetery_order . '`.`carrier_id` IS NOT NULL ';
				$clauses['where'] .= ' AND `' . $this->wpdb->packetery_order . '`.`is_exported` = false ';
			}
			if ( $paramValues['packetery_to_print'] ) {
				$clauses['where'] .= ' AND `' . $this->wpdb->packetery_order . '`.`packet_id` IS NOT NULL ';
				$clauses['where'] .= ' AND `' . $this->wpdb->packetery_order . '`.`is_label_printed` = false ';
			}
			if ( $paramValues['packetery_order_type'] ) {
				if ( Carrier\Repository::INTERNAL_PICKUP_POINTS_ID === $paramValues['packetery_order_type'] ) {
					$clauses['where'] .= ' AND `' . $this->wpdb->packetery_order . '`.`carrier_id` = "' . $this->wpdb->_real_escape( Carrier\Repository::INTERNAL_PICKUP_POINTS_ID ) . '"';
				} else {
					$clauses['where'] .= ' AND `' . $this->wpdb->packetery_order . '`.`carrier_id` != "' . $this->wpdb->_real_escape( Carrier\Repository::INTERNAL_PICKUP_POINTS_ID ) . '"';
				}
			}
		}

		return $clauses;
	}

	/**
	 * Create table to store orders.
	 *
	 * @return bool
	 */
	public function createTable(): bool {
		$wpdb = $this->wpdb;

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
				`address_validated` boolean NOT NULL,
				`delivery_address` TEXT NULL,
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
	 * Drop table used to store orders.
	 */
	public function drop(): void {
		$wpdb = $this->wpdb;
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

		$wpdb = $this->wpdb;

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
		return $this->builder->finalize( $wcOrder, $partialOrder );
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
			$orderWeight = $this->calculator->calculateOrderWeight( $wcOrder );
		}

		$partialOrder = new Order(
			$result->id,
			$result->carrier_id
		);

		$partialOrder->setWeight( $orderWeight );
		$partialOrder->setPacketId( $result->packet_id );
		$partialOrder->setSize( new Size( $this->parseFloat( $result->length ), $this->parseFloat( $result->width ), $this->parseFloat( $result->height ) ) );
		$partialOrder->setIsExported( (bool) $result->is_exported );
		$partialOrder->setIsLabelPrinted( (bool) $result->is_label_printed );
		$partialOrder->setCarrierNumber( $result->carrier_number );
		$partialOrder->setPacketStatus( $result->packet_status );
		$partialOrder->setAdultContent( $this->containsAdultContent( $wcOrder ) );
		$partialOrder->setAddressValidated( (bool) $result->address_validated );

		if ( $result->delivery_address ) {
			$deliveryAddressDecoded = json_decode( $result->delivery_address );
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

		$data = [
			'id'                => (int) $order->getNumber(),
			'carrier_id'        => $order->getCarrierId(),
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
			// In case WC_Order does not exist, result set is limited to existing records.
			if ( $wcOrder ) {
				$order = $this->getByWcOrder( $wcOrder );
				if ( $order ) {
					$orderEntities[ $order->getNumber() ] = $order;
				}
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
	public function findStatusSyncingOrders( int $limit ): iterable {
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
			if ( false === $wcOrder || ! $wcOrder->has_shipping_method( ShippingMethod::PACKETERY_METHOD_ID ) ) {
				continue;
			}

			$partial = $this->createPartialOrder( $wcOrder, $row );
			yield $this->builder->finalize( $wcOrder, $partial );
		}
	}

	/**
	 * Finds out if adult content is present.
	 *
	 * @param \WC_Order $wcOrder WC Order.
	 *
	 * @return bool
	 */
	private function containsAdultContent( \WC_Order $wcOrder ): bool {
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
	 * Counts order to be submitted.
	 *
	 * @return int
	 */
	public function countOrdersToSubmit(): int {
		$wpdb = $this->wpdb;

		return (int) $wpdb->get_var(
			'SELECT COUNT(DISTINCT o.id) FROM `' . $wpdb->packetery_order . '` o 
			JOIN `' . $wpdb->posts . '` wp_p ON wp_p.`ID` = o.`id`
			WHERE o.`carrier_id` IS NOT NULL AND o.`is_exported` = false'
		);
	}

	/**
	 * Counts order to be printed.
	 *
	 * @return int
	 */
	public function countOrdersToPrint(): int {
		$wpdb = $this->wpdb;

		return (int) $wpdb->get_var(
			'SELECT COUNT(DISTINCT o.id) FROM `' . $wpdb->packetery_order . '` o 
			JOIN `' . $wpdb->posts . '` wp_p ON wp_p.`ID` = o.`id`
			WHERE o.`packet_id` IS NOT NULL AND o.`is_label_printed` = false'
		);
	}

	/**
	 * Deletes all custom table records linked to permanently deleted orders.
	 *
	 * @return void
	 */
	public function deleteOrphans(): void {
		$wpdb = $this->wpdb;

		$wpdb->query(
			'DELETE `' . $wpdb->packetery_order . '` FROM `' . $wpdb->packetery_order . '`
			LEFT JOIN `' . $wpdb->posts . '` ON `' . $wpdb->posts . '`.`ID` = `' . $wpdb->packetery_order . '`.`id`
			WHERE `' . $wpdb->posts . '`.`ID` IS NULL'
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
		$wpdb = $this->wpdb;

		$wpdb->delete( $wpdb->packetery_order, [ 'id' => $orderId ], '%d' );
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

}
