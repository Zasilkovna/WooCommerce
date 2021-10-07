<?php
/**
 * Main Packeta plugin class.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Core\Log\ILogger;
use Packetery\Module\Log;
use Packetery\Module\Carrier\Downloader;
use Packetery\Module\Carrier\OptionsPage;
use Packetery\Module\Carrier\Repository;
use Packetery\Module\Order;
use Packetery\Module\Options;
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

	public const VERSION = '1.0.0';
	public const DOMAIN  = 'packetery';

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
	private $carrier_repository;

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
	 * Plugin constructor.
	 *
	 * @param Order\Metabox      $order_metabox      Order metabox.
	 * @param MessageManager     $message_manager    Message manager.
	 * @param Options\Page       $options_page       Options page.
	 * @param Repository         $carrier_repository Carrier repository.
	 * @param Downloader         $carrier_downloader Carrier downloader object.
	 * @param Checkout           $checkout           Checkout class.
	 * @param Engine             $latte_engine       PacketeryLatte engine.
	 * @param OptionsPage        $carrierOptionsPage Carrier options page.
	 * @param Order\BulkActions  $orderBulkActions   Order BulkActions.
	 * @param Order\LabelPrint   $labelPrint         Label printing.
	 * @param Order\GridExtender $gridExtender       Order grid extender.
	 * @param Product\DataTab    $productTab         Product tab.
	 * @param Log\Page           $logPage            Log page.
	 * @param ILogger            $logger             Log manager.
	 */
	public function __construct(
		Order\Metabox $order_metabox,
		MessageManager $message_manager,
		Options\Page $options_page,
		Repository $carrier_repository,
		Downloader $carrier_downloader,
		Checkout $checkout,
		Engine $latte_engine,
		OptionsPage $carrierOptionsPage,
		Order\BulkActions $orderBulkActions,
		Order\LabelPrint $labelPrint,
		Order\GridExtender $gridExtender,
		Product\DataTab $productTab,
		Log\Page $logPage,
		ILogger $logger
	) {
		$this->options_page       = $options_page;
		$this->latte_engine       = $latte_engine;
		$this->carrier_repository = $carrier_repository;
		$this->carrier_downloader = $carrier_downloader;
		$this->main_file_path     = PACKETERY_PLUGIN_DIR . '/packetery.php';
		$this->order_metabox      = $order_metabox;
		$this->message_manager    = $message_manager;
		$this->options_page       = $options_page;
		$this->checkout           = $checkout;
		$this->carrierOptionsPage = $carrierOptionsPage;
		$this->orderBulkActions   = $orderBulkActions;
		$this->labelPrint         = $labelPrint;
		$this->gridExtender       = $gridExtender;
		$this->productTab         = $productTab;
		$this->logPage            = $logPage;
		$this->logger             = $logger;
	}

	/**
	 * Method to register hooks
	 */
	public function run(): void {
		add_action( 'init', array( $this, 'loadTranslation' ), 1 );
		add_action( 'init', [ $this->logger, 'register' ], 5 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminAssets' ) );
		Form::initialize();

		add_action(
			'admin_notices',
			function () {
				$this->message_manager->render();
			}
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
		add_filter( 'request', [ $this->gridExtender, 'addQueryVarsToRequest' ] );
		add_filter( 'manage_edit-shop_order_columns', [ $this->gridExtender, 'addOrderListColumns' ] );
		add_action( 'manage_shop_order_posts_custom_column', [ $this->gridExtender, 'fillCustomOrderListColumns' ] );
		add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', [ $this->gridExtender, 'addQueryVars' ], 10, 2 );

		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_head', array( $this->labelPrint, 'hideFromMenus' ) );
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
		add_action( 'admin_notices', [ $this->orderBulkActions, 'renderPacketsExportResult' ] );

		add_action( 'admin_init', [ $this->labelPrint, 'outputLabelsPdf' ] );
	}

	/**
	 * Renders delivery detail for packetery orders.
	 *
	 * @param WC_Order $order WordPress order.
	 */
	public function renderDeliveryDetail( WC_Order $order ): void {
		$orderEntity = new Order\Entity( $order );
		if ( false === $orderEntity->isPacketeryPickupPointRelated() ) {
			return;
		}

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/delivery-detail.latte',
			[
				'order' => $orderEntity,
			]
		);
	}

	/**
	 * Renders delivery detail for packetery orders, on "thank you" page and in frontend detail.
	 *
	 * @param WC_Order $order WordPress order.
	 */
	public function renderOrderDetail( WC_Order $order ): void {
		$orderEntity = new Order\Entity( $order );
		if ( false === $orderEntity->isPacketeryPickupPointRelated() ) {
			return;
		}

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/detail.latte',
			[ 'order' => $orderEntity ]
		);
	}

	/**
	 *  Renders email footer.
	 */
	public function render_email_footer(): void {
		$orderEntity = Order\Entity::fromGlobals();
		if ( $orderEntity === null || $orderEntity->isPacketeryRelated() === false ) {
			return;
		}

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/email/footer.latte',
			[ 'order' => $orderEntity ]
		);
	}

	/**
	 * Enqueues admin JS file.
	 *
	 * @param string $name     Name of script.
	 * @param string $file     Relative file path.
	 * @param bool   $inFooter Tells where to include script.
	 */
	private function enqueueScript( string $name, string $file, bool $inFooter ): void {
		wp_enqueue_script(
			$name,
			plugin_dir_url( $this->main_file_path ) . $file,
			[],
			md5( (string) filemtime( PACKETERY_PLUGIN_DIR . '/' . $file ) ),
			$inFooter
		);
	}

	/**
	 * Enqueues javascript files and stylesheets for administration.
	 */
	public function enqueueAdminAssets(): void {
		$this->enqueueScript( 'live-form-validation', 'public/libs/live-form-validation/live-form-validation.js', false );
		$this->enqueueScript( 'packetery-admin-country-carrier', 'public/admin-country-carrier.js', true );
		wp_enqueue_style(
			'packetery-admin-styles',
			plugin_dir_url( $this->main_file_path ) . 'public/admin.css',
			[],
			md5( (string) filemtime( PACKETERY_PLUGIN_DIR . '/public/admin.css' ) )
		);
	}

	/**
	 *  Add links to left admin menu.
	 */
	public function add_menu_pages(): void {
		$this->options_page->register();
		$this->carrierOptionsPage->register();
		$this->labelPrint->register();
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
			array(
				$this,
				'plugin_action_links',
			)
		);
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Activates plugin.
	 */
	public function activate(): void {
		$this->init();
		$this->carrier_repository->create_table();
	}

	/**
	 * Uninstalls plugin and drops custom database table.
	 * Only a static class method or function can be used in an uninstall hook.
	 */
	public static function uninstall(): void {
		$container  = require PACKETERY_PLUGIN_DIR . '/bootstrap.php';
		$repository = $container->getByType( Repository::class );
		$repository->drop();
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/plugin_action_links_plugin_file/
	 *
	 * @param array $links Plugin Action links.
	 *
	 * @return array
	 */
	public function plugin_action_links( array $links ): array {
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=packeta-options' ) ) . '" aria-label="' .
					esc_attr__( 'View Packeta settings', 'packetery' ) . '">' .
					esc_html__( 'Settings', 'packetery' ) . '</a>';

		return $links;
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/plugin_row_meta/
	 *
	 * @param array  $links Plugin Row Meta.
	 * @param string $plugin_file_name Plugin Base file.
	 *
	 * @return array
	 */
	public function plugin_row_meta( array $links, string $plugin_file_name ): array {
		if ( ! strpos( $plugin_file_name, basename( $this->main_file_path ) ) ) {
			return $links;
		}

		$links[] = '<a href="' . esc_url( 'https://www.packeta.com/todo-plugin-docs/' ) . '" aria-label="' .
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

}
