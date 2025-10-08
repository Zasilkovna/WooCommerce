<?php

namespace Packetery\Module\Views;

use Packetery\Module\ContextResolver;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionNames;
use Packetery\Module\Options\Page;
use Packetery\Module\Order\DetailCommonLogic;
use Packetery\Module\Shipping\ShippingProvider;
use Packetery\Nette\Http\Request;
use WC_Abstract_Order;

class WizardAssetManager {

	/** @var WpAdapter */
	private $wpAdapter;

	/** @var AssetManager */
	private $assetManager;

	/** @var Request */
	private $request;

	/** @var ContextResolver */
	private $contextResolver;

	/** @var DetailCommonLogic */
	private $detailCommonLogic;

	/** @var WcAdapter  */
	private $wcAdapter;

	public function __construct(
		AssetManager $assetManager,
		Request $request,
		WpAdapter $wpAdapter,
		ContextResolver $contextResolver,
		DetailCommonLogic $detailCommonLogic,
		WcAdapter $wcAdapter
	) {
		$this->assetManager      = $assetManager;
		$this->request           = $request;
		$this->wpAdapter         = $wpAdapter;
		$this->contextResolver   = $contextResolver;
		$this->detailCommonLogic = $detailCommonLogic;
		$this->wcAdapter         = $wcAdapter;
	}

	public function enqueueWizardAssets(): void {
		$page                                 = $this->request->getQuery( 'page' );
		$isWizardEnabled                      = $this->request->getQuery( 'wizard-enabled' ) === 'true';
		$isItFirstRunOrderDetailEditPacket    = (bool) $this->wpAdapter->getOption( OptionNames::PACKETERY_TUTORIAL_ORDER_DETAIL_EDIT_PACKET );
		$isItFirstRunOrderOrderGridEditPacket = (bool) $this->wpAdapter->getOption( OptionNames::PACKETERY_TUTORIAL_ORDER_GRID_EDIT_PACKET );

		if ( $isWizardEnabled || $isItFirstRunOrderDetailEditPacket || $isItFirstRunOrderOrderGridEditPacket ) {
			$this->enqueueBaseAssets();
			if ( $page === Page::SLUG ) {
				$this->enqueueSettingsTours();
			}

			if ( $this->contextResolver->isOrderGridPage() ) {
				$this->enqueueOrderGridTours( $isItFirstRunOrderOrderGridEditPacket );
			}

			if ( $this->detailCommonLogic->isPacketeryOrder() ) {
				$this->enqueueOrderDetailTours( $isItFirstRunOrderDetailEditPacket );
			}
		}
	}

	private function enqueueBaseAssets(): void {
		$this->assetManager->enqueueStyle( 'packetery-driverjs-css', 'public/libs/driverjs-1.3.4/driver.css' );
		$this->assetManager->enqueueScript( 'packetery-driverjs', 'public/libs/driverjs-1.3.4/driver.js.iife.js', true );
		$this->assetManager->enqueueStyle( 'packetery-driverjs-css-custom', 'public/css/driver.js.css' );
	}

	/**
	 * @return array<string, array<string, string|null>|string|null>
	 */
	private function getBasicTranslations(): array {
		return [
			'next'               => $this->wpAdapter->__( 'Next', 'packeta' ),
			'previous'           => $this->wpAdapter->__( 'Previous', 'packeta' ),
			'close'              => $this->wpAdapter->__( 'Close', 'packeta' ),
			'of'                 => $this->wpAdapter->__( 'of', 'packeta' ),
			'areYouSure'         => $this->wpAdapter->__( 'Are you sure?', 'packeta' ),
			'settingsSaveButton' => [
				'title'       => $this->wpAdapter->__( 'Save all changes', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Save with this button after all changes are made.', 'packeta' ),
			],
		];
	}

	private function enqueueSettingsTours(): void {
		$basicTranslations = $this->getBasicTranslations();

		if ( $this->request->getQuery( 'wizard-general-settings-tour-enabled' ) === 'true' ) {
			$this->createGeneralSettingsTour( $basicTranslations );
		}

		if ( $this->request->getQuery( 'wizard-packet-status-tracking-tour-enabled' ) === 'true' ) {
			$this->createPacketStatusTrackingTour( $basicTranslations );
		}

		if ( $this->request->getQuery( 'wizard-auto-submission-tour-enabled' ) === 'true' ) {
			$this->createAutoSubmissionTour( $basicTranslations );
		}

		if ( $this->request->getQuery( 'wizard-advanced-tour-enabled' ) === 'true' ) {
			$this->createAdvancedTour( $basicTranslations );
		}
	}

	private function enqueueOrderGridTours( bool $isItFirstRunOrderOrderGridEditPacket ): void {
		$basicTranslations = $this->getBasicTranslations();
		if ( $this->request->getQuery( 'wizard-order-grid-edit-packet-enabled' ) === 'true' ||
			$isItFirstRunOrderOrderGridEditPacket
		) {
			if ( $this->hasTableGridOurShippingMethods() ) {
				update_option( OptionNames::PACKETERY_TUTORIAL_ORDER_GRID_EDIT_PACKET, 0 );
				$this->createOrderGridEditPacketTour( $basicTranslations );
			}
		}
		if ( $this->request->getQuery( 'wizard-order-grid-enabled' ) === 'true' ) {
			$this->createOrderGridTour( $basicTranslations );
		}
	}

	private function enqueueOrderDetailTours( bool $isItFirstRunOrderDetailEditPacket ): void {
		$basicTranslations = $this->getBasicTranslations();
		if ( $this->request->getQuery( 'wizard-order-detail-edit-packet-enabled' ) === 'true' || $isItFirstRunOrderDetailEditPacket ) {
			update_option( OptionNames::PACKETERY_TUTORIAL_ORDER_DETAIL_EDIT_PACKET, 0 );
			$this->createOrderDetailEditPacketTour( $basicTranslations );
		}
		if ( $this->request->getQuery( 'wizard-order-detail-custom-declaration-enabled' ) === 'true' ) {
			$this->createOrderDetailCustomDeclarationTour( $basicTranslations );
		}
	}

	/**
	 * @param string                                                $scriptName
	 * @param array<string, array<string, string|null>|string|null> $translations
	 */
	private function enqueueTourScript( string $scriptName, array $translations ): void {
		$this->assetManager->enqueueScript( 'packetery-admin-wizard-tour', 'public/js/tours/' . $scriptName, true, [ 'packetery-driverjs' ] );
		$this->wpAdapter->localizeScript( 'packetery-admin-wizard-tour', 'wizardTourConfig', [ 'translations' => $translations ] );
	}

	/**
	 * @param array<string, array<string, string|null>|string|null> $basicTranslations
	 */
	private function createGeneralSettingsTour( array $basicTranslations ): void {
		$translations = [
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
					esc_html__( 'Fill in the %1$ssender label%2$s here - you will find it in %3$sclient section%4$s - user information - field \'Indication\'.', 'packeta' ),
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
				'description' => $this->wpAdapter->__( 'Select the payment method that will be set as cash on delivery. This information will be passed to the carrier to calculate surcharges.', 'packeta' ),
			],
			'packagingWeight'      => [
				'title'       => $this->wpAdapter->__( 'Weight of packaging material', 'packeta' ),
				'description' => $this->wpAdapter->__( 'This parameter is used to determine the weight of the packaging material. This value is automatically added to the total weight of each order that contains products with non-zero weight. It is also taken into account when evaluating weight rules in the cart.', 'packeta' ),
			],
			'defaultWeightEnabled' => [
				'title'       => $this->wpAdapter->__( 'Enable default weight', 'packeta' ),
				'description' => $this->wpAdapter->__( 'If no weight is set for the products in order, this value will be used.', 'packeta' ),
			],
			'dimensionsUnit'       => [
				'title'       => $this->wpAdapter->__( 'Units used for dimensions', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Units used for package dimensions, such as centimeters (cm) or millimeters (mm).', 'packeta' ),
			],
			'dimensionsEnabled'    => [
				'title'       => $this->wpAdapter->__( 'Enable default dimensions', 'packeta' ),
				'description' => $this->wpAdapter->__( 'When enabled, these values will be automatically used for any order that contains products with zero dimensions.', 'packeta' ),
			],
			'pickupPointAddress'   => [
				'title'       => $this->wpAdapter->__( 'Replace shipping address with pickup point address', 'packeta' ),
				'description' => $this->wpAdapter->__( 'If this option is enabled, the customers shipping address will automatically be replaced with the selected pickup point address.', 'packeta' ),
			],
			'checkoutDetection'    => [
				'title'       => $this->wpAdapter->__( 'Force checkout type', 'packeta' ),
				'description' => $this->wpAdapter->__( 'If you have trouble displaying the widget button in the checkout, you can set what type of checkout you are using.', 'packeta' ),
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
				'description' => $this->wpAdapter->__( 'This option determines where the pickup point information will be displayed in the e-mail. Choose the appropriate option based on the structure of e-mails in your e-shop.', 'packeta' ),
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
		];

		$this->enqueueTourScript( 'admin-wizard-general-settings.js', array_merge( $translations, $basicTranslations ) );
	}

	/**
	 * @param array<string, array<string, string|null>|string|null> $basicTranslations
	 */
	private function createPacketStatusTrackingTour( array $basicTranslations ): void {
		$translations = [
			'numberOrders'            => [
				'title'       => $this->wpAdapter->__( 'Number of orders synced during one cron call', 'packeta' ),
				'description' => $this->wpAdapter->__( 'The number of orders that will be checked during a single cron call.', 'packeta' ),
			],
			'trackingDays'            => [
				'title'       => $this->wpAdapter->__( 'Number of days for which the order status is checked', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Number of days after the creation of an order, during which the order status will be checked.', 'packeta' ),
			],
			'orderStatus'             => [
				'title'       => $this->wpAdapter->__( 'Order statuses, for which cron will check the packet status', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Cron will automatically track all orders with these statuses and check if the shipment status has changed.', 'packeta' ),
			],
			'packetStatus'            => [
				'title'       => $this->wpAdapter->__( 'Packet statuses that are being checked', 'packeta' ),
				'description' => $this->wpAdapter->__( 'If an order has a shipment with one of these selected statuses, the shipment status will be tracked.', 'packeta' ),
			],
			'enableChangeOrderStatus' => [
				'title'       => $this->wpAdapter->__( 'Allow order status change', 'packeta' ),
				'description' => $this->wpAdapter->__( 'You can enable automatic change of order status here.', 'packeta' ),
			],
		];

		$this->enqueueTourScript( 'admin-wizard-packet-status-tracking-settings.js', array_merge( $translations, $basicTranslations ) );
	}

	/**
	 * @param array<string, array<string, string|null>|string|null> $basicTranslations
	 */
	private function createAutoSubmissionTour( array $basicTranslations ): void {
		$translations = [
			'autoSubmissionEnabled' => [
				'title'       => $this->wpAdapter->__( 'Allow packet auto-submission', 'packeta' ),
				'description' => $this->wpAdapter->__( 'You can enable automatic submission of the shipment when the order status changes here.', 'packeta' ),
			],
			'autoSubmissionMapping' => [
				'title'       => $this->wpAdapter->__( 'Status mapping', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Choose events for payment methods that will trigger packet submission', 'packeta' ),
			],
		];

		$this->enqueueTourScript( 'admin-wizard-auto-submission-settings.js', array_merge( $translations, $basicTranslations ) );
	}

	/**
	 * @param array<string, array<string, string|null>|string|null> $basicTranslations
	 */
	private function createAdvancedTour( array $basicTranslations ): void {
		$translations = [
			'newCarrierEnabled' => [
				'title'       => $this->wpAdapter->__( 'Advanced carrier settings', 'packeta' ),
				'description' => $this->wpAdapter->__( 'You can enable the advanced carrier settings to get better support of WooCommerce features here.', 'packeta' ),
			],
		];

		$this->enqueueTourScript( 'admin-wizard-advanced-settings.js', array_merge( $translations, $basicTranslations ) );
	}

	private function createOrderGridEditPacketTour( array $basicTranslations ): void {
		$translations = [
			'modalWeight'       => [
				'title'       => $this->wpAdapter->__( 'Weight', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Here you see the shipment weight, calculated from the products in the order. If products don’t have a weight, the default value set in the plugin settings will be used. You can also adjust it manually.', 'packeta' ),
			],
			'modalLength'       => [
				'title'       => $this->wpAdapter->__( 'Length', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Enter the shipment length in mm or cm, depending on plugin settings. The length of the largest product in the order is used. If the product has no dimensions, the default from plugin settings is used. You can also adjust it manually.', 'packeta' ),
			],
			'modalWidth'        => [
				'title'       => $this->wpAdapter->__( 'Width', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Enter the shipment width in mm or cm, depending on plugin settings. The width of the largest product in the order is used. If the product has no dimensions, the default from plugin settings is used. You can also adjust it manually.', 'packeta' ),
			],
			'modalHeight'       => [
				'title'       => $this->wpAdapter->__( 'Height', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Enter the shipment height in mm or cm, depending on plugin settings. The height of the largest product in the order is used. If the product has no dimensions, the default from plugin settings is used. You can also adjust it manually.', 'packeta' ),
			],
			'modalAdultContent' => [
				'title'       => $this->wpAdapter->__( 'Adult content', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Automatically checked for orders with adult products.', 'packeta' ),
			],
			'modalCod'          => [
				'title'       => $this->wpAdapter->__( 'COD', 'packeta' ),
				'description' => $this->wpAdapter->__( 'You can edit the cash on delivery manually, otherwise the total order value is used.', 'packeta' ),
			],
			'modalValue'        => [
				'title'       => $this->wpAdapter->__( 'Value', 'packeta' ),
				'description' => $this->wpAdapter->__( 'You can edit the packet value manually, otherwise the total order price is used.', 'packeta' ),
			],
			'modalDeliverOn'    => [
				'title'       => $this->wpAdapter->__( 'Deliver on', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Deferred delivery date – if set, the packet will be ready for pickup from this date onward.', 'packeta' ),
			],
		];
		$this->enqueueTourScript( 'admin-wizard-create-packet-modal.js', array_merge( $translations, $basicTranslations ) );
	}

	private function createOrderDetailEditPacketTour( array $basicTranslations ): void {
		$translations = [
			'weight'             => [
				'title'       => $this->wpAdapter->__( 'Weight', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Here you see the shipment weight, calculated from the products in the order. If products don’t have a weight, the default value set in the plugin settings will be used. You can also adjust it manually.', 'packeta' ),
			],
			'length'             => [
				'title'       => $this->wpAdapter->__( 'Length', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Enter the shipment length in mm or cm, depending on plugin settings. The length of the largest product in the order is used. If the product has no dimensions, the default from plugin settings is used. You can also adjust it manually.', 'packeta' ),
			],
			'width'              => [
				'title'       => $this->wpAdapter->__( 'Width', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Enter the shipment width in mm or cm, depending on plugin settings. The width of the largest product in the order is used. If the product has no dimensions, the default from plugin settings is used. You can also adjust it manually.', 'packeta' ),
			],
			'height'             => [
				'title'       => $this->wpAdapter->__( 'Height', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Enter the shipment height in mm or cm, depending on plugin settings. The height of the largest product in the order is used. If the product has no dimensions, the default from plugin settings is used. You can also adjust it manually.', 'packeta' ),
			],
			'adultContent'       => [
				'title'       => $this->wpAdapter->__( 'Adult content', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Automatically checked for orders with adult products.', 'packeta' ),
			],
			'cod'                => [
				'title'       => $this->wpAdapter->__( 'COD', 'packeta' ),
				'description' => $this->wpAdapter->__( 'You can edit the cash on delivery manually, otherwise the total order value is used.', 'packeta' ),
			],
			'value'              => [
				'title'       => $this->wpAdapter->__( 'Value', 'packeta' ),
				'description' => $this->wpAdapter->__( 'You can edit the packet value manually, otherwise the total order price is used.', 'packeta' ),
			],
			'deliverOn'          => [
				'title'       => $this->wpAdapter->__( 'Deliver on', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Deferred delivery date – if set, the packet will be ready for pickup from this date onward.', 'packeta' ),
			],
			'pickupPoint'        => [
				'title'       => $this->wpAdapter->__( 'Pickup point', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Here you can change the pickup point where the packet will be delivered.', 'packeta' ),
			],
			'pickupAddress'      => [
				'title'       => $this->wpAdapter->__( 'Pickup address', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Here you can change the delivery address of the packet via the widget.', 'packeta' ),
			],
			'trackingUrl'        => [
				'title'       => $this->wpAdapter->__( 'Tracking url', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Here you can find the URL to track the packet, you can click through to online tracking.', 'packeta' ),
			],
			'claimTrackingUrl'   => [
				'title'       => $this->wpAdapter->__( 'Claim tracking url', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Here you can find the URL to track the claim packet (Claim Assistant), you can click through to online tracking.', 'packeta' ),
			],
			'claimPassword'      => [
				'title'       => $this->wpAdapter->__( 'Claim password', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Password required to submit the packet via Claim Assistant.', 'packeta' ),
			],
			'buttonSubmitPacket' => [
				'title'       => $this->wpAdapter->__( 'Submit', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Use this button to submit the packet. Clicking it will send the packet to the Packeta system', 'packeta' ),
			],
			'buttonCancel'       => [
				'title'       => $this->wpAdapter->__( 'Cancel', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Button to cancel the packet. Clicking it will cancel the packet in the Packeta system, allowing you to submit it again.', 'packeta' ),
			],
			'print'              => [
				'title'       => $this->wpAdapter->__( 'Print', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Clicking this button opens a PDF of the label ready for printing.', 'packeta' ),
			],
			'storedUnitl'        => [
				'title'       => $this->wpAdapter->__( 'Stored until', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Here you can see the date until which the packet can be picked up (shown if shipment tracking is enabled and the packet is ready for pickup).', 'packeta' ),
			],
			'claimUrl'           => [
				'title'       => $this->wpAdapter->__( 'Claim url', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Here you can find the URL to track the packet, you can click through to online tracking.', 'packeta' ),
			],
			'claimLabel'         => [
				'title'       => $this->wpAdapter->__( 'Claim label', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Here you can find the URL to track the claim packet (claim assistant), you can click through to online tracking.', 'packeta' ),
			],
			'cancelClaim'        => [
				'title'       => $this->wpAdapter->__( 'Cancel claim', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Button to cancel a claim assistant packet. Clicking it will cancel the packet in the Packeta system, and you’ll be able to submit it again.', 'packeta' ),
			],
			'packetStatus'       => [
				'title'       => $this->wpAdapter->__( 'Packet status', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Here you can see the current stage of the packet (shown only if this option is enabled in the plugin settings).', 'packeta' ),
			],
			'logsLink'           => [
				'title'       => $this->wpAdapter->__( 'Logs', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Button that, when clicked, shows the logs for this order. This lets you easily check what actions have been taken on the order in the system.', 'packeta' ),
			],
		];
		$this->enqueueTourScript( 'admin-wizard-create-packet-metabox.js', array_merge( $translations, $basicTranslations ) );
	}

	private function createOrderGridTour( array $basicTranslations ): void {
		$translations = [
			'bulkActions'     => [
				'title'       => $this->wpAdapter->__( 'Bulk actions', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Here you can use Packeta bulk actions – submit packets, print labels, or print a packet list.', 'packeta' ),
			],
			'orderType'       => [
				'title'       => $this->wpAdapter->__( 'Packeta shipping method', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Filter to show Packeta orders sent either to pickup points or to a carrier.', 'packeta' ),
			],
			'filterToSubmit'  => [
				'title'       => $this->wpAdapter->__( 'Packeta orders to submit', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Shows Packeta orders that haven’t been submitted yet.', 'packeta' ),
			],
			'filterToPrint'   => [
				'title'       => $this->wpAdapter->__( 'Packeta orders to print', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Shows Packeta orders where the label hasn’t been printed yet', 'packeta' ),
			],
			'weight'          => [
				'title'       => $this->wpAdapter->__( 'Weight', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Total packet weight from the order, you can adjust it', 'packeta' ),
			],
			'packeta'         => [
				'title'       => $this->wpAdapter->__( 'Packeta', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Here you can find Packeta actions – submit packet, add details, or print labels (shown only when available).', 'packeta' ),
			],
			'trackingNumber'  => [
				'title'       => $this->wpAdapter->__( 'Tracking no.', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Here you can find the packet number assigned by Packeta, clickable for online tracking..', 'packeta' ),
			],
			'status'          => [
				'title'       => $this->wpAdapter->__( 'Packeta packet status', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Here you can see the current stage of the packet (shown only if this option is enabled in the plugin settings).', 'packeta' ),
			],
			'storedUntil'     => [
				'title'       => $this->wpAdapter->__( 'Stored until', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Here you can see the date until which the packet can be picked up (shown if shipment tracking is enabled and the packet is ready for pickup).', 'packeta' ),
			],
			'pickupOrCarrier' => [
				'title'       => $this->wpAdapter->__( 'Pickup point or carrier', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Here you can see the name of the pickup point or the carrier.', 'packeta' ),
			],
		];
		$this->enqueueTourScript( 'admin-wizard-order-grid.js', array_merge( $translations, $basicTranslations ) );
	}

	private function createOrderDetailCustomDeclarationTour( array $basicTranslations ): void {
		$translations = [
			'ead'              => [
				'title'       => $this->wpAdapter->__( 'EAD', 'packeta' ),
				'description' => $this->wpAdapter->__( 'European Administrative Document (EAD) for shipments outside the EU.', 'packeta' ),
			],
			'cost'             => [
				'title'       => $this->wpAdapter->__( 'Delivery cost', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Delivery cost included on the invoice', 'packeta' ),
			],
			'number'           => [
				'title'       => $this->wpAdapter->__( 'Invoice number', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Invoice number for the shipment.', 'packeta' ),
			],
			'invoiceIssueDate' => [
				'title'       => $this->wpAdapter->__( 'Invoice issue date', 'packeta' ),
				'description' => $this->wpAdapter->__( 'The date the invoice was issued.', 'packeta' ),
			],
			'invoiceFile'      => [
				'title'       => $this->wpAdapter->__( 'Invoice PDF file', 'packeta' ),
				'description' => $this->wpAdapter->__( 'The date the invoice was issued.', 'packeta' ),
			],
			'mrn'              => [
				'title'       => $this->wpAdapter->__( 'MRN', 'packeta' ),
				'description' => $this->wpAdapter->__( 'MRN – a unique reference code for customs shipments.', 'packeta' ),
			],
			'eadFile'          => [
				'title'       => $this->wpAdapter->__( 'EAD PDF file', 'packeta' ),
				'description' => $this->wpAdapter->__( 'PDF file of the EAD document to attach to the shipment.', 'packeta' ),
			],
			'customsCode'      => [
				'title'       => $this->wpAdapter->__( 'Customs code', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Customs code of the product for the declaration.', 'packeta' ),
			],
			'value'            => [
				'title'       => $this->wpAdapter->__( 'Value', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Value of the product', 'packeta' ),
			],
			'productNameEn'    => [
				'title'       => $this->wpAdapter->__( 'Product name (EN)', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Product name in English.', 'packeta' ),
			],
			'productName'      => [
				'title'       => $this->wpAdapter->__( 'Product name', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Product name in your local language.', 'packeta' ),
			],
			'unitsCount'       => [
				'title'       => $this->wpAdapter->__( 'Units count', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Number of product units in the shipment.', 'packeta' ),
			],
			'countryOfOrigin'  => [
				'title'       => $this->wpAdapter->__( 'Country of origin code', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Country of origin code of the product.', 'packeta' ),
			],
			'weight'           => [
				'title'       => $this->wpAdapter->__( 'Weight (kg)', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Weight of the product in kilograms.', 'packeta' ),
			],
			'isFoodOrBook'     => [
				'title'       => $this->wpAdapter->__( 'Food or book?', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Check if the product is food or a book.', 'packeta' ),
			],
			'isVOC'            => [
				'title'       => $this->wpAdapter->__( 'Is VOC?', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Check if the product contains VOC (volatile organic compounds).', 'packeta' ),
			],
			'addDeclaration'   => [
				'title'       => $this->wpAdapter->__( 'Add item', 'packeta' ),
				'description' => $this->wpAdapter->__( 'Add another item to the customs declaration.', 'packeta' ),
			],
		];
		$this->enqueueTourScript( 'admin-wizard-custom-declaration-metabox.js', array_merge( $translations, $basicTranslations ) );
	}

	private function hasTableGridOurShippingMethods(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only reading paged parameter for listing
		$page  = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		$limit = get_option( 'edit_shop_order_per_page', 20 );

		$orders = $this->wcAdapter->getOrdersWithoutPagination(
			[
				'limit'   => $limit,
				'page'    => $page,
				'status'  => array_keys( wc_get_order_statuses() ),
				'orderby' => 'date',
				'order'   => 'DESC',
			]
		);
		if ( is_array( $orders ) ) {
			foreach ( $orders as $order ) {
				if ( $order instanceof WC_Abstract_Order && ShippingProvider::wcOrderHasOurMethod( $order ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
