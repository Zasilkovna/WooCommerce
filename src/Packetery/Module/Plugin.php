<?php
/**
 * Main Packeta plugin class.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Core\Log\ILogger;
use Packetery\Core\Log\Record;
use Packetery\Module\Carrier\Downloader;
use Packetery\Module\Carrier\OptionsPage;
use Packetery\Module\Carrier\Repository;
use Packetery\Module\Log;
use Packetery\Module\Options;
use Packetery\Module\Order;
use Packetery\Module\Product;
use PacketeryLatte\Engine;
use PacketeryNette\Http\Request;
use WC_Order;

/**
 * Class Plugin
 *
 * @package Packetery
 */
class Plugin {

	public const VERSION               = '1.2.5';
	public const DOMAIN                = 'packetery';
	public const MIN_LISTENER_PRIORITY = -9998;

	/**
	 * Options page.
	 *
	 * @var Options\Page Options page,
	 */
	private $options_page;

	/**
	 * PacketeryLatte engine.
	 *
	 * @var Engine
	 */
	private $latte_engine;

	/**
	 * Carrier downloader object.
	 *
	 * @var Downloader
	 */
	private $carrier_downloader;

	/**
	 * Carrier repository.
	 *
	 * @var Repository
	 */
	private $carrierRepository;

	/**
	 * Country options page.
	 *
	 * @var OptionsPage
	 */
	private $carrierOptionsPage;

	/**
	 * Path to main plugin file.
	 *
	 * @var string Path to main plugin file.
	 */
	private $main_file_path;

	/**
	 * Admin edit order page Packeta metabox.
	 *
	 * @var Order\Metabox
	 */
	private $order_metabox;

	/**
	 * Message manager.
	 *
	 * @var MessageManager
	 */
	private $message_manager;

	/**
	 * Checkout object.
	 *
	 * @var Checkout
	 */
	private $checkout;

	/**
	 * Order BulkActions.
	 *
	 * @var Order\BulkActions
	 */
	private $orderBulkActions;

	/**
	 * Label printing.
	 *
	 * @var Order\LabelPrint
	 */
	private $labelPrint;

	/**
	 * Order collection printing.
	 *
	 * @var Order\CollectionPrint
	 */
	private $orderCollectionPrint;

	/**
	 * Order grid extender.
	 *
	 * @var Order\GridExtender
	 */
	private $gridExtender;

	/**
	 * Product tab.
	 *
	 * @var Product\DataTab
	 */
	private $productTab;

	/**
	 * Log page.
	 *
	 * @var Log\Page
	 */
	private $logPage;

	/**
	 * Log manager.
	 *
	 * @var ILogger
	 */
	private $logger;

	/**
	 * Order controller.
	 *
	 * @var Order\Controller
	 */
	private $orderController;

	/**
	 * Order modal.
	 *
	 * @var Order\Modal
	 */
	private $orderModal;

	/**
	 * Options exporter.
	 *
	 * @var Options\Exporter
	 */
	private $exporter;

	/**
	 * Packet synchronizer.
	 *
	 * @var Order\PacketSynchronizer
	 */
	private $packetSynchronizer;

	/**
	 * Request.
	 *
	 * @var Request
	 */
	private $request;

	/**
	 * Order repository.
	 *
	 * @var Order\Repository
	 */
	private $orderRepository;

	/**
	 * Plugin upgrade.
	 *
	 * @var Upgrade
	 */
	private $upgrade;

	/**
	 * QueryProcessor.
	 *
	 * @var QueryProcessor
	 */
	private $queryProcessor;

	/**
	 * Log repository.
	 *
	 * @var Log\Repository
	 */
	private $logRepository;

	/**
	 * Plugin constructor.
	 *
	 * @param Order\Metabox            $order_metabox        Order metabox.
	 * @param MessageManager           $message_manager      Message manager.
	 * @param Options\Page             $options_page         Options page.
	 * @param Repository               $carrierRepository    Carrier repository.
	 * @param Downloader               $carrier_downloader   Carrier downloader object.
	 * @param Checkout                 $checkout             Checkout class.
	 * @param Engine                   $latte_engine         PacketeryLatte engine.
	 * @param OptionsPage              $carrierOptionsPage   Carrier options page.
	 * @param Order\BulkActions        $orderBulkActions     Order BulkActions.
	 * @param Order\LabelPrint         $labelPrint           Label printing.
	 * @param Order\GridExtender       $gridExtender         Order grid extender.
	 * @param Product\DataTab          $productTab           Product tab.
	 * @param Log\Page                 $logPage              Log page.
	 * @param ILogger                  $logger               Log manager.
	 * @param Order\Controller         $orderController      Order controller.
	 * @param Order\Modal              $orderModal           Order modal.
	 * @param Options\Exporter         $exporter             Options exporter.
	 * @param Order\CollectionPrint    $orderCollectionPrint Order collection print.
	 * @param Order\PacketSynchronizer $packetSynchronizer   Packet synchronizer.
	 * @param Request                  $request              HTTP request.
	 * @param Order\Repository         $orderRepository      Order repository.
	 * @param Upgrade                  $upgrade              Plugin upgrade.
	 * @param QueryProcessor           $queryProcessor       QueryProcessor.
	 * @param Log\Repository           $logRepository        Log repository.
	 */
	public function __construct(
		Order\Metabox $order_metabox,
		MessageManager $message_manager,
		Options\Page $options_page,
		Repository $carrierRepository,
		Downloader $carrier_downloader,
		Checkout $checkout,
		Engine $latte_engine,
		OptionsPage $carrierOptionsPage,
		Order\BulkActions $orderBulkActions,
		Order\LabelPrint $labelPrint,
		Order\GridExtender $gridExtender,
		Product\DataTab $productTab,
		Log\Page $logPage,
		ILogger $logger,
		Order\Controller $orderController,
		Order\Modal $orderModal,
		Options\Exporter $exporter,
		Order\CollectionPrint $orderCollectionPrint,
		Order\PacketSynchronizer $packetSynchronizer,
		Request $request,
		Order\Repository $orderRepository,
		Upgrade $upgrade,
		QueryProcessor $queryProcessor,
		Log\Repository $logRepository
	) {
		$this->options_page         = $options_page;
		$this->latte_engine         = $latte_engine;
		$this->carrierRepository    = $carrierRepository;
		$this->carrier_downloader   = $carrier_downloader;
		$this->main_file_path       = PACKETERY_PLUGIN_DIR . '/packeta.php';
		$this->order_metabox        = $order_metabox;
		$this->message_manager      = $message_manager;
		$this->checkout             = $checkout;
		$this->carrierOptionsPage   = $carrierOptionsPage;
		$this->orderBulkActions     = $orderBulkActions;
		$this->labelPrint           = $labelPrint;
		$this->gridExtender         = $gridExtender;
		$this->productTab           = $productTab;
		$this->logPage              = $logPage;
		$this->logger               = $logger;
		$this->orderController      = $orderController;
		$this->orderModal           = $orderModal;
		$this->exporter             = $exporter;
		$this->orderCollectionPrint = $orderCollectionPrint;
		$this->packetSynchronizer   = $packetSynchronizer;
		$this->request              = $request;
		$this->orderRepository      = $orderRepository;
		$this->upgrade              = $upgrade;
		$this->queryProcessor       = $queryProcessor;
		$this->logRepository        = $logRepository;
	}

	/**
	 * Method to register hooks
	 */
	public function run(): void {
		add_action( 'init', [ $this, 'loadTranslation' ] );

		if ( ! self::isWooCommercePluginActive() ) {
			add_action( 'admin_notices', [ $this, 'echoInactiveWooCommerceNotice' ] );

			return;
		}

		add_action( 'init', [ $this->upgrade, 'check' ] );
		add_action( 'init', [ $this->logger, 'register' ] );
		add_action( 'init', [ $this->message_manager, 'init' ] );
		add_action( 'rest_api_init', [ $this->orderController, 'registerRoutes' ] );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminAssets' ) );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueFrontAssets' ] );

		add_action(
			'admin_notices',
			function () {
				$this->message_manager->render( MessageManager::RENDERER_WORDPRESS );
			},
			self::MIN_LISTENER_PRIORITY
		);
		add_action( 'init', array( $this, 'init' ) );

		register_activation_hook( $this->main_file_path, array( $this, 'activate' ) );

		// TODO: deactivation_hook.
		register_deactivation_hook(
			$this->main_file_path,
			static function () {
			}
		);

		register_uninstall_hook( $this->main_file_path, array( __CLASS__, 'uninstall' ) );

		add_action( 'woocommerce_email_footer', array( $this, 'render_email_footer' ) );
		add_filter( 'woocommerce_shipping_methods', array( $this, 'add_shipping_method' ) );

		add_filter( 'views_edit-shop_order', [ $this->gridExtender, 'addFilterLinks' ] );
		add_action( 'restrict_manage_posts', [ $this->gridExtender, 'renderOrderTypeSelect' ] );
		$this->queryProcessor->register();

		add_filter( 'manage_edit-shop_order_columns', [ $this->gridExtender, 'addOrderListColumns' ] );
		add_action( 'manage_shop_order_posts_custom_column', [ $this->gridExtender, 'fillCustomOrderListColumns' ] );

		add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', [ $this->upgrade, 'handleCustomQueryVar' ], 10, 2 );

		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_head', array( $this->labelPrint, 'hideFromMenus' ) );
		add_action( 'admin_head', array( $this->orderCollectionPrint, 'hideFromMenus' ) );
		$this->orderModal->register();
		$this->order_metabox->register();

		$this->checkout->register_hooks();
		$this->productTab->register();

		add_action(
			'packetery_cron_carriers_hook',
			function () {
				$this->carrier_downloader->runAndRender();
			}
		);
		if ( ! wp_next_scheduled( 'packetery_cron_carriers_hook' ) ) {
			wp_schedule_event( time(), 'daily', 'packetery_cron_carriers_hook' );
		}

		// TODO: Packet status sync.
		wp_clear_scheduled_hook( 'packetery_cron_packet_status_sync_hook' );

		add_action( 'woocommerce_admin_order_data_after_shipping_address', [ $this, 'renderDeliveryDetail' ] );
		add_action( 'woocommerce_order_details_after_order_table', [ $this, 'renderOrderDetail' ] );

		// Adding custom actions to dropdown in admin order list.
		add_filter( 'bulk_actions-edit-shop_order', [ $this->orderBulkActions, 'addActions' ], 20, 1 );
		// Execute the action for selected orders.
		add_filter(
			'handle_bulk_actions-edit-shop_order',
			[
				$this->orderBulkActions,
				'handleActions',
			],
			10,
			3
		);
		// Print packets export result.
		add_action( 'admin_notices', [ $this->orderBulkActions, 'renderPacketsExportResult' ], self::MIN_LISTENER_PRIORITY );

		add_action( 'admin_init', [ $this->labelPrint, 'outputLabelsPdf' ] );
		add_action( 'admin_init', [ $this->orderCollectionPrint, 'print' ] );

		add_action( 'admin_init', [ $this->exporter, 'outputExportTxt' ] );
		add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', [ $this, 'transformGetOrdersQuery' ] );
	}

	/**
	 * Print inactive WooCommerce notice.
	 *
	 * @return void
	 */
	public function echoInactiveWooCommerceNotice(): void {
		if ( self::isWooCommercePluginActive() ) {
			// When Packeta plugin is active and WooCommerce plugin is inactive.
			// If user decides to activate WooCommerce plugin then invalid notice will not be rendered.
			// Packeta plugin probably bootstraps twice in such case.
			return;
		}

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/admin-notice.latte',
			[
				'message' => [
					'type'    => 'error',
					'message' => __( 'packetaPluginRequiresWooCommerce', 'packetery' ),
				],
			]
		);
	}

	/**
	 * Is WC plugin active.
	 *
	 * @return bool
	 */
	private static function isWooCommercePluginActive(): bool {
		return in_array( 'woocommerce/woocommerce.php', (array) get_option( 'active_plugins', [] ), true );
	}

	/**
	 * Filter queries.
	 *
	 * @param array $query Query.
	 *
	 * @return array
	 */
	public function transformGetOrdersQuery( array $query ): array {
		if ( ! empty( $query['packetery_meta_query'] ) ) {
			// @codingStandardsIgnoreStart
			$query['meta_query'] = $query['packetery_meta_query'];
			// @codingStandardsIgnoreEnd
		}

		return $query;
	}

	/**
	 * Renders delivery detail for packetery orders.
	 *
	 * @param WC_Order $order WordPress order.
	 */
	public function renderDeliveryDetail( WC_Order $order ): void {
		$orderEntity = $this->orderRepository->getByWcOrder( $order );
		if ( null === $orderEntity ) {
			return;
		}

		$carrierId      = $orderEntity->getCarrierId();
		$carrierOptions = Carrier\Options::createByCarrierId( $carrierId );

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/delivery-detail.latte',
			[
				'pickupPoint'              => $orderEntity->getPickupPoint(),
				'validatedDeliveryAddress' => $orderEntity->getValidatedDeliveryAddress(),
				'carrierAddressValidation' => $carrierOptions->getAddressValidation(),
			]
		);
	}

	/**
	 * Renders delivery detail for packetery orders, on "thank you" page and in frontend detail.
	 *
	 * @param WC_Order $wcOrder WordPress order.
	 */
	public function renderOrderDetail( WC_Order $wcOrder ): void {
		$order = $this->orderRepository->getById( $wcOrder->get_id() );
		if ( null === $order ) {
			return;
		}

		$pickupPoint              = $order->getPickupPoint();
		$validatedDeliveryAddress = $order->getValidatedDeliveryAddress();
		if ( null === $pickupPoint && null === $validatedDeliveryAddress ) {
			return;
		}

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/detail.latte',
			[
				'pickupPoint'              => $pickupPoint,
				'validatedDeliveryAddress' => $validatedDeliveryAddress,
			]
		);
	}

	/**
	 *  Renders email footer.
	 *
	 * @param \WC_Email|null $email Email data.
	 */
	public function render_email_footer( ?\WC_Email $email ): void {
		if ( null === $email || ! $email->object instanceof \WC_Order ) {
			return;
		}

		$packeteryOrder = $this->orderRepository->getByWcOrder( $email->object );
		if ( null === $packeteryOrder ) {
			return;
		}

		$pickupPoint              = $packeteryOrder->getPickupPoint();
		$validatedDeliveryAddress = $packeteryOrder->getValidatedDeliveryAddress();

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/email/footer.latte',
			[
				'pickupPoint'              => $pickupPoint,
				'validatedDeliveryAddress' => $validatedDeliveryAddress,
			]
		);
	}

	/**
	 * Enqueues admin JS file.
	 *
	 * @param string $name     Name of script.
	 * @param string $file     Relative file path.
	 * @param bool   $inFooter Tells where to include script.
	 * @param array  $deps     Script dependencies.
	 */
	private function enqueueScript( string $name, string $file, bool $inFooter, array $deps = [] ): void {
		wp_enqueue_script(
			$name,
			plugin_dir_url( $this->main_file_path ) . $file,
			$deps,
			md5( (string) filemtime( PACKETERY_PLUGIN_DIR . '/' . $file ) ),
			$inFooter
		);
	}

	/**
	 * Enqueues CSS file.
	 *
	 * @param string $name Name of script.
	 * @param string $file Relative file path.
	 */
	private function enqueueStyle( string $name, string $file ): void {
		wp_enqueue_style(
			$name,
			plugin_dir_url( $this->main_file_path ) . $file,
			[],
			md5( (string) filemtime( PACKETERY_PLUGIN_DIR . '/' . $file ) )
		);
	}

	/**
	 * Builds asset URL.
	 *
	 * @param string $asset Relative asset path without leading slash.
	 *
	 * @return string
	 */
	public static function buildAssetUrl( string $asset ): string {
		$url      = plugin_dir_url( PACKETERY_PLUGIN_DIR . '/packeta.php' ) . $asset;
		$filename = PACKETERY_PLUGIN_DIR . '/' . $asset;

		return add_query_arg( [ 'v' => md5( (string) filemtime( $filename ) ) ], $url );
	}

	/**
	 * Enqueues javascript files and stylesheets for checkout.
	 */
	public function enqueueFrontAssets(): void {
		if ( is_checkout() ) {
			$this->enqueueStyle( 'packetery-front-styles', 'public/front.css' );
			$this->enqueueScript( 'packetery-checkout', 'public/checkout.js', true, [ 'jquery' ] );
		}
	}

	/**
	 * Enqueues javascript files and stylesheets for administration.
	 */
	public function enqueueAdminAssets(): void {
		global $pagenow, $typenow;

		$page              = $this->request->getQuery( 'page' );
		$isOrderGridPage   = $this->gridExtender->isOrderGridPage( $pagenow, $typenow );
		$isOrderDetailPage = 'post.php' === $pagenow && 'shop_order' === $typenow;

		if ( $isOrderGridPage || $isOrderDetailPage || in_array( $page, [ Carrier\OptionsPage::SLUG, Options\Page::SLUG ], true ) ) {
			$this->enqueueScript( 'live-form-validation-options', 'public/live-form-validation-options.js', false );
			$this->enqueueScript( 'live-form-validation', 'public/libs/live-form-validation/live-form-validation.js', false, [ 'live-form-validation-options' ] );
		}

		if ( Carrier\OptionsPage::SLUG === $page ) {
			$this->enqueueScript( 'packetery-admin-country-carrier', 'public/admin-country-carrier.js', true, [ 'jquery' ] );
		}

		if ( $isOrderGridPage || $isOrderDetailPage || in_array( $page, [ Options\Page::SLUG, Carrier\OptionsPage::SLUG, Log\Page::SLUG, Order\labelPrint::MENU_SLUG ], true ) ) {
			$this->enqueueStyle( 'packetery-admin-styles', 'public/admin.css' );
		}

		if ( $isOrderGridPage ) {
			$this->enqueueScript( 'packetery-admin-grid-order-edit-js', 'public/admin-grid-order-edit.js', true, [ 'jquery', 'wp-util', 'backbone' ] );
		}

		if ( $isOrderDetailPage ) {
			$this->enqueueScript( 'packetery-admin-pickup-point-picker', 'public/admin-pickup-point-picker.js', false, [ 'jquery' ] );
		}
	}

	/**
	 *  Add links to left admin menu.
	 */
	public function add_menu_pages(): void {
		$this->options_page->register();
		$this->carrierOptionsPage->register();
		$this->labelPrint->register();
		$this->orderCollectionPrint->register();
		$this->logPage->register();
	}

	/**
	 * Gets current locale.
	 */
	private function getLocale(): string {
		return apply_filters(
			'plugin_locale',
			( is_admin() ? get_user_locale() : get_locale() ),
			self::DOMAIN
		);
	}

	/**
	 * Loads plugin translation file by user locale.
	 */
	public function loadTranslation(): void {
		$domain = self::DOMAIN;
		$locale = $this->getLocale();

		$moFile = PACKETERY_PLUGIN_DIR . "/languages/$domain-$locale.mo";
		$result = load_textdomain( $domain, $moFile );

		if ( false === $result ) {
			$moFile = PACKETERY_PLUGIN_DIR . "/languages/$domain-en_US.mo";
			load_textdomain( $domain, $moFile );
		}
	}

	/**
	 * Inits plugin.
	 */
	public function init(): void {
		add_filter(
			'plugin_action_links_' . plugin_basename( $this->main_file_path ),
			[
				$this,
				'addPluginActionLinks',
			]
		);
		add_filter( 'plugin_row_meta', [ $this, 'addPluginRowMeta' ], 10, 2 );
	}

	/**
	 * Activates plugin.
	 */
	public function activate(): void {
		global $wpdb;

		$this->logRepository->createTable();
		if ( false === PACKETERY_DEBUG ) {
			$this->options_page->setDefaultValues();
		}

		$this->init();

		$createResult = $this->carrierRepository->createTable();
		if ( false === $createResult ) {
			$lastError = $wpdb->last_error;
			$this->message_manager->flash_message( __( 'carrierTableNotCreatedMoreInformationInPacketaLog', 'packetery' ), MessageManager::TYPE_ERROR );

			$record         = new Record();
			$record->action = Record::ACTION_CARRIER_TABLE_NOT_CREATED;
			$record->status = Record::STATUS_ERROR;
			$record->title  = __( 'carrierTableNotCreated', 'packetery' );
			$record->params = [
				'errorMessage' => $lastError,
			];
			$this->logger->add( $record );
		}

		$createResult = $this->orderRepository->createTable();
		if ( false === $createResult ) {
			$lastError = $wpdb->last_error;
			$this->message_manager->flash_message( __( 'orderTableNotCreatedMoreInformationInPacketaLog', 'packetery' ), MessageManager::TYPE_ERROR );

			$record         = new Record();
			$record->action = Record::ACTION_ORDER_TABLE_NOT_CREATED;
			$record->status = Record::STATUS_ERROR;
			$record->title  = __( 'orderTableNotCreated', 'packetery' );
			$record->params = [
				'errorMessage' => $lastError,
			];
			$this->logger->add( $record );
		}

		$this->upgrade->check();
	}

	/**
	 * Uninstalls plugin and drops custom database table.
	 * Only a static class method or function can be used in an uninstall hook.
	 */
	public static function uninstall(): void {
		if ( defined( 'PACKETERY_DEBUG' ) && PACKETERY_DEBUG === true ) {
			return;
		}

		$container = require PACKETERY_PLUGIN_DIR . '/bootstrap.php';

		$optionsRepository = $container->getByType( Options\Repository::class );
		$pluginOptions     = $optionsRepository->getPluginOptions();
		foreach ( $pluginOptions as $option ) {
			delete_option( $option->option_name );
		}

		$logRepository = $container->getByType( Log\Repository::class );
		$logRepository->drop();

		$carrierRepository = $container->getByType( Carrier\Repository::class );
		$carrierRepository->drop();

		$orderRepository = $container->getByType( Order\Repository::class );
		$orderRepository->drop();
	}

	/**
	 * Adds action links visible at the plugin screen.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/plugin_action_links_plugin_file/
	 *
	 * @param array $links Plugin Action links.
	 *
	 * @return array
	 */
	public function addPluginActionLinks( array $links ): array {
		$settingsLink = '<a href="' . esc_url( admin_url( 'admin.php?page=' . Options\Page::SLUG ) ) . '" aria-label="' .
					esc_attr__( 'View Packeta settings', 'packetery' ) . '">' .
					esc_html__( 'Settings', 'packetery' ) . '</a>';

		array_unshift( $links, $settingsLink );

		return $links;
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/plugin_row_meta/
	 *
	 * @param array  $links Plugin Row Meta.
	 * @param string $pluginFileName Plugin Base file.
	 *
	 * @return array
	 */
	public function addPluginRowMeta( array $links, string $pluginFileName ): array {
		if ( ! strpos( $pluginFileName, basename( $this->main_file_path ) ) ) {
			return $links;
		}
		$links[] = '<a href="' . esc_url( 'https://github.com/Zasilkovna/WooCommerce/wiki' ) . '" aria-label="' .
		esc_attr__( 'View Packeta documentation', 'packetery' ) . '">' .
		esc_html__( 'Documentation', 'packetery' ) . '</a>';

		return $links;
	}

	/**
	 * Adds Packeta method to available shipping methods.
	 *
	 * @param array $methods Previous state.
	 *
	 * @return array
	 */
	public function add_shipping_method( array $methods ): array {
		$methods[ ShippingMethod::PACKETERY_METHOD_ID ] = ShippingMethod::class;

		return $methods;
	}

	/**
	 * Hides submenu item. Must not be called too early.
	 *
	 * @param string $itemSlug Item slug.
	 */
	public static function hideSubmenuItem( string $itemSlug ): void {
		global $submenu;
		if ( isset( $submenu[ Options\Page::SLUG ] ) ) {
			foreach ( $submenu[ Options\Page::SLUG ] as $key => $menu ) {
				if ( $itemSlug === $menu[2] ) {
					unset( $submenu[ Options\Page::SLUG ][ $key ] );
				}
			}
		}
	}

	/**
	 * Gets software identity for Packeta APIs.
	 *
	 * @return string
	 */
	public static function getAppIdentity(): string {
		return 'Woocommerce: ' . get_bloginfo( 'version' ) . ', WordPress: ' . WC_VERSION . ', plugin Packeta: ' . self::VERSION;
	}
}
