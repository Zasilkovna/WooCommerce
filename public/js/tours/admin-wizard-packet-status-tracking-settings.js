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
				element: '.packetery-js-wizard-tracking-number-orders',
				popover: {
					title: wizardTourConfig.translations.numberOrders.title,
					description: wizardTourConfig.translations.numberOrders.description
				}
			},
			{
				element: '.packetery-js-wizard-tracking-days',
				popover: {
					title: wizardTourConfig.translations.trackingDays.title,
					description: wizardTourConfig.translations.trackingDays.description
				}
			},
			{
				element: '.packetery-js-wizard-tracking-order-status',
				popover: {
					title: wizardTourConfig.translations.orderStatus.title,
					description: wizardTourConfig.translations.orderStatus.description
				}
			},
			{
				element: '.packetery-js-wizard-tracking-packet-status',
				popover: {
					title: wizardTourConfig.translations.packetStatus.title,
					description: wizardTourConfig.translations.packetStatus.description
				}
			},
			{
				element: '.packetery-js-wizard-tracking-enable-change-order-status',
				popover: {
					title: wizardTourConfig.translations.enableChangeOrderStatus.title,
					description: wizardTourConfig.translations.enableChangeOrderStatus.description,
					side: 'left',
					align: 'end'
				}
			},
			{
				element: '.packetery-js-wizard-settings-save-button',
				popover: {
					title: wizardTourConfig.translations.settingsSaveButton.title,
					description: wizardTourConfig.translations.settingsSaveButton.description,
					side: 'right',
					align: 'end'
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
