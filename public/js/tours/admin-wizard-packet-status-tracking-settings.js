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
					description: wizardTourConfig.translations.enableChangeOrderStatus.description
				}
			},
		],
		onDestroyStarted: function () {
			if (!driverObj.hasNextStep() || window.confirm("Are you sure?")) {
				driverObj.destroy();
			}
		}
	});
driverObj.drive();
