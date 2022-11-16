<?php
/**
 * Class WpdbFactory
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

/**
 * Class WpdbFactory
 *
 * @package Packetery
 */
class WpdbFactory {

	/**
	 * Gets wpdb instance
	 *
	 * @return Wpdb
	 */
	public function create(): Wpdb {
		global $wpdb;

		$instance = new Wpdb( $wpdb );

		$wpdb->packetery_carrier = sprintf( '%scarrier', $instance->getPacketeryPrefix() );
		$wpdb->packetery_order   = sprintf( '%sorder', $instance->getPacketeryPrefix() );
		$wpdb->packetery_log     = sprintf( '%slog', $instance->getPacketeryPrefix() );

		$instance->packetery_carrier = $wpdb->packetery_carrier;
		$instance->packetery_order   = $wpdb->packetery_order;
		$instance->packetery_log     = $wpdb->packetery_log;
		$instance->posts             = $wpdb->posts;
		$instance->options           = $wpdb->options;

		return $instance;
	}
}
