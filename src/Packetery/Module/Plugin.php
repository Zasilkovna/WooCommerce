<?php
/**
 * Main Packeta plugin class.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Core\Log\ILogger;
use Packetery\Module\Carrier\OptionsPage;
use Packetery\Module\Log;
use Packetery\Module\Options;
use Packetery\Module\Order;
use Packetery\Module\Product;
use PacketeryLatte\Engine;
use PacketeryNette\Http\Request;
use PacketeryNette\Utils\Html;
use WC_Email;
use WC_Order;

/**
 * Class Plugin
 *
 * @package Packetery
 */
class Plugin {

	public const VERSION                = '1.4';
	public const DOMAIN                 = 'packeta';
	public const MIN_LISTENER_PRIORITY  = - 9998;
	public const PARAM_PACKETERY_ACTION = 'packetery_action';
	public const PARAM_NONCE            = '_wpnonce';

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
	 * Dashboard widget.
	 *
	 * @var DashboardWidget
	 */
	private $dashboardWidget;

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
	 * Cron service.
	 *
	 * @var CronService
	 */
	private $cronService;

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
	 * Options provider.
	 *
	 * @var Options\Provider
	 */
	private $optionsProvider;

	/**
	 * Packet canceller.
	 *
	 * @var Order\PacketCanceller
	 */
	private $packetCanceller;

	/**
	 * Context resolver.
	 *
	 * @var ContextResolver
	 */
	private $contextResolver;

	/**
	 * Packet submitter
	 *
	 * @var Order\PacketSubmitter
	 */
	private $packetSubmitter;

	/**
	 * Plugin constructor.
	 *
	 * @param Order\Metabox         $order_metabox        Order metabox.
	 * @param MessageManager        $message_manager      Message manager.
	 * @param Options\Page          $options_page         Options page.
	 * @param Checkout              $checkout             Checkout class.
	 * @param Engine                $latte_engine         PacketeryLatte engine.
	 * @param OptionsPage           $carrierOptionsPage   Carrier options page.
	 * @param Order\BulkActions     $orderBulkActions     Order BulkActions.
	 * @param Order\LabelPrint      $labelPrint           Label printing.
	 * @param Order\GridExtender    $gridExtender         Order grid extender.
	 * @param Product\DataTab       $productTab           Product tab.
	 * @param Log\Page              $logPage              Log page.
	 * @param ILogger               $logger               Log manager.
	 * @param Order\Controller      $orderController      Order controller.
	 * @param Order\Modal           $orderModal           Order modal.
	 * @param Options\Exporter      $exporter             Options exporter.
	 * @param Order\CollectionPrint $orderCollectionPrint Order collection print.
	 * @param Request               $request              HTTP request.
	 * @param Order\Repository      $orderRepository      Order repository.
	 * @param Upgrade               $upgrade              Plugin upgrade.
	 * @param QueryProcessor        $queryProcessor       QueryProcessor.
	 * @param Options\Provider      $optionsProvider      Options provider.
	 * @param CronService           $cronService          Cron service.
	 * @param Order\PacketCanceller $packetCanceller      Packet canceller.
	 * @param ContextResolver       $contextResolver      Context resolver.
	 * @param DashboardWidget       $dashboardWidget      Dashboard widget.
	 * @param Order\PacketSubmitter $packetSubmitter      Packet submitter.
	 */
	public function __construct(
		Order\Metabox $order_metabox,
		MessageManager $message_manager,
		Options\Page $options_page,
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
		Request $request,
		Order\Repository $orderRepository,
		Upgrade $upgrade,
		QueryProcessor $queryProcessor,
		Options\Provider $optionsProvider,
		CronService $cronService,
		Order\PacketCanceller $packetCanceller,
		ContextResolver $contextResolver,
		DashboardWidget $dashboardWidget,
		Order\PacketSubmitter $packetSubmitter
	) {
		$this->options_page         = $options_page;
		$this->latte_engine         = $latte_engine;
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
		$this->request              = $request;
		$this->orderRepository      = $orderRepository;
		$this->upgrade              = $upgrade;
		$this->queryProcessor       = $queryProcessor;
		$this->optionsProvider      = $optionsProvider;
		$this->cronService          = $cronService;
		$this->packetCanceller      = $packetCanceller;
		$this->contextResolver      = $contextResolver;
		$this->dashboardWidget      = $dashboardWidget;
		$this->packetSubmitter      = $packetSubmitter;
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

		// TODO: deactivation_hook.
		register_deactivation_hook(
			$this->main_file_path,
			static function () {
				CronService::deactivate();
			}
		);

		register_uninstall_hook( $this->main_file_path, array( __CLASS__, 'uninstall' ) );

		add_action( 'woocommerce_email_footer', [ $this, 'renderEmailFooter' ] );
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
		add_action( 'admin_head', [ $this, 'renderConfirmModalTemplate' ] );
		$this->orderModal->register();
		$this->order_metabox->register();

		$this->checkout->register_hooks();
		$this->productTab->register();
		$this->cronService->register();

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
		add_action( 'admin_init', [ $this, 'handleActions' ] );
		add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', [ $this, 'transformGetOrdersQuery' ] );

		add_action( 'deleted_post', [ $this->orderRepository, 'deletedPostHook' ], 10, 2 );
		$this->dashboardWidget->register();
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
					'message' => __( 'Packeta plugin requires WooCommerce. Please install and activate it first.', 'packeta' ),
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
		return Helper::isPluginActive( 'woocommerce/woocommerce.php' );
	}

	/**
	 * Creates HTML link parts in array.
	 *
	 * @param string $href Href.
	 *
	 * @return string[]
	 */
	public static function createLinkParts( string $href ): array {
		$link = Html::el( 'a' )->href( $href );
		return [ $link->startTag(), $link->endTag() ];
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
				'translations'             => [
					'packeta'                => __( 'Packeta', 'packeta' ),
					'pickupPointDetail'      => __( 'Pickup Point Detail', 'packeta' ),
					'name'                   => __( 'Name', 'packeta' ),
					'address'                => __( 'Address', 'packeta' ),
					'pickupPointDetailCaps'  => __( 'Pickup Point Detail', 'packeta' ),
					'addressWasNotValidated' => __( 'Address was not validated', 'packeta' ),
					'validatedAddress'       => __( 'Validated address', 'packeta' ),
					'street'                 => __( 'Street', 'packeta' ),
					'houseNumber'            => __( 'House number', 'packeta' ),
					'city'                   => __( 'City', 'packeta' ),
					'zip'                    => __( 'Zip', 'packeta' ),
					'county'                 => __( 'County', 'packeta' ),
					'gps'                    => __( 'GPS', 'packeta' ),
				],
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
				'displayPickupPointInfo'   => $this->shouldDisplayPickupPointInfo(),
				'pickupPoint'              => $pickupPoint,
				'validatedDeliveryAddress' => $validatedDeliveryAddress,
				'translations'             => [
					'packeta'             => __( 'Packeta', 'packeta' ),
					'selectedPickupPoint' => __( 'Selected pickup point', 'packeta' ),
					'pickupPointName'     => __( 'Pickup Point Name', 'packeta' ),
					'pickupPointDetail'   => __( 'Pickup Point Detail', 'packeta' ),
					'validatedAddress'    => __( 'Validated address', 'packeta' ),
					'address'             => __( 'Address', 'packeta' ),
				],
			]
		);
	}

	/**
	 *  Renders email footer.
	 *
	 * @param mixed $email Email data.
	 */
	public function renderEmailFooter( $email ): void {
		if ( ! $email instanceof WC_Email || ! $email->object instanceof WC_Order ) {
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
				'displayPickupPointInfo'   => $this->shouldDisplayPickupPointInfo(),
				'pickupPoint'              => $pickupPoint,
				'validatedDeliveryAddress' => $validatedDeliveryAddress,
				'translations'             => [
					'packeta'                  => __( 'Packeta', 'packeta' ),
					'pickupPointDetail'        => __( 'Pickup Point Detail', 'packeta' ),
					'pickupPointName'          => __( 'Pickup Point Name', 'packeta' ),
					'link'                     => __( 'Link', 'packeta' ),
					'pickupPointAddress'       => __( 'Pickup Point Address', 'packeta' ),
					'validatedDeliveryAddress' => __( 'validated delivery address', 'packeta' ),
					'street'                   => __( 'Street', 'packeta' ),
					'houseNumber'              => __( 'House number', 'packeta' ),
					'city'                     => __( 'City', 'packeta' ),
					'zip'                      => __( 'Zip', 'packeta' ),
					'county'                   => __( 'County', 'packeta' ),
				],
			]
		);
	}

	/**
	 * Tells if pickup point info should be displayed.
	 *
	 * @return bool
	 */
	public function shouldDisplayPickupPointInfo(): bool {
		return ! $this->optionsProvider->replaceShippingAddressWithPickupPointAddress() || wc_ship_to_billing_address_only();
	}

	/**
	 * Renders confirm modal template.
	 */
	public function renderConfirmModalTemplate(): void {
		if ( $this->contextResolver->isPacketeryConfirmPage() ) {
			$this->latte_engine->render(
				PACKETERY_PLUGIN_DIR . '/template/confirm-modal-template.latte',
				[
					'translations' => [
						'closeModalPanel' => __( 'Close modal panel', 'packeta' ),
						'no'              => __( 'No', 'packeta' ),
						'yes'             => __( 'Yes', 'packeta' ),
					],
				]
			);
		}
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
			$this->enqueueStyle( 'packetery-custom-front-styles', 'public/custom-front.css' );
			$this->enqueueScript( 'packetery-checkout', 'public/checkout.js', true, [ 'jquery' ] );
			wp_localize_script( 'packetery-checkout', 'packeteryCheckoutSettings', $this->checkout->createSettings() );
		}
	}

	/**
	 * Enqueues javascript files and stylesheets for administration.
	 */
	public function enqueueAdminAssets(): void {
		$page              = $this->request->getQuery( 'page' );
		$isOrderGridPage   = $this->contextResolver->isOrderGridPage();
		$isOrderDetailPage = $this->contextResolver->isOrderDetailPage();

		if ( $isOrderGridPage || $isOrderDetailPage || in_array( $page, [ Carrier\OptionsPage::SLUG, Options\Page::SLUG ], true ) ) {
			$this->enqueueScript( 'live-form-validation-options', 'public/live-form-validation-options.js', false );
			$this->enqueueScript( 'live-form-validation', 'public/libs/live-form-validation/live-form-validation.js', false, [ 'live-form-validation-options' ] );
			$this->enqueueScript( 'live-form-validation-extension', 'public/live-form-validation-extension.js', false, [ 'live-form-validation' ] );
		}

		if ( Carrier\OptionsPage::SLUG === $page ) {
			$this->enqueueScript( 'packetery-admin-country-carrier', 'public/admin-country-carrier.js', true, [ 'jquery' ] );
		}

		$isProductDetailPage = $this->contextResolver->isProductDetailPage();

		if ( $isOrderGridPage || $isOrderDetailPage || $isProductDetailPage || in_array( $page, [ Options\Page::SLUG, Carrier\OptionsPage::SLUG, Log\Page::SLUG, Order\labelPrint::MENU_SLUG ], true ) ) {
			$this->enqueueStyle( 'packetery-admin-styles', 'public/admin.css' );
		}

		if ( $isOrderGridPage ) {
			$this->enqueueScript( 'packetery-admin-grid-order-edit-js', 'public/admin-grid-order-edit.js', true, [ 'jquery', 'wp-util', 'backbone' ] );
		}

		if ( $isOrderDetailPage ) {
			$this->enqueueScript( 'packetery-admin-pickup-point-picker', 'public/admin-pickup-point-picker.js', false, [ 'jquery' ] );
		}

		if ( $this->contextResolver->isPacketeryConfirmPage() ) {
			$this->enqueueScript( 'packetery-confirm', 'public/confirm.js', true, [ 'jquery', 'backbone' ] );
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
		/**
		 * Applies plugin_locale filters.
		 *
		 * @since 1.0.0
		 */
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
					esc_attr__( 'View the plugin documentation', 'packeta' ) . '">' .
					esc_html__( 'Settings', 'packeta' ) . '</a>';

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
		esc_attr__( 'View Packeta documentation', 'packeta' ) . '">' .
		esc_html__( 'Documentation', 'packeta' ) . '</a>';

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

	/**
	 * Check for action parameter and process wanted action.
	 *
	 * @return void
	 */
	public function handleActions(): void {
		$action = $this->request->getQuery( self::PARAM_PACKETERY_ACTION );

		if ( Order\PacketActionsCommonLogic::ACTION_SUBMIT_PACKET === $action ) {
			$this->packetSubmitter->processAction();
		}

		if ( Order\PacketActionsCommonLogic::ACTION_CANCEL_PACKET === $action ) {
			$this->packetCanceller->processAction();
		}
	}
}
