<?php
/**
 * Class Page
 *
 * @package Packetery\Log
 */

declare( strict_types=1 );


namespace PacketeryModule\Log;

use Packetery\Log\ILogger;
use Packetery\Log\Record;
use PacketeryLatte\Engine;

/**
 * Class Page
 *
 * @package Packetery\Log
 */
class Page {

	/**
	 * Engine.
	 *
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * Manager.
	 *
	 * @var ILogger
	 */
	private $logger;

	/**
	 * Page constructor.
	 *
	 * @param Engine  $latteEngine Engine.
	 * @param ILogger $manager     Manager.
	 */
	public function __construct( Engine $latteEngine, ILogger $manager ) {
		$this->latteEngine = $latteEngine;
		$this->logger      = $manager;
	}

	/**
	 * Registers Page.
	 */
	public function register(): void {
		add_submenu_page(
			'packeta-options',
			__( 'logsPageTitle', 'packetery' ),
			__( 'logsPageTitle', 'packetery' ),
			'manage_options',
			'packeta-logs',
			[ $this, 'render' ]
		);
	}

	/**
	 * Renders Page.
	 */
	public function render(): void {
		$translatedActions = [
			Record::ACTION_PACKET_SENDING      => __( 'logAction_packet-sending', 'packetery' ),
			Record::ACTION_CARRIER_LABEL_PRINT => __( 'logAction_carrier-label-print', 'packetery' ),
			Record::ACTION_LABEL_PRINT         => __( 'logAction_label-print', 'packetery' ),
			Record::ACTION_CARRIER_LIST_UPDATE => __( 'logAction_carrier-list-update', 'packetery' ),
		];

		$rows = $this->logger->getRecords( [ 'date' => 'DESC' ] );

		$this->latteEngine->render( PACKETERY_PLUGIN_DIR . '/template/log/page.latte', [ 'rows' => $rows, 'translatedActions' => $translatedActions ] );
	}
}
