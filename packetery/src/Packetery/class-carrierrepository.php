<?php
/**
 * Packeta carrier repository
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery;

/**
 * Class CarrierRepository
 *
 * @package Packetery
 */
class CarrierRepository {
	/**
	 * Create table to store carriers.
	 */
	public static function create() {
		global $wpdb;
		$wpdb->packetery_carrier = $wpdb->prefix . 'packetery_carrier';

		$wpdb->query(
			'CREATE TABLE IF NOT EXISTS `' . $wpdb->packetery_carrier . '` (
				`id` int NOT NULL,
				`name` varchar(255) NOT NULL,
				`is_pickup_points` boolean NOT NULL,
				`has_carrier_direct_label` boolean NOT NULL,
				`separate_house_number` boolean NOT NULL,
				`customs_declarations` boolean NOT NULL,
				`requires_email` boolean NOT NULL,
				`requires_phone` boolean NOT NULL,
				`requires_size` boolean NOT NULL,
				`disallows_cod` boolean NOT NULL,
				`country` varchar(255) NOT NULL,
				`currency` varchar(255) NOT NULL,
				`max_weight` float NOT NULL,
				`deleted` boolean NOT NULL,
				UNIQUE (`id`)
			) ENGINE=MyISAM;'
		);
	}

	/**
	 * Drop table used to store carriers.
	 */
	public static function drop() {
		global $wpdb;
		$wpdb->packetery_carrier = $wpdb->prefix . 'packetery_carrier';

		$wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->packetery_carrier . '`' );
	}

}
