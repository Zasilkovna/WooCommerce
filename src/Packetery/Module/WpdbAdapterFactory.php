<?php
/**
 * Class WpdbAdapterFactory
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

/**
 * Class WpdbAdapterFactory
 *
 * @package Packetery
 */
class WpdbAdapterFactory {

	/**
	 * Creates WpdbAdapter instance
	 *
	 * @return WpdbAdapter
	 */
	public function create(): WpdbAdapter {
		global $wpdb;

		$instance                                     = new WpdbAdapter( $wpdb );
		$instance->packetery_carrier                  = sprintf( '%scarrier', $instance->getPacketeryPrefix() );
		$instance->packetery_order                    = sprintf( '%sorder', $instance->getPacketeryPrefix() );
		$instance->packetery_log                      = sprintf( '%slog', $instance->getPacketeryPrefix() );
		$instance->packetery_customs_declaration      = sprintf( '%scustoms_declaration', $instance->getPacketeryPrefix() );
		$instance->packetery_customs_declaration_item = sprintf( '%scustoms_declaration_item', $instance->getPacketeryPrefix() );
		$instance->wc_orders                          = sprintf( '%swc_orders', $wpdb->prefix );
		$instance->posts                              = $wpdb->posts;
		$instance->options                            = $wpdb->options;
		$instance->postmeta                           = $wpdb->postmeta;

		return $instance;
	}
}
