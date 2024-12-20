<?php

declare( strict_types=1 );

namespace Packetery\Module\Views;

use Packetery\Core\CoreHelper;
use Packetery\Module\Carrier;
use Packetery\Module\Checkout\CheckoutService;
use Packetery\Module\Checkout\CheckoutSettings;
use Packetery\Module\ContextResolver;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Log;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Options;
use Packetery\Module\Options\FlagManager\FeatureFlagNotice;
use Packetery\Module\Options\FlagManager\FeatureFlagProvider;
use Packetery\Module\Order;
use Packetery\Module\Order\Metabox;
use Packetery\Module\Plugin;
use Packetery\Nette\Http\Request;

class AssetManager {

	/**
	 * @var ContextResolver
	 */
	private $contextResolver;

	/**
	 * @var FeatureFlagProvider
	 */
	private $featureFlagProvider;

	/**
	 * @var FeatureFlagNotice
	 */
	private $featureFlagNotice;

	/**
	 * @var Metabox
	 */
	private $orderMetabox;

	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var CheckoutSettings
	 */
	private $checkoutSettings;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	/**
	 * @var CheckoutService
	 */
	private $checkoutService;

	public function __construct(
		ContextResolver $contextResolver,
		FeatureFlagProvider $featureFlagProvider,
		FeatureFlagNotice $featureFlagNotice,
		Metabox $orderMetabox,
		Request $request,
		CheckoutSettings $checkoutSettings,
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter,
		CheckoutService $checkoutService
	) {

		$this->contextResolver     = $contextResolver;
		$this->featureFlagProvider = $featureFlagProvider;
		$this->featureFlagNotice   = $featureFlagNotice;
		$this->orderMetabox        = $orderMetabox;
		$this->request             = $request;
		$this->checkoutSettings    = $checkoutSettings;
		$this->wpAdapter           = $wpAdapter;
		$this->wcAdapter           = $wcAdapter;
		$this->checkoutService     = $checkoutService;
	}

	/**
	 * Enqueues admin JS file.
	 *
	 * @param string $name Name of script.
	 * @param string $file Relative file path.
	 * @param bool   $inFooter Tells where to include script.
	 * @param array  $deps Script dependencies.
	 */
	private function enqueueScript( string $name, string $file, bool $inFooter, array $deps = [] ): void {
		$this->wpAdapter->enqueueScript(
			$name,
			$this->wpAdapter->pluginDirUrl( ModuleHelper::getPluginMainFilePath() ) . $file,
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
		$this->wpAdapter->enqueueStyle(
			$name,
			$this->wpAdapter->pluginDirUrl( ModuleHelper::getPluginMainFilePath() ) . $file,
			[],
			md5( (string) filemtime( PACKETERY_PLUGIN_DIR . '/' . $file ) )
		);
	}

	/**
	 * Enqueues javascript files and stylesheets for checkout.
	 */
	public function enqueueFrontAssets(): void {
		if ( $this->wcAdapter->isCheckout() ) {
			if ( $this->wpAdapter->doingAjax() === false ) {
				$this->enqueueStyle( 'packetery-front-styles', 'public/css/front.css' );
				$this->enqueueStyle( 'packetery-custom-front-styles', 'public/css/custom-front.css' );
			}
			if ( $this->checkoutService->areBlocksUsedInCheckout() ) {
				$this->wpAdapter->enqueueScript(
					'packetery-widget-library',
					'https://widget.packeta.com/v6/www/js/library.js',
					[],
					Plugin::VERSION,
					false
				);
			} elseif ( $this->wpAdapter->doingAjax() === false ) {
				$this->enqueueScript( 'packetery-checkout', 'public/js/checkout.js', true, [ 'jquery' ] );
				$this->wpAdapter->localizeScript( 'packetery-checkout', 'packeteryCheckoutSettings', $this->checkoutSettings->createSettings() );
			}
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
			'deliverOnMinDate' => $this->wpAdapter->date( CoreHelper::DATEPICKER_FORMAT, strtotime( 'tomorrow' ) ),
			'dateFormat'       => CoreHelper::DATEPICKER_FORMAT_JS,
		];

		if ( $isOrderGridPage || $isOrderDetailPage || in_array(
			$page,
			[
				Carrier\OptionsPage::SLUG,
				Options\Page::SLUG,
			],
			true
		) ) {
			$this->enqueueScript( 'live-form-validation-options', 'public/js/live-form-validation-options.js', false );
			$this->enqueueScript( 'live-form-validation', 'public/libs/live-form-validation/live-form-validation.js', false, [ 'live-form-validation-options' ] );
			$this->enqueueScript( 'live-form-validation-extension', 'public/js/live-form-validation-extension.js', false, [ 'live-form-validation' ] );
		}

		if ( in_array( $page, [ Carrier\OptionsPage::SLUG, Options\Page::SLUG ], true ) ) {
			$this->enqueueStyle( 'packetery-select2-css', 'public/libs/select2-4.0.13/dist.min.css' );
			$this->enqueueScript( 'packetery-select2', 'public/libs/select2-4.0.13/dist.min.js', true, [ 'jquery' ] );
		}

		if ( $page === Carrier\OptionsPage::SLUG ) {
			$this->enqueueScript(
				'packetery-multiplier',
				'public/js/multiplier.js',
				true,
				[
					'jquery',
					'live-form-validation-extension',
				]
			);
			$this->enqueueScript(
				'packetery-admin-country-carrier',
				'public/js/admin-country-carrier.js',
				true,
				[
					'jquery',
					'packetery-multiplier',
					'packetery-select2',
				]
			);
		}

		if ( $page === Options\Page::SLUG ) {
			$this->enqueueScript(
				'packetery-admin-options',
				'public/js/admin-options.js',
				true,
				[
					'jquery',
					'packetery-select2',
				]
			);
		}

		$isProductPage = $this->contextResolver->isProductPage();
		$isPageDetail  = $this->contextResolver->isPageDetail();

		$screen      = $this->wpAdapter->getCurrentScree();
		$isDashboard = ( $screen !== null && $screen->id === 'dashboard' );

		if (
			$isOrderGridPage || $isOrderDetailPage || $isProductPage || $isProductCategoryPage || $isDashboard || $isPageDetail ||
			in_array(
				$page,
				[
					Options\Page::SLUG,
					Carrier\OptionsPage::SLUG,
					Log\Page::SLUG,
					Order\LabelPrint::MENU_SLUG,
				],
				true
			)
		) {
			$this->enqueueStyle( 'packetery-admin-styles', 'public/css/admin.css' );
			// It is placed here so that typenow in contextResolver works and there is no need to repeat the conditions.
			if ( $this->featureFlagProvider->shouldShowSplitActivationNotice() ) {
				$this->wpAdapter->addAction( 'admin_notices', [ $this->featureFlagNotice, 'renderSplitActivationNotice' ] );
			}
		}

		if ( $isOrderGridPage ) {
			$orderGridPageSettings = [
				'translations' => [
					'hasToFillCustomsDeclaration' => $this->wpAdapter->__( 'Customs declaration has to be filled in order detail.', 'packeta' ),
					'packetSubmissionNotPossible' => $this->wpAdapter->__( 'It is not possible to submit the shipment because all the information required for this shipment is not filled.', 'packeta' ),
				],
			];
			$this->enqueueScript(
				'packetery-admin-grid-order-edit-js',
				'public/js/admin-grid-order-edit.js',
				true,
				[
					'jquery',
					'wp-util',
					'backbone',
				]
			);

			$this->wpAdapter->localizeScript( 'packetery-admin-grid-order-edit-js', 'datePickerSettings', $datePickerSettings );
			$this->wpAdapter->localizeScript( 'packetery-admin-grid-order-edit-js', 'settings', $orderGridPageSettings );

			$this->enqueueScript(
				'packetery-admin-stored-until-modal-js',
				'public/js/admin-stored-until-modal.js',
				true,
				[
					'jquery',
					'wp-util',
					'backbone',
				]
			);
			$this->wpAdapter->localizeScript( 'packetery-admin-stored-until-modal-js', 'datePickerSettings', $datePickerSettings );
			$this->wpAdapter->localizeScript( 'packetery-admin-stored-until-modal-js', 'settings', $orderGridPageSettings );
		}

		$pickupPointPickerSettings = null;
		$addressPickerSettings     = null;

		if ( $isOrderDetailPage ) {
			$this->enqueueScript(
				'packetery-multiplier',
				'public/js/multiplier.js',
				true,
				[
					'jquery',
					'live-form-validation-extension',
				]
			);
			$this->enqueueScript(
				'admin-order-detail',
				'public/js/admin-order-detail.js',
				true,
				[
					'jquery',
					'packetery-multiplier',
					'live-form-validation-extension',
				]
			);

			$this->wpAdapter->localizeScript( 'admin-order-detail', 'datePickerSettings', $datePickerSettings );
			$pickupPointPickerSettings = $this->orderMetabox->getPickupPointWidgetSettings();
			$addressPickerSettings     = $this->orderMetabox->getAddressWidgetSettings();

			$this->enqueueScript(
				'packetery-admin-stored-until-modal-js',
				'public/js/admin-stored-until-modal.js',
				true,
				[
					'jquery',
					'wp-util',
					'backbone',
				]
			);

		}

		if ( $pickupPointPickerSettings !== null || $addressPickerSettings !== null ) {
			$this->wpAdapter->enqueueScript( 'packetery-widget-library', 'https://widget.packeta.com/v6/www/js/library.js', [], Plugin::VERSION, true );
		}

		if ( $pickupPointPickerSettings !== null ) {
			$this->enqueueScript(
				'packetery-admin-pickup-point-picker',
				'public/js/admin-pickup-point-picker.js',
				true,
				[
					'jquery',
					'packetery-widget-library',
				]
			);

			$this->wpAdapter->localizeScript(
				'packetery-admin-pickup-point-picker',
				'packeteryPickupPointPickerSettings',
				$pickupPointPickerSettings
			);
		}

		if ( $addressPickerSettings !== null ) {
			$this->enqueueScript(
				'packetery-admin-address-picker',
				'public/js/admin-address-picker.js',
				true,
				[
					'jquery',
					'packetery-widget-library',
				]
			);
			$this->wpAdapter->localizeScript(
				'packetery-admin-address-picker',
				'packeteryAddressPickerSettings',
				$addressPickerSettings
			);
		}

		if ( $this->contextResolver->isConfirmModalPage() ) {
			$this->enqueueScript( 'packetery-confirm', 'public/js/confirm.js', true, [ 'jquery', 'backbone' ] );
		}
	}
}
