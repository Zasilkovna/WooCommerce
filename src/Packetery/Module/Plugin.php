<?php
/**
 * Main Packeta plugin class.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Packetery\Core\CoreHelper;
use Packetery\Core\Entity\Order as PacketeryOrder;
use Packetery\Core\Log\ILogger;
use Packetery\Latte\Engine;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\OptionsPage;
use Packetery\Module\Exception\InvalidCarrierException;
use Packetery\Module\Options\FlagManager\FeatureFlagNotice;
use Packetery\Module\Options\FlagManager\FeatureFlagProvider;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order\CarrierModal;
use Packetery\Nette\Http\Request;
use WC_Email;
use WC_Order;

/**
 * Class Plugin
 *
 * @package Packetery
 */
class Plugin {

	public const VERSION                = '1.8.4';
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
	 * Metaboxes wrapper.
	 *
	 * @var Order\MetaboxesWrapper
	 */
	private $metaboxesWrapper;

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
	 * @var Api\Registrar
	 */
	private $apiRegistrar;

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
	 * @var OptionsProvider
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
	 * Packet claim submitter
	 *
	 * @var Order\PacketClaimSubmitter
	 */
	private $packetClaimSubmitter;

	/**
	 * Category form fields.
	 *
	 * @var ProductCategory\FormFields
	 */
	private $productCategoryFormFields;

	/**
	 * Packet auto submitter.
	 *
	 * @var Order\PacketAutoSubmitter
	 */
	private $packetAutoSubmitter;

	/**
	 * Feature flag provider.
	 *
	 * @var FeatureFlagProvider
	 */
	private $featureFlagProvider;

	/**
	 * Feature flag notice manager.
	 *
	 * @var FeatureFlagNotice
	 */
	private $featureFlagNotice;

	/**
	 * API extender.
	 *
	 * @var Order\ApiExtender
	 */
	private $apiExtender;

	/**
	 * Label print.
	 *
	 * @var Order\LabelPrintModal
	 */
	private $labelPrintModal;

	/**
	 * Hook handler.
	 *
	 * @var HookHandler
	 */
	private $hookHandler;

	/**
	 * Carrier Modal.
	 *
	 * @var CarrierModal
	 */
	private $carrierModal;

	/**
	 * Carrier options factory.
	 *
	 * @var CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/**
	 * Plugin constructor.
	 *
	 * @param Order\Metabox              $order_metabox             Order metabox.
	 * @param MessageManager             $message_manager           Message manager.
	 * @param Options\Page               $options_page              Options page.
	 * @param Checkout                   $checkout                  Checkout class.
	 * @param Engine                     $latte_engine              PacketeryLatte engine.
	 * @param OptionsPage                $carrierOptionsPage        Carrier options page.
	 * @param Order\BulkActions          $orderBulkActions          Order BulkActions.
	 * @param Order\LabelPrint           $labelPrint                Label printing.
	 * @param Order\GridExtender         $gridExtender              Order grid extender.
	 * @param Product\DataTab            $productTab                Product tab.
	 * @param Log\Page                   $logPage                   Log page.
	 * @param ILogger                    $logger                    Log manager.
	 * @param Api\Registrar              $apiRegistar               API endpoints registrar.
	 * @param Order\Modal                $orderModal                Order modal.
	 * @param Options\Exporter           $exporter                  Options exporter.
	 * @param Order\CollectionPrint      $orderCollectionPrint      Order collection print.
	 * @param Request                    $request                   HTTP request.
	 * @param Order\Repository           $orderRepository           Order repository.
	 * @param Upgrade                    $upgrade                   Plugin upgrade.
	 * @param QueryProcessor             $queryProcessor            QueryProcessor.
	 * @param OptionsProvider            $optionsProvider           Options provider.
	 * @param CronService                $cronService               Cron service.
	 * @param Order\PacketCanceller      $packetCanceller           Packet canceller.
	 * @param ContextResolver            $contextResolver           Context resolver.
	 * @param DashboardWidget            $dashboardWidget           Dashboard widget.
	 * @param Order\PacketSubmitter      $packetSubmitter           Packet submitter.
	 * @param Order\PacketClaimSubmitter $packetClaimSubmitter      Packet claim submitter.
	 * @param ProductCategory\FormFields $productCategoryFormFields Product category form fields.
	 * @param Order\PacketAutoSubmitter  $packetAutoSubmitter       Packet auto submitter.
	 * @param FeatureFlagProvider        $featureFlagProvider       Feature flag provider.
	 * @param FeatureFlagNotice          $featureFlagNotice         Feature flag notice manager.
	 * @param Order\MetaboxesWrapper     $metaboxesWrapper          Metaboxes wrapper.
	 * @param Order\ApiExtender          $apiExtender               API extender.
	 * @param Order\LabelPrintModal      $labelPrintModal           Label print modal.
	 * @param HookHandler                $hookHandler               Hook handler.
	 * @param CarrierModal               $carrierModal              Carrier Modal.
	 * @param CarrierOptionsFactory      $carrierOptionsFactory     Carrier options factory.
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
		Api\Registrar $apiRegistar,
		Order\Modal $orderModal,
		Options\Exporter $exporter,
		Order\CollectionPrint $orderCollectionPrint,
		Request $request,
		Order\Repository $orderRepository,
		Upgrade $upgrade,
		QueryProcessor $queryProcessor,
		OptionsProvider $optionsProvider,
		CronService $cronService,
		Order\PacketCanceller $packetCanceller,
		ContextResolver $contextResolver,
		DashboardWidget $dashboardWidget,
		Order\PacketSubmitter $packetSubmitter,
		Order\PacketClaimSubmitter $packetClaimSubmitter,
		ProductCategory\FormFields $productCategoryFormFields,
		Order\PacketAutoSubmitter $packetAutoSubmitter,
		FeatureFlagProvider $featureFlagProvider,
		FeatureFlagNotice $featureFlagNotice,
		Order\MetaboxesWrapper $metaboxesWrapper,
		Order\ApiExtender $apiExtender,
		Order\LabelPrintModal $labelPrintModal,
		HookHandler $hookHandler,
		CarrierModal $carrierModal,
		CarrierOptionsFactory $carrierOptionsFactory
	) {
		$this->options_page              = $options_page;
		$this->latte_engine              = $latte_engine;
		$this->main_file_path            = PACKETERY_PLUGIN_DIR . '/packeta.php';
		$this->order_metabox             = $order_metabox;
		$this->message_manager           = $message_manager;
		$this->checkout                  = $checkout;
		$this->carrierOptionsPage        = $carrierOptionsPage;
		$this->orderBulkActions          = $orderBulkActions;
		$this->labelPrint                = $labelPrint;
		$this->gridExtender              = $gridExtender;
		$this->productTab                = $productTab;
		$this->logPage                   = $logPage;
		$this->logger                    = $logger;
		$this->apiRegistrar              = $apiRegistar;
		$this->orderModal                = $orderModal;
		$this->exporter                  = $exporter;
		$this->orderCollectionPrint      = $orderCollectionPrint;
		$this->request                   = $request;
		$this->orderRepository           = $orderRepository;
		$this->upgrade                   = $upgrade;
		$this->queryProcessor            = $queryProcessor;
		$this->optionsProvider           = $optionsProvider;
		$this->cronService               = $cronService;
		$this->packetCanceller           = $packetCanceller;
		$this->contextResolver           = $contextResolver;
		$this->dashboardWidget           = $dashboardWidget;
		$this->packetSubmitter           = $packetSubmitter;
		$this->packetClaimSubmitter      = $packetClaimSubmitter;
		$this->productCategoryFormFields = $productCategoryFormFields;
		$this->packetAutoSubmitter       = $packetAutoSubmitter;
		$this->featureFlagProvider       = $featureFlagProvider;
		$this->metaboxesWrapper          = $metaboxesWrapper;
		$this->apiExtender               = $apiExtender;
		$this->labelPrintModal           = $labelPrintModal;
		$this->hookHandler               = $hookHandler;
		$this->carrierModal              = $carrierModal;
		$this->carrierOptionsFactory     = $carrierOptionsFactory;
		$this->featureFlagNotice         = $featureFlagNotice;
	}

	/**
	 * Gets list of multisite sites.
	 *
	 * @return array
	 */
	public static function getSites(): array {
		return get_sites(
			[
				'fields'            => 'ids',
				'number'            => 0,
				'update_site_cache' => false,
			]
		);
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

		add_action( 'before_woocommerce_init', [ $this, 'declareWooCommerceCompability' ] );
		add_action( 'init', [ $this->upgrade, 'check' ] );
		add_action( 'init', [ $this->logger, 'register' ] );
		add_action( 'init', [ $this->message_manager, 'init' ] );
		add_action( 'rest_api_init', [ $this->apiRegistrar, 'registerRoutes' ] );

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

		$wcEmailHook = $this->optionsProvider->getEmailHook();
		add_action( $wcEmailHook, [ $this, 'renderEmailFooter' ] );
		add_filter( 'woocommerce_shipping_methods', array( $this, 'add_shipping_method' ) );

		$orderListScreenId = 'woocommerce_page_wc-orders';
		add_filter( 'views_edit-shop_order', [ $this->gridExtender, 'addFilterLinks' ] );
		add_action( 'restrict_manage_posts', [ $this->gridExtender, 'renderOrderTypeSelect' ] );

		$wooCommerceVersion = ModuleHelper::getWooCommerceVersion();
		if ( null !== $wooCommerceVersion && version_compare( $wooCommerceVersion, '7.9.0', '>=' ) ) {
			add_filter( sprintf( 'views_%s', $orderListScreenId ), [ $this->gridExtender, 'addFilterLinks' ] );
			add_action( 'woocommerce_order_list_table_restrict_manage_orders', [ $this->gridExtender, 'renderOrderTypeSelect' ] );
		}

		$this->queryProcessor->register();

		add_filter( 'manage_edit-shop_order_columns', [ $this->gridExtender, 'addOrderListColumns' ] );
		add_filter( sprintf( 'manage_%s_columns', $orderListScreenId ), [ $this->gridExtender, 'addOrderListColumns' ] );
		add_action( 'manage_shop_order_posts_custom_column', [ $this->gridExtender, 'fillCustomOrderListColumns' ], 10, 2 );
		add_action( sprintf( 'manage_%s_custom_column', $orderListScreenId ), [ $this->gridExtender, 'fillCustomOrderListColumns' ], 10, 2 );

		if ( ! wp_doing_ajax() ) {
			add_action( 'admin_menu', [ $this, 'add_menu_pages' ] );
			add_action( 'admin_head', [ $this->labelPrint, 'hideFromMenus' ] );
			add_action( 'admin_head', [ $this->orderCollectionPrint, 'hideFromMenus' ] );
			add_action( 'admin_head', [ $this, 'renderConfirmModalTemplate' ] );
			$this->orderModal->register();
			$this->labelPrintModal->register();
			$this->metaboxesWrapper->register();
		}

		$this->checkout->register_hooks();
		$this->productTab->register();
		$this->cronService->register();
		$this->productCategoryFormFields->register();
		$this->packetAutoSubmitter->register();
		$this->apiExtender->register();
		$this->hookHandler->register();
		$this->carrierModal->register();

		add_action( 'woocommerce_admin_order_data_after_shipping_address', [ $this, 'renderDeliveryDetail' ] );
		add_action( 'woocommerce_order_details_after_order_table', [ $this, 'renderOrderDetail' ] );

		// Adding custom actions to dropdown in admin order list.
		add_filter( 'bulk_actions-edit-shop_order', [ $this->orderBulkActions, 'addActions' ], 20, 1 );
		add_filter( sprintf( 'bulk_actions-%s', $orderListScreenId ), [ $this->orderBulkActions, 'addActions' ], 20, 1 );
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
		add_filter( sprintf( 'handle_bulk_actions-%s', $orderListScreenId ), [ $this->orderBulkActions, 'handleActions' ], 10, 3 );
		// Print packets export result.
		add_action( 'admin_notices', [ $this->orderBulkActions, 'renderPacketsExportResult' ], self::MIN_LISTENER_PRIORITY );

		add_action( 'admin_init', [ $this->labelPrint, 'outputLabelsPdf' ] );
		add_action( 'admin_init', [ $this->orderCollectionPrint, 'print' ] );

		add_action( 'admin_init', [ $this->exporter, 'outputExportTxt' ] );
		add_action( 'admin_init', [ $this, 'handleActions' ] );

		add_action( 'deleted_post', [ $this->orderRepository, 'deletedPostHook' ], 10, 2 );
		$this->dashboardWidget->register();

		$this->packetSubmitter->registerCronAction();

		add_action( 'woocommerce_blocks_checkout_block_registration', [ $this, 'registerCheckoutBlock' ] );

		add_action( 'wp_ajax_get_settings', [ $this->checkout, 'createSettingsAjax' ] );
		add_action( 'wp_ajax_nopriv_get_settings', [ $this->checkout, 'createSettingsAjax' ] );

		add_action(
			'woocommerce_blocks_loaded',
			function () {
				if ( function_exists( 'woocommerce_store_api_register_update_callback' ) ) {
					woocommerce_store_api_register_update_callback(
						[
							'namespace' => 'packetery-js-hooks',
							'callback'  => [ $this, 'saveShippingAndPaymentMethodsToSession' ],
						]
					);
				}
			}
		);
		add_action( 'woocommerce_cart_calculate_fees', [ $this->checkout, 'applyCodSurgarche' ], 20, 1 );
	}

	/**
	 * Saves selected methods to session.
	 *
	 * @param array $data Provided data.
	 *
	 * @return void
	 */
	public function saveShippingAndPaymentMethodsToSession( array $data ): void {
		if ( isset( $data['shipping_method'] ) ) {
			WC()->session->set( 'packetery_checkout_shipping_method', $data['shipping_method'] );
		}

		if ( isset( $data['payment_method'] ) ) {
			WC()->session->set( 'packetery_checkout_payment_method', $data['payment_method'] );
		}

		WC()->cart->calculate_totals();
	}

	/**
	 * Declares plugin compability with features.
	 *
	 * @return void
	 */
	public function declareWooCommerceCompability(): void {
		if ( false === class_exists( FeaturesUtil::class ) ) {
			return;
		}

		// High-Performance Order Storage.
		FeaturesUtil::declare_compatibility( 'custom_order_tables', $this->main_file_path );
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
		return ModuleHelper::isPluginActive( 'woocommerce/woocommerce.php' );
	}

	/**
	 * Renders delivery detail for packetery orders.
	 *
	 * @param WC_Order $order WordPress order.
	 */
	public function renderDeliveryDetail( WC_Order $order ): void {
		try {
			$orderEntity = $this->orderRepository->getByWcOrder( $order );
		} catch ( InvalidCarrierException $exception ) {
			$orderEntity = null;
		}
		if ( null === $orderEntity ) {
			return;
		}

		$carrierId      = $orderEntity->getCarrier()->getId();
		$carrierOptions = $this->carrierOptionsFactory->createByCarrierId( $carrierId );

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/delivery-detail.latte',
			[
				'pickupPoint'              => $orderEntity->getPickupPoint(),
				'validatedDeliveryAddress' => $orderEntity->getValidatedDeliveryAddress(),
				'carrierAddressValidation' => $carrierOptions->getAddressValidation(),
				'isExternalCarrier'        => $orderEntity->isExternalCarrier(),
				'translations'             => [
					'packeta'                => __( 'Packeta', 'packeta' ),
					'pickupPointDetail'      => __( 'Pickup Point Detail', 'packeta' ),
					'name'                   => __( 'Name', 'packeta' ),
					'address'                => __( 'Address', 'packeta' ),
					'pickupPointDetailCaps'  => __( 'Pickup Point Detail', 'packeta' ),
					'addressWasNotValidated' => __( 'Address was not validated', 'packeta' ),
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
		$order = $this->orderRepository->getByWcOrder( $wcOrder, true );
		if ( null === $order ) {
			return;
		}

		if ( $this->shouldHidePacketaInfo( $order ) ) {
			return;
		}

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/detail.latte',
			[
				'displayPickupPointInfo' => $this->shouldDisplayPickupPointInfo(),
				'order'                  => $order,
				'translations'           => [
					'packeta'              => __( 'Packeta', 'packeta' ),
					'pickupPointName'      => __( 'Pickup Point Name', 'packeta' ),
					'pickupPointDetail'    => __( 'Pickup Point Detail', 'packeta' ),
					'address'              => __( 'Address', 'packeta' ),
					'packetTrackingOnline' => __( 'Packet tracking online', 'packeta' ),
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
		$wcOrder = null;
		if ( ( $email instanceof WC_Email ) && ( $email->object instanceof WC_Order ) ) {
			$wcOrder = $email->object;
		}

		if ( $email instanceof WC_Order ) {
			$wcOrder = $email;
		}

		if ( null === $wcOrder ) {
			return;
		}

		try {
			$packeteryOrder = $this->orderRepository->getByWcOrder( $wcOrder );
		} catch ( InvalidCarrierException $exception ) {
			$packeteryOrder = null;
		}
		if ( null === $packeteryOrder ) {
			return;
		}

		if ( $this->shouldHidePacketaInfo( $packeteryOrder ) ) {
			return;
		}

		$templateParams = [
			'displayPickupPointInfo' => $this->shouldDisplayPickupPointInfo(),
			'order'                  => $packeteryOrder,
			'translations'           => [
				'packeta'              => __( 'Packeta', 'packeta' ),
				'pickupPointDetail'    => __( 'Pickup Point Detail', 'packeta' ),
				'pickupPointName'      => __( 'Pickup Point Name', 'packeta' ),
				'link'                 => __( 'Link', 'packeta' ),
				'pickupPointAddress'   => __( 'Pickup Point Address', 'packeta' ),
				'packetTrackingOnline' => __( 'Packet tracking online', 'packeta' ),
			],
		];
		$emailHtml      = $this->latte_engine->renderToString(
			PACKETERY_PLUGIN_DIR . '/template/email/order.latte',
			$templateParams
		);
		/**
		 * This filter allows you to change the HTML of e-mail footer.
		 *
		 * @since 1.5.3
		 */
		ModuleHelper::renderString( (string) apply_filters( 'packeta_email_footer', $emailHtml, $templateParams ) );
	}

	/**
	 * Determines if a Packeta order should be displayed.
	 *
	 * @param PacketeryOrder $order Order.
	 *
	 * @return bool
	 */
	private function shouldHidePacketaInfo( PacketeryOrder $order ): bool {
		$isPickupPointInfoVisible = $this->shouldDisplayPickupPointInfo() && $order->getPickupPoint();

		return ( ! $isPickupPointInfoVisible ) && false === $order->isExported();
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
	 * @return string|null
	 */
	public static function buildAssetUrl( string $asset ): ?string {
		$url      = plugin_dir_url( PACKETERY_PLUGIN_DIR . '/packeta.php' ) . $asset;
		$filename = PACKETERY_PLUGIN_DIR . '/' . $asset;

		if ( ! file_exists( $filename ) ) {
			return null;
		}

		return add_query_arg( [ 'v' => md5( (string) filemtime( $filename ) ) ], $url );
	}

	/**
	 * Enqueues javascript files and stylesheets for checkout.
	 */
	public function enqueueFrontAssets(): void {
		if ( is_checkout() ) {
			$this->enqueueStyle( 'packetery-front-styles', 'public/css/front.css' );
			$this->enqueueStyle( 'packetery-custom-front-styles', 'public/css/custom-front.css' );
			if ( $this->checkout->areBlocksUsedInCheckout() ) {
				wp_enqueue_script( 'packetery-widget-library', 'https://widget.packeta.com/v6/www/js/library.js', [], self::VERSION, false );
			} else {
				$this->enqueueScript( 'packetery-checkout', 'public/js/checkout.js', true, [ 'jquery' ] );
			}
			wp_localize_script( 'packetery-checkout', 'packeteryCheckoutSettings', $this->checkout->createSettings() );
		}
	}

	/**
	 * Enqueues javascript files and stylesheets for administration.
	 */
	public function enqueueAdminAssets(): void {
		$page                  = $this->request->getQuery( 'page' );
		$isOrderGridPage       = $this->contextResolver->isOrderGridPage();
		$isOrderDetailPage     = $this->contextResolver->isOrderDetailPage();
		$isProductCategoryPage = $this->contextResolver->isProductCategoryDetailPage() || $this->contextResolver->isProductCategoryGridPage();
		$datePickerSettings    = [
			'deliverOnMinDate' => wp_date( CoreHelper::DATEPICKER_FORMAT, strtotime( 'tomorrow' ) ),
			'dateFormat'       => CoreHelper::DATEPICKER_FORMAT_JS,
		];

		if ( $isOrderGridPage || $isOrderDetailPage || in_array( $page, [ Carrier\OptionsPage::SLUG, Options\Page::SLUG ], true ) ) {
			$this->enqueueScript( 'live-form-validation-options', 'public/js/live-form-validation-options.js', false );
			$this->enqueueScript( 'live-form-validation', 'public/libs/live-form-validation/live-form-validation.js', false, [ 'live-form-validation-options' ] );
			$this->enqueueScript( 'live-form-validation-extension', 'public/js/live-form-validation-extension.js', false, [ 'live-form-validation' ] );
		}

		if ( in_array( $page, [ Carrier\OptionsPage::SLUG, Options\Page::SLUG ], true ) ) {
			$this->enqueueStyle( 'packetery-select2-css', 'public/libs/select2-4.0.13/dist.min.css' );
			$this->enqueueScript( 'packetery-select2', 'public/libs/select2-4.0.13/dist.min.js', true, [ 'jquery' ] );
		}

		if ( Carrier\OptionsPage::SLUG === $page ) {
			$this->enqueueScript( 'packetery-multiplier', 'public/js/multiplier.js', true, [ 'jquery', 'live-form-validation-extension' ] );
			$this->enqueueScript( 'packetery-admin-country-carrier', 'public/js/admin-country-carrier.js', true, [ 'jquery', 'packetery-multiplier', 'packetery-select2' ] );
		}

		if ( Options\Page::SLUG === $page ) {
			$this->enqueueScript( 'packetery-admin-options', 'public/js/admin-options.js', true, [ 'jquery', 'packetery-select2' ] );
		}

		$isProductPage = $this->contextResolver->isProductPage();
		$isPageDetail  = $this->contextResolver->isPageDetail();
		$screen        = get_current_screen();
		$isDashboard   = ( $screen && 'dashboard' === $screen->id );

		if (
			$isOrderGridPage || $isOrderDetailPage || $isProductPage || $isProductCategoryPage || $isDashboard || $isPageDetail ||
			in_array(
				$page,
				[
					Options\Page::SLUG,
					Carrier\OptionsPage::SLUG,
					Log\Page::SLUG,
					Order\labelPrint::MENU_SLUG,
				],
				true
			)
		) {
			$this->enqueueStyle( 'packetery-admin-styles', 'public/css/admin.css' );
			// It is placed here so that typenow in contextResolver works and there is no need to repeat the conditions.
			if ( $this->featureFlagProvider->shouldShowSplitActivationNotice() ) {
				add_action( 'admin_notices', [ $this->featureFlagNotice, 'renderSplitActivationNotice' ] );
			}
		}

		if ( $isOrderGridPage ) {
			$orderGridPageSettings = [
				'translations' => [
					'hasToFillCustomsDeclaration' => __( 'Customs declaration has to be filled in order detail.', 'packeta' ),
					'packetSubmissionNotPossible' => __( 'It is not possible to submit the shipment because all the information required for this shipment is not filled.', 'packeta' ),
				],
			];
			$this->enqueueScript( 'packetery-admin-grid-order-edit-js', 'public/js/admin-grid-order-edit.js', true, [ 'jquery', 'wp-util', 'backbone' ] );
			wp_localize_script( 'packetery-admin-grid-order-edit-js', 'datePickerSettings', $datePickerSettings );
			wp_localize_script( 'packetery-admin-grid-order-edit-js', 'settings', $orderGridPageSettings );
		}

		$pickupPointPickerSettings = null;
		$addressPickerSettings     = null;

		if ( $isOrderDetailPage ) {
			$this->enqueueScript( 'packetery-multiplier', 'public/js/multiplier.js', true, [ 'jquery', 'live-form-validation-extension' ] );
			$this->enqueueScript( 'admin-order-detail', 'public/js/admin-order-detail.js', true, [ 'jquery', 'packetery-multiplier', 'live-form-validation-extension' ] );
			wp_localize_script( 'admin-order-detail', 'datePickerSettings', $datePickerSettings );
			$pickupPointPickerSettings = $this->order_metabox->getPickupPointWidgetSettings();
			$addressPickerSettings     = $this->order_metabox->getAddressWidgetSettings();
		}

		if ( null !== $pickupPointPickerSettings || null !== $addressPickerSettings ) {
			wp_enqueue_script( 'packetery-widget-library', 'https://widget.packeta.com/v6/www/js/library.js', [], self::VERSION, true );
		}

		if ( null !== $pickupPointPickerSettings ) {
			$this->enqueueScript( 'packetery-admin-pickup-point-picker', 'public/js/admin-pickup-point-picker.js', true, [ 'jquery', 'packetery-widget-library' ] );
			wp_localize_script( 'packetery-admin-pickup-point-picker', 'packeteryPickupPointPickerSettings', $pickupPointPickerSettings );
		}

		if ( null !== $addressPickerSettings ) {
			$this->enqueueScript( 'packetery-admin-address-picker', 'public/js/admin-address-picker.js', true, [ 'jquery', 'packetery-widget-library' ] );
			wp_localize_script( 'packetery-admin-address-picker', 'packeteryAddressPickerSettings', $addressPickerSettings );
		}

		if ( $this->contextResolver->isPacketeryConfirmPage() ) {
			$this->enqueueScript( 'packetery-confirm', 'public/js/confirm.js', true, [ 'jquery', 'backbone' ] );
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
	public static function getLocale(): string {
		/**
		 * Applies plugin_locale filters.
		 *
		 * @since 1.0.0
		 */
		return (string) apply_filters(
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
		unload_textdomain( $domain );
		$locale = self::getLocale();
		$moFile = WP_LANG_DIR . "/plugins/$domain-$locale.mo";

		if ( file_exists( $moFile ) ) {
			load_plugin_textdomain( $domain );
		} else {
			load_default_textdomain();
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

		// This hook is tested.
		add_filter(
			'__experimental_woocommerce_blocks_add_data_attributes_to_block',
			function ( $allowed_blocks ) {
				$allowed_blocks[] = 'packeta/packeta-widget';
				return $allowed_blocks;
			},
			10,
			1
		);
		// This hook is expected replacement in the future.
		add_filter(
			'woocommerce_blocks_add_data_attributes_to_block',
			function ( $allowed_blocks ) {
				$allowed_blocks[] = 'packeta/packeta-widget';
				return $allowed_blocks;
			},
			10,
			1
		);
	}

	/**
	 * Uninstalls plugin and drops custom database table.
	 * Only a static class method or function can be used in an uninstall hook.
	 */
	public static function uninstall(): void {
		if ( defined( 'PACKETERY_DEBUG' ) && PACKETERY_DEBUG === true ) {
			return;
		}

		if ( is_multisite() ) {
			self::cleanUpRepositoriesForMultisite();
		} else {
			self::cleanUpRepositories();
		}
	}

	/**
	 * Drops all plugin tables when Multisite is enabled.
	 *
	 * @return void
	 */
	private static function cleanUpRepositoriesForMultisite(): void {
		$sites = self::getSites();

		foreach ( $sites as $site ) {
			switch_to_blog( $site );
			self::cleanUpRepositories();
			restore_current_blog();
		}
	}

	/**
	 * Drops all plugin tables for a single site.
	 *
	 * @return void
	 */
	private static function cleanUpRepositories(): void {
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

		$customsDeclarationItemsRepository = $container->getByType( CustomsDeclaration\Repository::class );
		$customsDeclarationItemsRepository->dropItems();

		$customsDeclarationRepository = $container->getByType( CustomsDeclaration\Repository::class );
		$customsDeclarationRepository->drop();
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
		return 'WordPress-' . get_bloginfo( 'version' ) . '-Woocommerce-' . WC_VERSION . '-Packeta-' . self::VERSION;
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

		if ( Order\PacketActionsCommonLogic::ACTION_SUBMIT_PACKET_CLAIM === $action ) {
			$this->packetClaimSubmitter->processAction();
		}

		if ( Order\PacketActionsCommonLogic::ACTION_CANCEL_PACKET === $action ) {
			$this->packetCanceller->processAction();
		}

		if ( FeatureFlagNotice::ACTION_HIDE_SPLIT_MESSAGE === $action ) {
			$this->featureFlagProvider->dismissSplitActivationNotice();
		}
	}

	/**
	 * Registers checkout block.
	 *
	 * @param IntegrationRegistry $integrationRegistry Integration registry.
	 *
	 * @return void
	 */
	public function registerCheckoutBlock( IntegrationRegistry $integrationRegistry ): void {
		$integrationRegistry->register(
			new Blocks\WidgetIntegration(
				$this->checkout->createSettings()
			)
		);
	}
}
