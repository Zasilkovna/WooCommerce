<?php
/**
 * Main Packeta plugin class.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Core\Log\ILogger;
use Packetery\Module\Address;
use Packetery\Core\Log\Record;
use Packetery\Module\Carrier\Downloader;
use Packetery\Module\Carrier\OptionsPage;
use Packetery\Module\Carrier\Repository;
use Packetery\Module\EntityFactory;
use Packetery\Module\Log;
use Packetery\Module\Options;
use Packetery\Module\Order;
use Packetery\Module\Product;
use PacketeryLatte\Engine;
use PacketeryNette\Forms\Form;
use WC_Order;

/**
 * Class Plugin
 *
 * @package Packetery
 */
class Plugin {

	public const VERSION               = '1.1.0';
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
	 * Address repository.
	 *
	 * @var Address\Repository
	 */
	private $addressRepository;

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
	 * PickupPoint factory.
	 *
	 * @var EntityFactory\PickupPoint
	 */
	private $pickupPointFactory;

	/**
	 * Order factory.
	 *
	 * @var EntityFactory\Order
	 */
	private $orderFactory;

	/**
	 * Options exporter.
	 *
	 * @var Options\Exporter
	 */
	private $exporter;

	/**
	 * Plugin constructor.
	 *
	 * @param Order\Metabox             $order_metabox        Order metabox.
	 * @param MessageManager            $message_manager      Message manager.
	 * @param Options\Page              $options_page         Options page.
	 * @param Repository                $carrierRepository Carrier repository.
	 * @param Downloader                $carrier_downloader   Carrier downloader object.
	 * @param Checkout                  $checkout             Checkout class.
	 * @param Engine                    $latte_engine         PacketeryLatte engine.
	 * @param OptionsPage               $carrierOptionsPage   Carrier options page.
	 * @param Order\BulkActions         $orderBulkActions     Order BulkActions.
	 * @param Order\LabelPrint          $labelPrint           Label printing.
	 * @param Order\GridExtender        $gridExtender         Order grid extender.
	 * @param Product\DataTab           $productTab           Product tab.
	 * @param Log\Page                  $logPage              Log page.
	 * @param ILogger                   $logger               Log manager.
	 * @param Address\Repository        $addressRepository    Address repository.
	 * @param Order\Controller          $orderController      Order controller.
	 * @param Order\Modal               $orderModal           Order modal.
	 * @param EntityFactory\PickupPoint $pickupPointFactory   PickupPoint factory.
	 * @param Options\Exporter          $exporter             Options exporter.
	 * @param Order\CollectionPrint     $orderCollectionPrint Order collection print.
	 * @param EntityFactory\Order       $orderFactory         Order factory.
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
		Address\Repository $addressRepository,
		Order\Controller $orderController,
		Order\Modal $orderModal,
		EntityFactory\PickupPoint $pickupPointFactory,
		Options\Exporter $exporter,
		Order\CollectionPrint $orderCollectionPrint,
		EntityFactory\Order $orderFactory
	) {
		$this->options_page         = $options_page;
		$this->latte_engine         = $latte_engine;
		$this->carrierRepository    = $carrierRepository;
		$this->carrier_downloader   = $carrier_downloader;
		$this->main_file_path       = PACKETERY_PLUGIN_DIR . '/packetery.php';
		$this->order_metabox        = $order_metabox;
		$this->message_manager      = $message_manager;
		$this->options_page         = $options_page;
		$this->checkout             = $checkout;
		$this->carrierOptionsPage   = $carrierOptionsPage;
		$this->orderBulkActions     = $orderBulkActions;
		$this->labelPrint           = $labelPrint;
		$this->gridExtender         = $gridExtender;
		$this->productTab           = $productTab;
		$this->logPage              = $logPage;
		$this->logger               = $logger;
		$this->addressRepository    = $addressRepository;
		$this->orderController      = $orderController;
		$this->orderModal           = $orderModal;
		$this->pickupPointFactory   = $pickupPointFactory;
		$this->exporter             = $exporter;
		$this->orderCollectionPrint = $orderCollectionPrint;
		$this->orderFactory         = $orderFactory;
	}

	/**
	 * Method to register hooks
	 */
	public function run(): void {
		add_action( 'init', array( $this, 'loadTranslation' ), 1 );
		add_action( 'init', [ $this->logger, 'register' ], 5 );
		add_action( 'init', [ $this->message_manager, 'init' ], 9 );
		add_action( 'init', [ $this->addressRepository, 'register' ] );
		add_action( 'rest_api_init', [ $this->orderController, 'registerRoutes' ] );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminAssets' ) );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueFrontAssets' ] );
		Form::initialize();

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
		add_filter( 'request', [ $this->gridExtender, 'addQueryVarsToRequest' ], PHP_INT_MAX );
		add_filter( 'manage_edit-shop_order_columns', [ $this->gridExtender, 'addOrderListColumns' ] );
		add_action( 'manage_shop_order_posts_custom_column', [ $this->gridExtender, 'fillCustomOrderListColumns' ] );
		add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', [ $this->gridExtender, 'handleCustomQueryVar' ], 10, 2 );

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
	}

	/**
	 * Renders delivery detail for packetery orders.
	 *
	 * @param WC_Order $order WordPress order.
	 */
	public function renderDeliveryDetail( WC_Order $order ): void {
		$orderEntity = $this->orderFactory->create( $order );
		if ( null === $orderEntity ) {
			return;
		}

		$carrierId      = $orderEntity->getCarrierId();
		$carrierOptions = Carrier\Options::createByCarrierId( $carrierId );

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/delivery-detail.latte',
			[
				'pickupPoint'              => $orderEntity->getPickupPoint(),
				'validatedDeliveryAddress' => $this->addressRepository->getValidatedByOrderId( (int) $orderEntity->getNumber() ),
				'carrierAddressValidation' => $carrierOptions->getAddressValidation(),
			]
		);
	}

	/**
	 * Renders delivery detail for packetery orders, on "thank you" page and in frontend detail.
	 *
	 * @param WC_Order $order WordPress order.
	 */
	public function renderOrderDetail( WC_Order $order ): void {
		$pickupPoint              = $this->pickupPointFactory->fromWcOrder( $order );
		$validatedDeliveryAddress = $this->addressRepository->getValidatedByOrderId( $order->get_id() );
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

		$packeteryOrder = $this->orderFactory->create( $email->object );
		if ( null === $packeteryOrder ) {
			return;
		}

		$pickupPoint              = $packeteryOrder->getPickupPoint();
		$validatedDeliveryAddress = $this->addressRepository->getValidatedByOrderId( (int) $packeteryOrder->getNumber() );

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
		$url      = plugin_dir_url( PACKETERY_PLUGIN_DIR . '/packetery.php' ) . $asset;
		$filename = PACKETERY_PLUGIN_DIR . '/' . $asset;

		return add_query_arg( [ 'v' => md5( (string) filemtime( $filename ) ) ], $url );
	}

	/**
	 * Enqueues javascript files and stylesheets for checkout.
	 */
	public function enqueueFrontAssets(): void {
		$this->enqueueStyle( 'packetery-front-styles', 'public/front.css' );
		$this->enqueueScript( 'packetery-checkout', 'public/checkout.js', false );
	}

	/**
	 * Enqueues javascript files and stylesheets for administration.
	 */
	public function enqueueAdminAssets(): void {
		$this->enqueueScript( 'live-form-validation-options', 'public/live-form-validation-options.js', false );
		$this->enqueueScript( 'live-form-validation', 'public/libs/live-form-validation/live-form-validation.js', false, [ 'live-form-validation-options' ] );
		$this->enqueueScript( 'packetery-admin-country-carrier', 'public/admin-country-carrier.js', true );
		wp_enqueue_style( 'dashicons' );
		$this->enqueueStyle( 'packetery-admin-styles', 'public/admin.css' );
		$this->enqueueScript( 'packetery-admin-grid-order-edit-js', 'public/admin-grid-order-edit.js', true, [ 'jquery', 'wp-util', 'backbone' ] );
		$this->enqueueScript( 'packetery-admin-pickup-point-picker', 'public/admin-pickup-point-picker.js', false, [ 'jquery' ] );
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
	}

	/**
	 * Activates plugin.
	 */
	public function activate(): void {
		global $wpdb;

		if ( false === PACKETERY_DEBUG ) {
			$this->options_page->setDefaultValues();
		}

		$this->init();

		$createResult = $this->carrierRepository->createTable();
		if ( false === $createResult ) {
			$lastError = $wpdb->last_error;
			$this->message_manager->flash_message( __( 'carrierTableNotCreatedMoreInformationInPacketaLog', 'packetery' ), 'error' );

			$record         = new Record();
			$record->action = Record::ACTION_CARRIER_TABLE_NOT_CREATED;
			$record->status = Record::STATUS_ERROR;
			$record->title  = __( 'carrierTableNotCreated', 'packetery' );
			$record->params = [
				'errorMessage' => $lastError,
			];
			$this->logger->add( $record );
		}

		$versionCheck = get_option( 'packetery_version' );
		if ( false === $versionCheck ) {
			update_option( 'packetery_version', self::VERSION );
		}
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

		$carrierRepository = $container->getByType( Carrier\Repository::class );
		$carrierRepository->drop();

		$logEntries = get_posts(
			[
				'post_type'   => Log\PostLogger::POST_TYPE,
				'post_status' => 'any',
				'nopaging'    => true,
				'fields'      => 'ids',
			]
		);
		foreach ( $logEntries as $logEntryId ) {
			wp_delete_post( $logEntryId, true );
		}

		unregister_post_type( Log\PostLogger::POST_TYPE );
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
