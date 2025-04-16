<?php

declare( strict_types=1 );

namespace Packetery\Module\Hooks;

use Packetery\Module\Api;
use Packetery\Module\Blocks\BlockHooks;
use Packetery\Module\Carrier\OptionsPage;
use Packetery\Module\Checkout\Checkout;
use Packetery\Module\Checkout\CheckoutSettings;
use Packetery\Module\CronService;
use Packetery\Module\Dashboard\DashboardPage;
use Packetery\Module\DashboardWidget;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Log;
use Packetery\Module\MessageManager;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Options;
use Packetery\Module\Options\OptionNames;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order;
use Packetery\Module\Order\CarrierModal;
use Packetery\Module\Order\CollectionPrint;
use Packetery\Module\Order\GridExtender;
use Packetery\Module\Order\LabelPrint;
use Packetery\Module\Order\LabelPrintModal;
use Packetery\Module\Order\MetaboxesWrapper;
use Packetery\Module\Order\PacketAutoSubmitter;
use Packetery\Module\Order\PacketSubmitter;
use Packetery\Module\Order\PacketSynchronizer;
use Packetery\Module\Order\StoredUntilModal;
use Packetery\Module\Product;
use Packetery\Module\ProductCategory;
use Packetery\Module\QueryProcessor;
use Packetery\Module\Shipping\ShippingProvider;
use Packetery\Module\ShippingMethod;
use Packetery\Module\Upgrade;
use Packetery\Module\Views\AssetManager;
use Packetery\Module\Views\ViewAdmin;
use Packetery\Module\Views\ViewFrontend;
use Packetery\Module\Views\ViewMail;
use Packetery\Module\Views\WizardAssetManager;

class HookRegistrar {

	private const MIN_LISTENER_PRIORITY = - 9998;

	/**
	 * @var DashboardWidget
	 */
	private $dashboardWidget;

	/**
	 * @var MetaboxesWrapper
	 */
	private $metaboxesWrapper;

	/**
	 * @var MessageManager
	 */
	private $messageManager;

	/**
	 * @var Checkout
	 */
	private $checkout;

	/**
	 * @var Order\BulkActions
	 */
	private $orderBulkActions;

	/**
	 * @var LabelPrint
	 */
	private $labelPrint;

	/**
	 * @var CollectionPrint
	 */
	private $orderCollectionPrint;

	/**
	 * @var GridExtender
	 */
	private $gridExtender;

	/**
	 * @var Product\DataTab
	 */
	private $productTab;

	/**
	 * @var CronService
	 */
	private $cronService;

	/**
	 * @var Api\Registrar
	 */
	private $apiRegistrar;

	/**
	 * @var Order\Modal
	 */
	private $orderModal;

	/**
	 * @var Options\Exporter
	 */
	private $exporter;

	/**
	 * @var Order\Repository
	 */
	private $orderRepository;

	/**
	 * @var Upgrade
	 */
	private $upgrade;

	/**
	 * @var QueryProcessor
	 */
	private $queryProcessor;

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * @var PacketSubmitter
	 */
	private $packetSubmitter;

	/**
	 * @var ProductCategory\FormFields
	 */
	private $productCategoryFormFields;

	/**
	 * @var PacketAutoSubmitter
	 */
	private $packetAutoSubmitter;

	/**
	 * @var Order\ApiExtender
	 */
	private $apiExtender;

	/**
	 * @var LabelPrintModal
	 */
	private $labelPrintModal;

	/**
	 * @var UpdateOrderHook
	 */
	private $updateOrderHook;

	/**
	 * @var CarrierModal
	 */
	private $carrierModal;

	/**
	 * @var StoredUntilModal
	 */
	private $storedUntilModal;

	/**
	 * @var BlockHooks
	 */
	private $blockHooks;

	/**
	 * @var ViewAdmin
	 */
	private $viewAdmin;

	/**
	 * @var ViewFrontend
	 */
	private $viewFrontend;

	/**
	 * @var ViewMail
	 */
	private $viewMail;

	/**
	 * @var AssetManager
	 */
	private $assetManager;

	/**
	 * @var PluginHooks
	 */
	private $pluginHooks;

	/**
	 * @var OptionsPage
	 */
	private $carrierOptionsPage;

	/**
	 * @var Log\Page
	 */
	private $logPage;

	/**
	 * @var Options\Page
	 */
	private $optionsPage;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var PacketSynchronizer
	 */
	private $packetSynchronizer;

	/**
	 * @var CheckoutSettings
	 */
	private $checkoutSettings;

	/**
	 * @var ModuleHelper
	 */
	private $moduleHelper;

	/**
	 * @var ShippingProvider
	 */
	private $shippingProvider;

	/**
	 * @var WizardAssetManager
	 */
	private $wizardAssetManager;

	/**
	 * @var DashboardPage
	 */
	private $dashboardPage;

	public function __construct(
		PluginHooks $pluginHooks,
		MessageManager $messageManager,
		Checkout $checkout,
		Order\BulkActions $orderBulkActions,
		Order\LabelPrint $labelPrint,
		GridExtender $gridExtender,
		Product\DataTab $productTab,
		Api\Registrar $apiRegistar,
		Order\Modal $orderModal,
		Options\Exporter $exporter,
		Order\CollectionPrint $orderCollectionPrint,
		Order\Repository $orderRepository,
		Upgrade $upgrade,
		QueryProcessor $queryProcessor,
		OptionsProvider $optionsProvider,
		CronService $cronService,
		DashboardWidget $dashboardWidget,
		PacketSubmitter $packetSubmitter,
		ProductCategory\FormFields $productCategoryFormFields,
		PacketAutoSubmitter $packetAutoSubmitter,
		Order\MetaboxesWrapper $metaboxesWrapper,
		Order\ApiExtender $apiExtender,
		LabelPrintModal $labelPrintModal,
		UpdateOrderHook $updateOrderHook,
		CarrierModal $carrierModal,
		StoredUntilModal $storedUntilModal,
		BlockHooks $blockHooks,
		ViewAdmin $viewAdmin,
		ViewFrontend $viewFrontend,
		ViewMail $viewMail,
		AssetManager $assetManager,
		OptionsPage $carrierOptionsPage,
		Log\Page $logPage,
		Options\Page $optionsPage,
		WpAdapter $wpAdapter,
		PacketSynchronizer $packetSynchronizer,
		CheckoutSettings $checkoutSettings,
		ModuleHelper $moduleHelper,
		ShippingProvider $shippingProvider,
		WizardAssetManager $wizardAssetManager,
		DashboardPage $dashboardPage
	) {
		$this->messageManager            = $messageManager;
		$this->checkout                  = $checkout;
		$this->orderBulkActions          = $orderBulkActions;
		$this->labelPrint                = $labelPrint;
		$this->gridExtender              = $gridExtender;
		$this->productTab                = $productTab;
		$this->apiRegistrar              = $apiRegistar;
		$this->orderModal                = $orderModal;
		$this->exporter                  = $exporter;
		$this->orderCollectionPrint      = $orderCollectionPrint;
		$this->orderRepository           = $orderRepository;
		$this->upgrade                   = $upgrade;
		$this->queryProcessor            = $queryProcessor;
		$this->optionsProvider           = $optionsProvider;
		$this->cronService               = $cronService;
		$this->dashboardWidget           = $dashboardWidget;
		$this->packetSubmitter           = $packetSubmitter;
		$this->productCategoryFormFields = $productCategoryFormFields;
		$this->packetAutoSubmitter       = $packetAutoSubmitter;
		$this->metaboxesWrapper          = $metaboxesWrapper;
		$this->apiExtender               = $apiExtender;
		$this->labelPrintModal           = $labelPrintModal;
		$this->updateOrderHook           = $updateOrderHook;
		$this->carrierModal              = $carrierModal;
		$this->storedUntilModal          = $storedUntilModal;
		$this->blockHooks                = $blockHooks;
		$this->viewAdmin                 = $viewAdmin;
		$this->viewFrontend              = $viewFrontend;
		$this->viewMail                  = $viewMail;
		$this->assetManager              = $assetManager;
		$this->pluginHooks               = $pluginHooks;
		$this->carrierOptionsPage        = $carrierOptionsPage;
		$this->logPage                   = $logPage;
		$this->optionsPage               = $optionsPage;
		$this->wpAdapter                 = $wpAdapter;
		$this->packetSynchronizer        = $packetSynchronizer;
		$this->checkoutSettings          = $checkoutSettings;
		$this->moduleHelper              = $moduleHelper;
		$this->shippingProvider          = $shippingProvider;
		$this->wizardAssetManager        = $wizardAssetManager;
		$this->dashboardPage             = $dashboardPage;
	}

	public function register(): void {
		$this->wpAdapter->addAction( 'init', [ $this->pluginHooks, 'loadTranslation' ] );

		if ( $this->moduleHelper->isWooCommercePluginActive() === false ) {
			if ( $this->wpAdapter->isAdmin() ) {
				$this->wpAdapter->addAction( 'admin_notices', [ $this->viewAdmin, 'echoInactiveWooCommerceNotice' ] );
			}

			return;
		}

		$this->wpAdapter->addAction( 'before_woocommerce_init', [ $this->pluginHooks, 'declareWooCommerceCompability' ] );
		$this->wpAdapter->addAction( 'init', [ $this->upgrade, 'check' ] );
		$this->wpAdapter->addAction( 'rest_api_init', [ $this->apiRegistrar, 'registerRoutes' ] );

		$this->wpAdapter->registerActivationHook( ModuleHelper::getPluginMainFilePath(), [ $this, 'activatePlugin' ] );

		$this->wpAdapter->registerDeactivationHook(
			ModuleHelper::getPluginMainFilePath(),
			static function () {
				CronService::deactivate();
			}
		);

		if ( $this->wpAdapter->isAdmin() ) {
			$this->registerBackEnd();
		} else {
			$this->registerFrontEnd();
		}

		/*
		TODO: delete the folder
		// https://github.com/woocommerce/woocommerce/blob/trunk/docs/product-editor-development/how-to-guides/custom-field-tutorial.md
		add_action(
			'init',
			function () {
				if ( isset( $_GET['page'] ) && $_GET['page'] === 'wc-admin' ) {
					// This points to the directory that contains your block.json.
					BlockRegistry::get_instance()->register_block_type_from_metadata( PACKETERY_PLUGIN_DIR . '/public/block-product' );
				}
			}
		);
		*/

		/*
		TODO: use group (tab) instead of shipping section?
		// https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Admin/Features/ProductBlockEditor/ProductTemplates/README.md/
		add_action(
			'woocommerce_block_template_area_product-form_after_add_block_general',
			function ( BlockInterface $general_group ) {
				$parent = $general_group->get_parent();

				$parent->add_group(
					[
						'id'         => 'YOUR-PREFIX-group',
						'order'      => $general_group->get_order() + 5,
						'attributes' => [
							'title' => __( 'My Group', 'YOUR-TEXT-DOMAIN' ),
						],
					]
				);
			}
		);
		*/

		add_action(
			'woocommerce_layout_template_after_instantiation',
			function ( $layout_template_id, $layout_template_area, $layout_template ) {
				$shippingTab = $layout_template->get_group_by_id( 'shipping' );

				if ( $shippingTab ) {
					// Creating a new section, this is optional.
					$packetaSection = $shippingTab->add_section(
						[
							'id'         => 'packeta-section',
							'order'      => 20, // TODO: use filter
							'attributes' => [
								'title'       => __( 'Packeta', 'packeta' ),
								'description' => __( 'Product settings for Packeta plugin', 'packeta' ),
							],
						]
					);

					// https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/product-editor/src/blocks/generic/README.md
					$packetaSection->add_block(
						[
							'id'         => Product\Entity::META_AGE_VERIFICATION_18_PLUS,
							'blockName'  => 'woocommerce/product-toggle-field',
							'order'      => 15,
							'attributes' => [
								'property'       => Product\Entity::META_AGE_VERIFICATION_18_PLUS,
								'checkedValue'   => true,
								'uncheckedValue' => false,
								'label'          => __( 'This product requires age verification', 'packeta' ),
								'uncheckedHelp'  => __( 'TODO: Description shown when not on', 'packeta' ),
							],
						]
					);

					// TODO: logic from Product\DataTab for disallowed carriers
					$carrierRestrictionCheckboxId = 'packeta_carrier_foo_disabled';
					$packetaSection->add_block(
						[
							'id'         => $carrierRestrictionCheckboxId,
							'blockName'  => 'woocommerce/product-checkbox-field',
							'order'      => 20,
							'attributes' => [
								'title'          => __( 'Example carrier restriction', 'packeta' ),
								'label'          => __( 'Carrier Foo disabled for this product', 'packeta' ),
								'property'       => $carrierRestrictionCheckboxId,
								'checkedValue'   => true,
								'uncheckedValue' => false,
								'tooltip'        => __(
									'When checked, customers will not be able to send this product using carrier Foo.',
									'packeta'
								),
							],
						]
					);
				}
			},
			10,
			3
		);

		// https://gist.github.com/mattsherman/8b3e67068d247f9825a4b1ca5caf2602
		add_filter(
			'woocommerce_rest_prepare_product_object',
			function ( $response, $post ) {
				$postMeta = get_post_meta( $post->get_id() );
				// todo: why is it array?
				$response->data[ Product\Entity::META_AGE_VERIFICATION_18_PLUS ] =
				isset( $postMeta[ Product\Entity::META_AGE_VERIFICATION_18_PLUS ][0] ) ? (bool) $postMeta[ Product\Entity::META_AGE_VERIFICATION_18_PLUS ][0] : null;

				// TODO: logic from Product\DataTab for disallowed carriers

				return $response;
			},
			10,
			2
		);

		add_filter(
			'woocommerce_rest_insert_product_object',
			function ( $product, $request ) {
				$productId = $product->get_id();
				$params    = $request->get_params();
				// $postMeta   = get_post_meta( $productId );

				if ( isset( $params[ Product\Entity::META_AGE_VERIFICATION_18_PLUS ] ) ) {
					update_post_meta( $productId, Product\Entity::META_AGE_VERIFICATION_18_PLUS, (bool) $params[ Product\Entity::META_AGE_VERIFICATION_18_PLUS ] );
				}
				// TODO: logic from Product\DataTab for disallowed carriers
			},
			10,
			2
		);

		// more good links:
		// https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Admin/Features/ProductBlockEditor/ProductTemplates/README.md/
		// https://github.com/woocommerce/woocommerce/blob/trunk/docs/product-editor-development/how-to-guides/generic-fields-tutorial.md

		$this->wpAdapter->addFilter( 'woocommerce_shipping_methods', [ $this, 'addShippingMethods' ] );
		$this->cronService->register();
		$this->packetAutoSubmitter->register();
		$this->apiExtender->register();
		$this->updateOrderHook->register();
		$this->packetSubmitter->registerCronAction();
		$this->packetSynchronizer->register();

		add_action( 'init', [ $this->shippingProvider, 'loadClasses' ] );

		add_action( 'packeta_create_tables', [ $this->upgrade, 'runCreateTables' ] );
	}

	private function registerBackEnd(): void {
		if ( $this->wpAdapter->doingAjax() === false ) {
			$this->wpAdapter->addAction( 'admin_init', [ $this, 'redirectAfterActivation' ] );
			$this->wpAdapter->addAction( 'init', [ $this->messageManager, 'init' ] );
			$this->wpAdapter->addAction( 'admin_enqueue_scripts', [ $this->assetManager, 'enqueueAdminAssets' ] );
			$this->wpAdapter->addAction( 'admin_enqueue_scripts', [ $this->wizardAssetManager, 'enqueueWizardAssets' ] );
			$this->wpAdapter->addAction(
				'admin_notices',
				function () {
					$this->messageManager->render( MessageManager::RENDERER_WORDPRESS );
				},
				self::MIN_LISTENER_PRIORITY
			);
			$this->wpAdapter->addAction( 'init', [ $this, 'addLinksToPluginGrid' ] );
			$this->wpAdapter->addFilter( 'views_edit-shop_order', [ $this->gridExtender, 'addFilterLinks' ] );
			$this->wpAdapter->addAction( 'restrict_manage_posts', [ $this->gridExtender, 'renderOrderTypeSelect' ] );
			$this->wpAdapter->addFilter( 'manage_edit-shop_order_columns', [ $this->gridExtender, 'addOrderListColumns' ] );

			$orderListScreenId = 'woocommerce_page_wc-orders';

			$wooCommerceVersion = ModuleHelper::getWooCommerceVersion();
			if ( $wooCommerceVersion !== null && version_compare( $wooCommerceVersion, '7.9.0', '>=' ) ) {
				$this->wpAdapter->addFilter( sprintf( 'views_%s', $orderListScreenId ), [ $this->gridExtender, 'addFilterLinks' ] );
				$this->wpAdapter->addAction(
					'woocommerce_order_list_table_restrict_manage_orders',
					[
						$this->gridExtender,
						'renderOrderTypeSelect',
					]
				);
			}

			$this->queryProcessor->register();

			$this->wpAdapter->addFilter(
				sprintf( 'manage_%s_columns', $orderListScreenId ),
				[
					$this->gridExtender,
					'addOrderListColumns',
				]
			);
			$this->wpAdapter->addAction(
				'manage_shop_order_posts_custom_column',
				[
					$this->gridExtender,
					'fillCustomOrderListColumns',
				],
				10,
				2
			);
			$this->wpAdapter->addAction(
				sprintf( 'manage_%s_custom_column', $orderListScreenId ),
				[
					$this->gridExtender,
					'fillCustomOrderListColumns',
				],
				10,
				2
			);

			$this->wpAdapter->addFilter(
				'manage_edit-shop_order_sortable_columns',
				[
					$this->gridExtender,
					'makeOrderListSpecificColumnsSortable',
				]
			);
			$this->wpAdapter->addFilter(
				sprintf( 'manage_%s_sortable_columns', $orderListScreenId ),
				[
					$this->gridExtender,
					'makeOrderListSpecificColumnsSortable',
				]
			);

			$this->optionsPage->register();
			$this->wpAdapter->addAction( 'admin_menu', [ $this, 'addMenuPages' ] );
			$this->wpAdapter->addAction( 'admin_head', [ $this->labelPrint, 'hideFromMenus' ] );
			$this->wpAdapter->addAction( 'admin_head', [ $this->orderCollectionPrint, 'hideFromMenus' ] );

			$this->wpAdapter->addAction( 'admin_head', [ $this->viewAdmin, 'renderConfirmModalTemplate' ] );

			$this->orderModal->register();
			$this->labelPrintModal->register();
			$this->metaboxesWrapper->register();
			$this->storedUntilModal->register();
			$this->productTab->register();
			$this->productCategoryFormFields->register();
			$this->carrierModal->register();

			$this->wpAdapter->addAction(
				'woocommerce_admin_order_data_after_shipping_address',
				[
					$this->viewAdmin,
					'renderDeliveryDetail',
				]
			);

			// Adding custom actions to dropdown in admin order list.
			$this->wpAdapter->addFilter( 'bulk_actions-edit-shop_order', [ $this->orderBulkActions, 'addActions' ], 20 );
			$this->wpAdapter->addFilter(
				sprintf( 'bulk_actions-%s', $orderListScreenId ),
				[
					$this->orderBulkActions,
					'addActions',
				],
				20
			);
			// Execute the action for selected orders.
			$this->wpAdapter->addFilter(
				'handle_bulk_actions-edit-shop_order',
				[
					$this->orderBulkActions,
					'handleActions',
				],
				10,
				3
			);
			$this->wpAdapter->addFilter(
				sprintf( 'handle_bulk_actions-%s', $orderListScreenId ),
				[
					$this->orderBulkActions,
					'handleActions',
				],
				10,
				3
			);
			// Print packets export result.
			$this->wpAdapter->addAction(
				'admin_notices',
				[
					$this->orderBulkActions,
					'renderPacketsExportResult',
				],
				self::MIN_LISTENER_PRIORITY
			);
			$this->wpAdapter->addAction( 'admin_init', [ $this->labelPrint, 'outputLabelsPdf' ] );
			$this->wpAdapter->addAction( 'admin_init', [ $this->orderCollectionPrint, 'print' ] );

			$this->wpAdapter->addAction( 'admin_init', [ $this->exporter, 'outputExportTxt' ] );
			$this->wpAdapter->addAction( 'admin_init', [ $this->pluginHooks, 'handleActions' ] );

			$this->wpAdapter->addAction( 'deleted_post', [ $this->orderRepository, 'deletedPostHook' ], 10, 2 );
			$this->dashboardWidget->register();
		} else {
			// For blocks, used at frontend.
			$this->wpAdapter->addAction( 'wp_ajax_get_settings', [ $this->checkoutSettings, 'actionCreateSettingsAjax' ] );
			$this->wpAdapter->addAction(
				'wp_ajax_nopriv_get_settings',
				[
					$this->checkoutSettings,
					'actionCreateSettingsAjax',
				]
			);
		}
	}

	private function registerFrontEnd(): void {
		$this->checkout->registerHooks();

		$wcEmailHook = $this->optionsProvider->getEmailHook();
		$this->wpAdapter->addAction( $wcEmailHook, [ $this->viewMail, 'renderEmailFooter' ] );

		$this->wpAdapter->addAction( 'wp_enqueue_scripts', [ $this->assetManager, 'enqueueFrontAssets' ] );
		$this->wpAdapter->addAction(
			'woocommerce_cart_calculate_fees',
			[
				$this->checkout,
				'actionCalculateFees',
			],
			20
		);
		if ( $this->wpAdapter->doingAjax() === false ) {
			$this->wpAdapter->addAction( 'woocommerce_order_details_after_order_table', [ $this->viewFrontend, 'renderOrderDetail' ] );

			$this->wpAdapter->addAction( 'init', [ $this->blockHooks, 'register' ] );
			// Cannot be moved to BlockHooks register.
			$this->wpAdapter->addAction( 'woocommerce_blocks_loaded', [ $this->blockHooks, 'orderUpdateCallback' ] );
		}
	}

	public function addLinksToPluginGrid(): void {
		$this->wpAdapter->addFilter(
			'plugin_action_links_' . $this->wpAdapter->pluginBasename( ModuleHelper::getPluginMainFilePath() ),
			[ $this->pluginHooks, 'addPluginActionLinks' ]
		);
		$this->wpAdapter->addFilter( 'plugin_row_meta', [ $this->pluginHooks, 'addPluginRowMeta' ], 10, 2 );
	}

	/**
	Adds Packeta method to available shipping methods.

	@param array<string, string> $methods Previous state.

	@return array<string, string>
	 */
	public function addShippingMethods( array $methods ): array {
		if ( $this->optionsProvider->isWcCarrierConfigEnabled() ) {
			$unsortedMethods = $this->shippingProvider->addMethods( $methods );

			return $this->shippingProvider->sortMethods( $unsortedMethods );
		}

		$methods[ ShippingMethod::PACKETERY_METHOD_ID ] = ShippingMethod::class;

		return $methods;
	}

	/**
	 *  Add links to left admin menu.
	 */
	public function addMenuPages(): void {
		$this->optionsPage->registerMenuPage();
		$this->dashboardPage->register();
		$this->optionsPage->registerSubmenuPage();
		$this->carrierOptionsPage->register();
		$this->labelPrint->register();
		$this->orderCollectionPrint->register();
		$this->logPage->register();
	}

	public function activatePlugin(): void {
		$this->wpAdapter->updateOption( OptionNames::PACKETERY_ACTIVATED, true );
	}

	public function redirectAfterActivation(): void {
		if ( (bool) $this->wpAdapter->getOption( OptionNames::PACKETERY_ACTIVATED ) === true ) {
			$this->wpAdapter->deleteOption( OptionNames::PACKETERY_ACTIVATED );
			$this->wpAdapter->safeRedirect( $this->wpAdapter->adminUrl( 'admin.php?page=' . DashboardPage::SLUG ) );
			exit;
		}
	}
}
