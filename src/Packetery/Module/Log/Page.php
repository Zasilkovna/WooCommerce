<?php
/**
 * Class Page
 *
 * @package Packetery\Module\Log
 */

declare( strict_types=1 );


namespace Packetery\Module\Log;

use Packetery\Core\Log\ILogger;
use Packetery\Core\Log\Record;
use PacketeryLatte\Engine;

/**
 * Class Page
 *
 * @package Packetery\Module\Log
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
			__( 'Log', 'packeta' ),
			__( 'Log', 'packeta' ),
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
			Record::ACTION_PACKET_SENDING            => __( 'Packet sending', 'packeta' ),
			Record::ACTION_CARRIER_LABEL_PRINT       => __( 'Carrier label print', 'packeta' ),
			Record::ACTION_LABEL_PRINT               => __( 'Label print', 'packeta' ),
			Record::ACTION_CARRIER_LIST_UPDATE       => __( 'Carrier list update', 'packeta' ),
			Record::ACTION_CARRIER_NUMBER_RETRIEVING => __( 'Getting external carrier tracking number', 'packeta' ),
			Record::ACTION_CARRIER_TABLE_NOT_CREATED => __( 'Database carrier table was not created.', 'packeta' ),
			Record::ACTION_ORDER_TABLE_NOT_CREATED   => __( 'Database order table was not created.', 'packeta' ),
			Record::ACTION_SENDER_VALIDATION         => __( 'Sender validation', 'packeta' ),
			Record::ACTION_PACKET_STATUS_SYNC        => __( 'Packet status synchronization', 'packeta' ),
		];

		$translatedStatuses = [
			Record::STATUS_ERROR   => __( 'Error', 'packeta' ),
			Record::STATUS_SUCCESS => __( 'Success', 'packeta' ),
		];

		$rows = $this->logger->getRecords( [ 'date' => 'DESC' ] );

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/log/page.latte',
			[
				'rows'               => $rows,
				'translatedActions'  => $translatedActions,
				'translatedStatuses' => $translatedStatuses,
				'translations'       => [
					'packeta'        => __( 'Packeta', 'packeta' ),
					'logsPageTitle'  => __( 'Log', 'packeta' ),
					'status'         => __( 'Status', 'packeta' ),
					'dateAndTime'    => __( 'Date and time', 'packeta' ),
					'action'         => __( 'Action', 'packeta' ),
					'note'           => __( 'Note', 'packeta' ),
					'logListIsEmpty' => __( 'API log is empty.', 'packeta' ),
				],
			]
		);
	}
}
