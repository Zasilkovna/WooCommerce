<?php
/**
 * Class Page
 *
 * @package Packetery\Module\Log
 */

declare( strict_types=1 );

namespace Packetery\Module\Log;

use Packetery\Core\Log\ILogger;
use Packetery\Core\Log\LogPageArguments;
use Packetery\Core\Log\Record;
use Packetery\Latte\Engine;
use Packetery\Module\Dashboard\DashboardPage;
use Packetery\Module\Forms\LogFilterFormFactory;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\MessageManager;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Views\UrlBuilder;
use Packetery\Nette\Http\Request;

/**
 * Class Page
 *
 * @package Packetery\Module\Log
 */
class Page {

	public const SLUG = 'packeta-logs';

	public const PARAM_ORDER_ID   = 'order_id';
	public const PARAM_LOG_ACTION = 'log_action';
	public const PARAM_STATUS     = 'status';
	public const PARAM_DATE_FROM  = 'date_from';
	public const PARAM_DATE_TO    = 'date_to';
	public const PARAM_SEARCH     = 'search';
	public const PARAM_PAGE       = 'paged';
	public const PARAM_DELETE_OLD = 'delete_old';

	private const NONCE_DELETE_OLD = 'packeta_delete_logs';

	private const ITEMS_PER_PAGE = 50;

	private Engine $latteEngine;
	private ILogger $logger;
	private Request $request;
	private ModuleHelper $moduleHelper;
	private UrlBuilder $urlBuilder;
	private WpAdapter $wpAdapter;
	private LogFilterFormFactory $logFilterForm;
	private MessageManager $messageManager;

	public function __construct(
		Engine $latteEngine,
		ILogger $manager,
		Request $request,
		ModuleHelper $moduleHelper,
		UrlBuilder $urlBuilder,
		WpAdapter $wpAdapter,
		LogFilterFormFactory $logFilterForm,
		MessageManager $messageManager
	) {
		$this->latteEngine    = $latteEngine;
		$this->logger         = $manager;
		$this->request        = $request;
		$this->moduleHelper   = $moduleHelper;
		$this->urlBuilder     = $urlBuilder;
		$this->wpAdapter      = $wpAdapter;
		$this->logFilterForm  = $logFilterForm;
		$this->messageManager = $messageManager;
	}

	/**
	 * Registers Page.
	 */
	public function register(): void {
		add_submenu_page(
			DashboardPage::SLUG,
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
		if ( $this->request->getQuery( self::PARAM_DELETE_OLD ) === '1' ) {
			$nonce = $this->request->getQuery( '_wpnonce' );
			if ( is_string( $nonce ) && $this->wpAdapter->verifyNonce( $nonce, self::NONCE_DELETE_OLD ) !== false ) {
				$this->deleteOldLogs();
				$this->messageManager->flash_message(
					$this->wpAdapter->__( 'Old logs deleted successfully.', 'packeta' )
				);
			}
			$this->wpAdapter->safeRedirect( $this->wpAdapter->removeQueryArg( [ self::PARAM_DELETE_OLD, '_wpnonce' ] ) );
			exit;
		}

		$translatedStatuses = [
			Record::STATUS_ERROR   => $this->wpAdapter->__( 'Error', 'packeta' ),
			Record::STATUS_SUCCESS => $this->wpAdapter->__( 'Success', 'packeta' ),
		];

		$orderId  = $this->getOrderId();
		$action   = $this->getAction();
		$status   = $this->getStatus();
		$dateFrom = $this->getDateFrom();
		$dateTo   = $this->getDateTo();
		$search   = $this->getSearch();
		$page     = $this->getPage();

		$dateQuery = [];
		if ( $dateFrom !== null ) {
			$dateQuery[] = [ 'after' => $dateFrom ];
		}
		if ( $dateTo !== null ) {
			$dateQuery[] = [ 'before' => $dateTo ];
		}

		$arguments  = new LogPageArguments( $orderId, $action, $status, $search, $dateQuery );
		$totalItems = $this->logger->countRecords( $arguments );
		$totalPages = (int) ceil( $totalItems / self::ITEMS_PER_PAGE );
		if ( $page > $totalPages && $totalPages > 0 ) {
			$page = $totalPages;
		}

		$arguments->setOrderBy( [ 'date' => 'DESC' ] );
		$arguments->setLimit( self::ITEMS_PER_PAGE );
		$arguments->setOffset( ( $page - 1 ) * self::ITEMS_PER_PAGE );
		$rows = $this->logger->getRecords( $arguments );

		$pagination = [
			'current'    => $page,
			'totalPages' => $totalPages,
			'totalItems' => $totalItems,
		];

		$defaults      = [
			'orderId'  => $orderId,
			'action'   => $action,
			'status'   => $status,
			'dateFrom' => $dateFrom,
			'dateTo'   => $dateTo,
			'search'   => $search,
		];
		$baseUrl       = $this->createPageUrl();
		$logFilterForm = $this->logFilterForm->create(
			$this->getTranslatedActions(),
			$translatedStatuses,
			$defaults,
			$baseUrl
		);

		$deleteUrl = $this->wpAdapter->addQueryArg(
			[
				self::PARAM_DELETE_OLD => '1',
				'_wpnonce'             => (string) $this->wpAdapter->createNonce( self::NONCE_DELETE_OLD ),
			]
		);

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/log/page.latte',
			[
				'rows'               => $rows,
				'translatedActions'  => $this->getTranslatedActions(),
				'translatedStatuses' => $translatedStatuses,
				'isCzechLocale'      => $this->moduleHelper->isCzechLocale(),
				'logoZasilkovna'     => $this->urlBuilder->buildAssetUrl( 'public/images/logo-zasilkovna.svg' ),
				'logoPacketa'        => $this->urlBuilder->buildAssetUrl( 'public/images/logo-packeta.svg' ),
				'paginationHtml'     => $this->getPaginationHtml( $pagination ),
				'deleteUrl'          => $deleteUrl,
				'logFilterForm'      => $logFilterForm,
				'translations'       => [
					'packeta'           => $this->wpAdapter->__( 'Packeta', 'packeta' ),
					'title'             => $this->wpAdapter->__( 'Log', 'packeta' ),
					'status'            => $this->wpAdapter->__( 'Status', 'packeta' ),
					'dateAndTime'       => $this->wpAdapter->__( 'Date and time', 'packeta' ),
					'action'            => $this->wpAdapter->__( 'Action', 'packeta' ),
					'note'              => $this->wpAdapter->__( 'Note', 'packeta' ),
					'logListIsEmpty'    => $this->wpAdapter->__( 'API log is empty.', 'packeta' ),
					'filter'            => $this->wpAdapter->__( 'Filter', 'packeta' ),
					'allActions'        => $this->wpAdapter->__( 'All actions', 'packeta' ),
					'allStatuses'       => $this->wpAdapter->__( 'All statuses', 'packeta' ),
					'dateFrom'          => $this->wpAdapter->__( 'Date from', 'packeta' ),
					'dateTo'            => $this->wpAdapter->__( 'Date to', 'packeta' ),
					'searchNote'        => $this->wpAdapter->__( 'Search in note', 'packeta' ),
					'deleteOldLogs'     => $this->wpAdapter->__( 'Delete logs older than 7 days', 'packeta' ),
					'deleteOldLogsConf' => $this->wpAdapter->__( 'Are you sure you want to delete logs older than 7 days?', 'packeta' ),
				],
			]
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function getTranslatedActions(): array {
		return [
			Record::ACTION_PACKET_SENDING            => $this->wpAdapter->__( 'Packet sending', 'packeta' ),
			Record::ACTION_PACKET_CLAIM_SENDING      => $this->wpAdapter->__( 'Packet claim sending', 'packeta' ),
			Record::ACTION_CARRIER_LABEL_PRINT       => $this->wpAdapter->__( 'Carrier label printing', 'packeta' ),
			Record::ACTION_LABEL_PRINT               => $this->wpAdapter->__( 'Label printing', 'packeta' ),
			Record::ACTION_CLAIM_LABEL_PRINT         => $this->wpAdapter->__( 'Claim assistant label printing', 'packeta' ),
			Record::ACTION_CARRIER_LIST_UPDATE       => $this->wpAdapter->__( 'Carrier list update', 'packeta' ),
			Record::ACTION_CARRIER_NUMBER_RETRIEVING => $this->wpAdapter->__( 'Getting external carrier tracking number', 'packeta' ),
			Record::ACTION_CARRIER_TABLE_NOT_CREATED => $this->wpAdapter->__( 'Database carrier table was not created.', 'packeta' ),
			Record::ACTION_ORDER_TABLE_NOT_CREATED   => $this->wpAdapter->__( 'Database order table was not created.', 'packeta' ),
			Record::ACTION_SENDER_VALIDATION         => $this->wpAdapter->__( 'Sender validation', 'packeta' ),
			Record::ACTION_PACKET_STATUS_SYNC        => $this->wpAdapter->__( 'Packet status synchronization', 'packeta' ),
			Record::ACTION_PACKET_CANCEL             => $this->wpAdapter->__( 'Packet cancel', 'packeta' ),
			Record::ACTION_PICKUP_POINT_VALIDATE     => $this->wpAdapter->__( 'Pickup point validation', 'packeta' ),
			Record::ACTION_ORDER_STATUS_CHANGE       => $this->wpAdapter->__( 'Order status change', 'packeta' ),
		];
	}

	private function deleteOldLogs(): void {
		$this->logger->deleteOld( '-7 days' );
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
		$action = $this->request->getQuery( self::PARAM_LOG_ACTION );
		if ( $action !== null && $action !== '' ) {
			return (string) $action;
		}

		return null;
	}

	private function getStatus(): ?string {
		$status = $this->request->getQuery( self::PARAM_STATUS );
		if ( $status !== null && $status !== '' ) {
			return (string) $status;
		}

		return null;
	}

	private function getDateFrom(): ?string {
		$date = $this->request->getQuery( self::PARAM_DATE_FROM );
		if ( $date !== null && $date !== '' ) {
			return (string) $date;
		}

		return null;
	}

	private function getDateTo(): ?string {
		$date = $this->request->getQuery( self::PARAM_DATE_TO );
		if ( $date !== null && $date !== '' ) {
			return (string) $date;
		}

		return null;
	}

	private function getSearch(): ?string {
		$search = $this->request->getQuery( self::PARAM_SEARCH );
		if ( $search !== null && $search !== '' ) {
			return (string) $search;
		}

		return null;
	}

	private function getPage(): int {
		$page = $this->request->getQuery( self::PARAM_PAGE );
		if ( is_numeric( $page ) && (int) $page > 0 ) {
			return (int) $page;
		}

		return 1;
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

	private function countRows( ?int $orderId = null ): int {
		return $this->logger->countRecords( new LogPageArguments( $orderId ) );
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

		if ( $orderId !== null ) {
			$params[ self::PARAM_ORDER_ID ] = $orderId;
		}
		if ( $action !== null ) {
			$params[ self::PARAM_LOG_ACTION ] = $action;
		}

		return $this->wpAdapter->addQueryArg(
			$params,
			admin_url( 'admin.php' )
		);
	}

	private function createPageUrl(): string {
		$params = [
			'page' => self::SLUG,
		];

		$queryKeys = [
			self::PARAM_ORDER_ID,
			self::PARAM_LOG_ACTION,
			self::PARAM_STATUS,
			self::PARAM_DATE_FROM,
			self::PARAM_DATE_TO,
			self::PARAM_SEARCH,
		];

		foreach ( $queryKeys as $key ) {
			$value = $this->request->getQuery( $key );
			if ( $value !== null && $value !== '' ) {
				$params[ $key ] = $value;
			}
		}

		return $this->wpAdapter->addQueryArg(
			$params,
			admin_url( 'admin.php' )
		);
	}

	/**
	 * @param array<string, int> $pagination
	 */
	private function getPaginationHtml( array $pagination ): string {
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}

		$table = new ListTableFork();
		$table->setPaginationArgs(
			[
				'total_items' => $pagination['totalItems'],
				'per_page'    => self::ITEMS_PER_PAGE,
				'total_pages' => $pagination['totalPages'],
				'paged'       => $pagination['current'],
			]
		);

		ob_start();
		$table->renderPagination( 'top' );

		return (string) ob_get_clean();
	}
}
