(function ($) {
	$(function () {
		const driver = window.driver.js.driver;

		const driverObj = driver(
			{
				showProgress: true,
				progressText: '{{current}} ' + wizardTourConfig.translations.of + ' {{total}}',
				showButtons: [
					'next',
					'previous',
					'close'
				],
				nextBtnText: wizardTourConfig.translations.next,
				prevBtnText: wizardTourConfig.translations.previous,
				doneBtnText: wizardTourConfig.translations.close,
				popoverClass: 'driverjs-theme',
				steps: [
					{
						element: '.packetery-js-wizard-general-password',
						popover: {
							title: wizardTourConfig.translations.apiPassword.title,
							description: wizardTourConfig.translations.apiPassword.description
						}
					},
					{
						element: '.packetery-js-wizard-general-sender',
						popover: {
							title: wizardTourConfig.translations.apiSender.title,
							description: wizardTourConfig.translations.apiSender.description
						}
					},
					{
						element: '.packetery-js-wizard-general-packeta-label-format',
						popover: {
							title: wizardTourConfig.translations.packetaLabelFormat.title,
							description: wizardTourConfig.translations.packetaLabelFormat.description
						}
					},
					{
						element: '.packetery-js-wizard-general-carrier-label-format',
						popover: {
							title: wizardTourConfig.translations.carrierLabelFormat.title,
							description: wizardTourConfig.translations.carrierLabelFormat.description
						}
					},
					{
						element: '.custom-select2-wrapper.packetery-js-wizard-general-cod',
						popover: {
							title: wizardTourConfig.translations.cod.title,
							description: wizardTourConfig.translations.cod.description
						},
						onDeselected: () => {
							$( '[data-packetery-select2]' ).select2( 'close' );
						}
					},
					{
						element: '.packetery-js-wizard-general-packaging-weight',
						popover: {
							title: wizardTourConfig.translations.packagingWeight.title,
							description: wizardTourConfig.translations.packagingWeight.description
						}
					},
					{
						element: '.packetery-js-wizard-general-default-weight-enabled',
						popover: {
							title: wizardTourConfig.translations.defaultWeightEnabled.title,
							description: wizardTourConfig.translations.defaultWeightEnabled.description
						}
					},
					{
						element: '.packetery-js-wizard-general-dimensions-unit',
						popover: {
							title: wizardTourConfig.translations.dimensionsUnit.title,
							description: wizardTourConfig.translations.dimensionsUnit.description
						}
					},
					{
						element: '.packetery-js-wizard-general-default-dimensions-enabled',
						popover: {
							title: wizardTourConfig.translations.dimensionsEnabled.title,
							description: wizardTourConfig.translations.dimensionsEnabled.description
						}
					},
					{
						element: '.packetery-js-wizard-general-pickup-point-address',
						popover: {
							title: wizardTourConfig.translations.pickupPointAddress.title,
							description: wizardTourConfig.translations.pickupPointAddress.description
						}
					},
					{
						element: '.packetery-js-wizard-general-checkout-detection',
						popover: {
							title: wizardTourConfig.translations.checkoutDetection.title,
							description: wizardTourConfig.translations.checkoutDetection.description
						}
					},
					{
						element: '.packetery-js-wizard-general-widget-button-location',
						popover: {
							title: wizardTourConfig.translations.widgetButtonLocation.title,
							description: wizardTourConfig.translations.widgetButtonLocation.description
						}
					},
					{
						element: '.packetery-js-wizard-general-hide-logo',
						popover: {
							title: wizardTourConfig.translations.hideLogo.title,
							description: wizardTourConfig.translations.hideLogo.description
						}
					},
					{
						element: '.packetery-js-wizard-general-email-hook',
						popover: {
							title: wizardTourConfig.translations.emailHook.title,
							description: wizardTourConfig.translations.emailHook.description
						}
					},
					{
						element: '.packetery-js-wizard-force-packet-cancel',
						popover: {
							title: wizardTourConfig.translations.forcePacketCancel.title,
							description: wizardTourConfig.translations.forcePacketCancel.description
						}
					},
					{
						element: '.packetery-js-wizard-widget-auto-open',
						popover: {
							title: wizardTourConfig.translations.widgetAutoOpen.title,
							description: wizardTourConfig.translations.widgetAutoOpen.description
						}
					},
					{
						element: '.packetery-js-wizard-free-shipping-shown',
						popover: {
							title: wizardTourConfig.translations.freeShippingShown.title,
							description: wizardTourConfig.translations.freeShippingShown.description
						}
					},
					{
						element: '.packetery-js-wizard-prices-include-tax',
						popover: {
							title: wizardTourConfig.translations.pricesIncludeTax.title,
							description: wizardTourConfig.translations.pricesIncludeTax.description
						}
					},
					{
						element: '.packetery-js-wizard-settings-save-button',
						popover: {
							title: wizardTourConfig.translations.settingsSaveButton.title,
							description: wizardTourConfig.translations.settingsSaveButton.description
						}
					}
				],
				onDestroyStarted: function () {
					if (!driverObj.hasNextStep() || window.confirm(wizardTourConfig.translations.areYouSure)) {
						driverObj.destroy();
					}
				}
			});
		driverObj.drive();
		setTimeout(() => {
			driverObj.refresh();
		}, 1000);
	});
})(jQuery);
