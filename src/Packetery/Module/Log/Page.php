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
			__( 'Log', PACKETERY_LANG_DOMAIN ),
			__( 'Log', PACKETERY_LANG_DOMAIN ),
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
			Record::ACTION_PACKET_SENDING            => __( 'Packet sending', PACKETERY_LANG_DOMAIN ),
			Record::ACTION_CARRIER_LABEL_PRINT       => __( 'Carrier label print', PACKETERY_LANG_DOMAIN ),
			Record::ACTION_LABEL_PRINT               => __( 'Label print', PACKETERY_LANG_DOMAIN ),
			Record::ACTION_CARRIER_LIST_UPDATE       => __( 'Carrier list update', PACKETERY_LANG_DOMAIN ),
			Record::ACTION_CARRIER_NUMBER_RETRIEVING => __( 'Getting external carrier tracking number', PACKETERY_LANG_DOMAIN ),
			Record::ACTION_CARRIER_TABLE_NOT_CREATED => __( 'Carrier table was not created', PACKETERY_LANG_DOMAIN ),
			Record::ACTION_ORDER_TABLE_NOT_CREATED   => __( 'Order table was not created', PACKETERY_LANG_DOMAIN ),
			Record::ACTION_SENDER_VALIDATION         => __( 'Sender validation', PACKETERY_LANG_DOMAIN ),
			Record::ACTION_PACKET_STATUS_SYNC        => __( 'Packet status synchronization', PACKETERY_LANG_DOMAIN ),
		];

		$translatedStatuses = [
			Record::STATUS_ERROR   => __( 'Error', PACKETERY_LANG_DOMAIN ),
			Record::STATUS_SUCCESS => __( 'Success', PACKETERY_LANG_DOMAIN ),
		];

		$rows = $this->logger->getRecords( [ 'date' => 'DESC' ] );

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/log/page.latte',
			[
				'rows'               => $rows,
				'translatedActions'  => $translatedActions,
				'translatedStatuses' => $translatedStatuses,
				'translations' => [
					'packeta'        => __( 'Packeta', PACKETERY_LANG_DOMAIN ),
					'logsPageTitle'  => __( 'Log', PACKETERY_LANG_DOMAIN ),
					'status'         => __( 'Status', PACKETERY_LANG_DOMAIN ),
					'dateAndTime'    => __( 'Date and time', PACKETERY_LANG_DOMAIN ),
					'action'         => __( 'Action', PACKETERY_LANG_DOMAIN ),
					'note'           => __( 'Note', PACKETERY_LANG_DOMAIN ),
					'logListIsEmpty' => __( 'Log list is empty', PACKETERY_LANG_DOMAIN ),
				],
			]
		);
	}
}
