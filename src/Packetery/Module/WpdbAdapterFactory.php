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

		$instance                                  = new WpdbAdapter( $wpdb );
		$instance->packeteryCarrier                = sprintf( '%scarrier', $instance->getPacketeryPrefix() );
		$instance->packeteryOrder                  = sprintf( '%sorder', $instance->getPacketeryPrefix() );
		$instance->packeteryLog                    = sprintf( '%slog', $instance->getPacketeryPrefix() );
		$instance->packeteryCustomsDeclaration     = sprintf( '%scustoms_declaration', $instance->getPacketeryPrefix() );
		$instance->packeteryCustomsDeclarationItem = sprintf( '%scustoms_declaration_item', $instance->getPacketeryPrefix() );
		$instance->wcOrders                        = sprintf( '%swc_orders', $wpdb->prefix );
		$instance->posts                           = $wpdb->posts;
		$instance->options                         = $wpdb->options;
		$instance->postmeta                        = $wpdb->postmeta;

		return $instance;
	}
}
