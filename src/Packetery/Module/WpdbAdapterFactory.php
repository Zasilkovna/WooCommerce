<?php

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Module\Framework\WcAdapter;

class WpdbAdapterFactory {

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	public function __construct( WcAdapter $wcAdapter ) {
		$this->wcAdapter = $wcAdapter;
	}

	public function create(): WpdbAdapter {
		global $wpdb;

		$instance                                  = new WpdbAdapter( $wpdb, $this->wcAdapter );
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
