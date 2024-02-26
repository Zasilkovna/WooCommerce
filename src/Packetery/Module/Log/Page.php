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
use Packetery\Latte\Engine;
use Packetery\Nette\Http\Request;

/**
 * Class Page
 *
 * @package Packetery\Module\Log
 */
class Page {

	public const SLUG = 'packeta-logs';

	public const PARAM_ORDER_ID = 'order_id';
	public const PARAM_ACTION   = 'action';

	private const MAX_ROWS = 500;

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
	 * HTTP request.
	 *
	 * @var Request
	 */
	private $request;

	/**
	 * Page constructor.
	 *
	 * @param Engine  $latteEngine Engine.
	 * @param ILogger $manager     Manager.
	 * @param Request $request     Request.
	 */
	public function __construct( Engine $latteEngine, ILogger $manager, Request $request ) {
		$this->latteEngine = $latteEngine;
		$this->logger      = $manager;
		$this->request     = $request;
	}

	/**
	 * Registers Page.
	 */
	public function register(): void {
		add_submenu_page(
			\Packetery\Module\Options\Page::SLUG,
			__( 'Log', 'packeta' ),
			__( 'Log', 'packeta' ),
			'manage_woocommerce',
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
			Record::ACTION_PACKET_CLAIM_SENDING      => __( 'Packet claim sending', 'packeta' ),
			Record::ACTION_CARRIER_LABEL_PRINT       => __( 'Carrier label print', 'packeta' ),
			Record::ACTION_LABEL_PRINT               => __( 'Label print', 'packeta' ),
			Record::ACTION_CARRIER_LIST_UPDATE       => __( 'Carrier list update', 'packeta' ),
			Record::ACTION_CARRIER_NUMBER_RETRIEVING => __( 'Getting external carrier tracking number', 'packeta' ),
			Record::ACTION_CARRIER_TABLE_NOT_CREATED => __( 'Database carrier table was not created.', 'packeta' ),
			Record::ACTION_ORDER_TABLE_NOT_CREATED   => __( 'Database order table was not created.', 'packeta' ),
			Record::ACTION_SENDER_VALIDATION         => __( 'Sender validation', 'packeta' ),
			Record::ACTION_PACKET_STATUS_SYNC        => __( 'Packet status synchronization', 'packeta' ),
			Record::ACTION_PACKET_CANCEL             => __( 'Packet cancel', 'packeta' ),
			Record::ACTION_PICKUP_POINT_VALIDATE     => __( 'Pickup point validation', 'packeta' ),
			Record::ACTION_ORDER_STATUS_CHANGE       => __( 'Order status change', 'packeta' ),
		];

		$translatedStatuses = [
			Record::STATUS_ERROR   => __( 'Error', 'packeta' ),
			Record::STATUS_SUCCESS => __( 'Success', 'packeta' ),
		];

		$orderId    = $this->getOrderId();
		$action     = $this->getAction();
		$rows       = $this->logger->getRecords( $orderId, $action, [ 'date' => 'DESC' ], self::MAX_ROWS );
		$totalCount = $this->countRows( $orderId, $action );

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/log/page.latte',
			[
				'rows'               => $rows,
				'maxRowsExceeded'    => $totalCount > self::MAX_ROWS,
				'translatedActions'  => $translatedActions,
				'translatedStatuses' => $translatedStatuses,
				'translations'       => [
					'packeta'        => __( 'Packeta', 'packeta' ),
					'title'          => __( 'Log', 'packeta' ),
					'status'         => __( 'Status', 'packeta' ),
					'dateAndTime'    => __( 'Date and time', 'packeta' ),
					'action'         => __( 'Action', 'packeta' ),
					'note'           => __( 'Note', 'packeta' ),
					'logListIsEmpty' => __( 'API log is empty.', 'packeta' ),
					// translators: 1: Total row count 2: Displayed row count.
					'moreRowsNotice' => sprintf( __( 'Total row count: %1$d. Number of displayed rows: %2$d.', 'packeta' ), $totalCount, self::MAX_ROWS ),
				],
			]
		);
	}

	/**
	 * Returns order ID.
	 *
	 * @return int|null
	 */
	private function getOrderId(): ?int {
		$orderId = $this->request->getQuery( self::PARAM_ORDER_ID );
		if ( is_numeric( $orderId ) ) {
			return (int) $orderId;
		}

		return null;
	}

	/**
	 * Returns action.
	 *
	 * @return string|null
	 */
	private function getAction(): ?string {
		$action = $this->request->getQuery( self::PARAM_ACTION );
		if ( ! empty( $action ) ) {
			return (string) $action;
		}

		return null;
	}

	/**
	 * Tells if log page displays at least one row.
	 *
	 * @param int|null $orderId Order ID.
	 *
	 * @return bool
	 */
	public function hasAnyRows( ?int $orderId ): bool {
		return $this->countRows( $orderId ) > 0;
	}

	/**
	 * Counts rows.
	 *
	 * @param int|null    $orderId Order ID.
	 * @param string|null $action  Action.
	 *
	 * @return int
	 */
	private function countRows( ?int $orderId = null, ?string $action = null ): int {
		return $this->logger->countRecords( $orderId, $action );
	}

	/**
	 * Creates link to log page.
	 *
	 * @param int|null    $orderId Order ID.
	 * @param string|null $action  Action.
	 *
	 * @return string
	 */
	public function createLogListUrl( ?int $orderId = null, ?string $action = null ): string {
		$params = [
			'page' => self::SLUG,
		];

		if ( null !== $orderId ) {
			$params[ self::PARAM_ORDER_ID ] = $orderId;
		}
		if ( null !== $action ) {
			$params[ self::PARAM_ACTION ] = $action;
		}

		return add_query_arg(
			$params,
			admin_url( 'admin.php' )
		);
	}
}
