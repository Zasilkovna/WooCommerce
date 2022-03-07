<?php
/**
 * Class Page
 *
 * @package Packetery\Log
 */

declare( strict_types=1 );


namespace Packetery\Module\Log;

use Packetery\Core\Log\ILogger;
use Packetery\Core\Log\Record;
use PacketeryLatte\Engine;

/**
 * Class Page
 *
 * @package Packetery\Log
 */
class Page {

	public const SLUG = 'packeta-logs';

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
			\Packetery\Module\Options\Page::SLUG,
			__( 'logsPageTitle', 'packetery' ),
			__( 'logsPageTitle', 'packetery' ),
			'manage_options',
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	/**
	 * Renders Page.
	 */
	public function render(): void {
		$translatedActions = [
			Record::ACTION_PACKET_SENDING            => __( 'logAction_packet-sending', 'packetery' ),
			Record::ACTION_CARRIER_LABEL_PRINT       => __( 'logAction_carrier-label-print', 'packetery' ),
			Record::ACTION_LABEL_PRINT               => __( 'logAction_label-print', 'packetery' ),
			Record::ACTION_CARRIER_LIST_UPDATE       => __( 'logAction_carrier-list-update', 'packetery' ),
			Record::ACTION_CARRIER_NUMBER_RETRIEVING => __( 'logAction_carrier-number-retrieving', 'packetery' ),
			Record::ACTION_CARRIER_TABLE_NOT_CREATED => __( 'logAction_carrier-table-not-created', 'packetery' ),
			Record::ACTION_ORDER_TABLE_NOT_CREATED   => __( 'logAction_order-table-not-created', 'packetery' ),
			Record::ACTION_SENDER_VALIDATION         => __( 'logAction_sender-validation', 'packetery' ),
			Record::ACTION_PACKET_STATUS_SYNC        => __( 'logAction_packet-status-sync', 'packetery' ),
		];

		$translatedStatuses = [
			Record::STATUS_ERROR   => __( 'statusError', 'packetery' ),
			Record::STATUS_SUCCESS => __( 'statusSuccess', 'packetery' ),
		];

		$rows = $this->logger->getRecords( [ 'date' => 'DESC' ] );

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/log/page.latte',
			[
				'rows'               => $rows,
				'translatedActions'  => $translatedActions,
				'translatedStatuses' => $translatedStatuses,
			]
		);
	}
}
