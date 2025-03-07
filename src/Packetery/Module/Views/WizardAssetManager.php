<?php

namespace Packetery\Module\Views;

use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\Page;
use Packetery\Nette\Http\Request;

class WizardAssetManager {
	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var AssetManager
	 */
	private $assetManager;

	/**
	 * @var Request
	 */
	private $request;

	public function __construct(
		AssetManager $assetManager,
		Request $request,
		WpAdapter $wpAdapter
	) {
		$this->assetManager = $assetManager;
		$this->request      = $request;
		$this->wpAdapter    = $wpAdapter;
	}

	public function enqueueWizardAssets(): void {
		$page                               = $this->request->getQuery( 'page' );
		$isWizardEnabled                    = $this->request->getQuery( 'wizard-enabled' ) === 'true';
		$isWizardGeneralSettingsTourEnabled = $this->request->getQuery( 'wizard-general-settings-tour-enabled' ) === 'true';
		$wizardTourConfig                   = [];

		if ( $isWizardEnabled ) {
			$basicTranslations = [
				'next'     => $this->wpAdapter->__( 'Next', 'packeta' ),
				'previous' => $this->wpAdapter->__( 'Previous', 'packeta' ),
				'close'    => $this->wpAdapter->__( 'Close', 'packeta' ),
				'of'       => $this->wpAdapter->__( 'of', 'packeta' ),
			];
		}

		if ( $page === Page::SLUG && $isWizardEnabled ) {
			$this->assetManager->enqueueStyle( 'packetery-driverjs-css', 'public/libs/driverjs-1.3.4/driver.css' );
			$this->assetManager->enqueueScript( 'packetery-driverjs', 'public/libs/driverjs-1.3.4/driver.js.iife.js', true );

			if ( $isWizardGeneralSettingsTourEnabled ) {
				$generalSettingsTranslation = [
					'translations' => [
						'apiPassword'          => [
							'title'       => $this->wpAdapter->__( 'API password', 'packeta' ),
							'description' => sprintf(
								$this->wpAdapter->__( 'API password can be found at %s', 'packeta' ),
								'<a href="https://client.packeta.com/support" target="_blank">https://client.packeta.com/support<a/>'
							),
						],
						'apiSender'            => [
							'title'       => $this->wpAdapter->__( 'Sender', 'packeta' ),
							'description' => sprintf(
							/* translators: 1: emphasis start 2: emphasis end 3: client section link start 4: client section link end */
								esc_html__( 'Fill here %1$ssender label%2$s - you will find it in %3$sclient section%4$s - user information - field \'Indication\'.', 'packeta' ),
								'<strong>',
								'</strong>',
								'<a href="https://client.packeta.com/senders" target="_blank">',
								'</a>'
							),
						],
						'packetaLabelFormat'   => [
							'title'       => $this->wpAdapter->__( 'Packeta Label Format', 'packeta' ),
							'description' => $this->wpAdapter->__( 'Select the label print format for Packeta according to your printer.', 'packeta' ),
						],
						'carrierLabelFormat'   => [
							'title'       => $this->wpAdapter->__( 'Carrier Label Format', 'packeta' ),
							'description' => $this->wpAdapter->__( 'Select the label print format for the carrier according to your printer.', 'packeta' ),
						],
						'cod'                  => [
							'title'       => $this->wpAdapter->__( 'Payment methods that represent cash on delivery', 'packeta' ),
							'description' => $this->wpAdapter->__( 'Select the payment method that will be marked as cash on delivery. This method will be used to correctly pass information to the carrier and calculate surcharges.', 'packeta' ),
						],
						'packagingWeight'      => [
							'title'       => $this->wpAdapter->__( 'Weight of packaging material', 'packeta' ),
							'description' => $this->wpAdapter->__( 'This parameter is used to determine the weight of the packaging material. This value is automatically added to the total weight of each order that contains products with non-zero weight. It is also taken into account when evaluating weight rules in the cart.', 'packeta' ),
						],
						'defaultWeightEnabled' => [
							'title'       => $this->wpAdapter->__( 'Enable default weight', 'packeta' ),
							'description' => $this->wpAdapter->__( 'If no weight is set for a product, the default value specified in the settings will be used.', 'packeta' ),
						],
						'dimensionsUnit'       => [
							'title'       => $this->wpAdapter->__( 'Units used for dimensions', 'packeta' ),
							'description' => $this->wpAdapter->__( 'Units used for package dimensions, such as centimeters (cm) or millimeters (mm).', 'packeta' ),
						],
						'dimensionsEnabled'    => [
							'title'       => $this->wpAdapter->__( 'Enable default dimensions', 'packeta' ),
							'description' => $this->wpAdapter->__( 'When enabled, it will automatically be added to the total dimensions of any order that contains products with zero dimensions.', 'packeta' ),
						],
						'pickupPointAddress'   => [
							'title'       => $this->wpAdapter->__( 'Replace shipping address with pickup point address', 'packeta' ),
							'description' => $this->wpAdapter->__( 'If this option is enabled, the customers shipping address will automatically be replaced with the selected pickup point address.', 'packeta' ),
						],
						'checkoutDetection'    => [
							'title'       => $this->wpAdapter->__( 'Force checkout type', 'packeta' ),
							'description' => $this->wpAdapter->__( 'If you have trouble displaying the widget button in the checkout, you can force what type of checkout you are using.', 'packeta' ),
						],
						'widgetButtonLocation' => [
							'title'       => $this->wpAdapter->__( 'Widget button location in checkout', 'packeta' ),
							'description' => $this->wpAdapter->__( 'Determines where the pickup point selection button will appear at checkout.', 'packeta' ),
						],
						'hideLogo'             => [
							'title'       => $this->wpAdapter->__( 'Hide Packeta checkout logo', 'packeta' ),
							'description' => $this->wpAdapter->__( 'Hides the Packeta logo at checkout.', 'packeta' ),
						],
						'emailHook'            => [
							'title'       => $this->wpAdapter->__( 'Hook used to view information in email', 'packeta' ),
							'description' => $this->wpAdapter->__( 'This option determines where the pickup point information will be displayed in the email. Choose the appropriate option based on the structure of emails in your e-shop.', 'packeta' ),
						],
						'forcePacketCancel'    => [
							'title'       => $this->wpAdapter->__( 'Force order cancellation', 'packeta' ),
							'description' => $this->wpAdapter->__( 'Cancel the packet for an order even if the cancellation in the Packeta system will not be successful.', 'packeta' ),
						],
						'widgetAutoOpen'       => [
							'title'       => $this->wpAdapter->__( 'Automatically open widget when shipping was selected', 'packeta' ),
							'description' => $this->wpAdapter->__( 'If this option is active, the widget for selecting pickup points will open automatically after selecting the shipping method at the checkout.', 'packeta' ),
						],
						'freeShippingShown'    => [
							'title'       => $this->wpAdapter->__( 'Display the FREE shipping text in checkout', 'packeta' ),
							'description' => $this->wpAdapter->__( 'If enabled, "FREE" will be displayed after the name of the shipping method, if free shipping is applied.', 'packeta' ),
						],
						'pricesIncludeTax'     => [
							'title'       => $this->wpAdapter->__( 'Prices include tax', 'packeta' ),
							'description' => $this->wpAdapter->__( 'If enabled, VAT will not be added to shipping prices and surcharges.', 'packeta' ),
						],
						'saveGeneral'          => [
							'title'       => $this->wpAdapter->__( 'Save all changes', 'packeta' ),
							'description' => $this->wpAdapter->__( 'After all changes are made, it is saved using this button.', 'packeta' ),
						],
					],
				];

				$wizardTourConfig['translations'] = array_merge( $generalSettingsTranslation['translations'], $basicTranslations );

				$this->assetManager->enqueueScript(
					'packetery-admin-wizard-tour',
					'public/js/tours/admin-wizard-general-settings.js',
					true,
					[
						'packetery-driverjs',
					]
				);

				$this->wpAdapter->localizeScript( 'packetery-admin-wizard-tour', 'wizardTourConfig', $wizardTourConfig );
			}
		}
	}
}
