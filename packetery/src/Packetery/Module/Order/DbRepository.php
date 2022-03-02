<?php
/**
 * Class DbRepository.
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Module\Carrier;
use PacketeryNette\Http;

/**
 * Class DbRepository.
 *
 * @package Packetery\Module\Order
 */
class DbRepository {

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
	 * Repository constructor.
	 *
	 * @param \wpdb        $wpdb Wpdb.
	 * @param Http\Request $httpRequest Nette Request.
	 */
	public function __construct( \wpdb $wpdb, Http\Request $httpRequest ) {
		$this->wpdb        = $wpdb;
		$this->httpRequest = $httpRequest;
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
				`id` int NOT NULL,
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
				`weight` float NOT NULL,
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
	 * @return array|null
	 */
	public function getById( int $id ): ?array {
		$wpdb = $this->get_wpdb();

		$result = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . $wpdb->packetery_order . '` WHERE `id` = %d', $id ) );
		if ( $result ) {
			// It's usually stdClass.
			return (array) $result;
		}

		return null;
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

}
