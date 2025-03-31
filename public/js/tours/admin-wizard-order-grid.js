const driver = window.driver.js.driver;

	const steps = [
		{
			element: '.bulkactions',
			popover: {
				title: wizardTourConfig.translations.bulkActions.title,
				description: wizardTourConfig.translations.bulkActions.description,
			}
		},
		{
			element: '.js-wizard-packetery-order-type',
			popover: {
				title: wizardTourConfig.translations.orderType.title,
				description: wizardTourConfig.translations.orderType.description,
			}
		},
		{
			element: '.js-wizard-packetery-filter-orders-to-submit',
			popover: {
				title: wizardTourConfig.translations.filterToSubmit.title,
				description: wizardTourConfig.translations.filterToSubmit.description,
			}
		},
		{
			element: '.js-wizard-packetery-filter-orders-to-print',
			popover: {
				title: wizardTourConfig.translations.filterToPrint.title,
				description: wizardTourConfig.translations.filterToPrint.description,
			}
		},
		{
			element: '.column-packetery_weight',
			popover: {
				title: wizardTourConfig.translations.weight.title,
				description: wizardTourConfig.translations.weight.description,
			}
		},
		{
			element: '.column-packetery',
			popover: {
				title: wizardTourConfig.translations.packeta.title,
				description: wizardTourConfig.translations.packeta.description,
			}
		},
		{
			element: '.column-packetery_packet_id',
			popover: {
				title: wizardTourConfig.translations.trackingNumber.title,
				description: wizardTourConfig.translations.trackingNumber.description,
			}
		},
		{
			element: '.column-packetery_packet_status',
			popover: {
				title: wizardTourConfig.translations.status.title,
				description: wizardTourConfig.translations.status.description,
			}
		},{
			element: '.column-packetery_packet_stored_until',
			popover: {
				title: wizardTourConfig.translations.storedUntil.title,
				description: wizardTourConfig.translations.storedUntil.description,
			}
		},{
			element: '.column-packetery_destination',
			popover: {
				title: wizardTourConfig.translations.pickupOrCarrier.title,
				description: wizardTourConfig.translations.pickupOrCarrier.description,
			}
		},



	];

	const filteredSteps = steps.filter(step => {
		const element = document.querySelector(step.element);
		return element && window.getComputedStyle(element).display !== 'none';
	});

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
			steps: filteredSteps,
			onDestroyStarted: function () {
				if (!driverObj.hasNextStep() || window.confirm(wizardTourConfig.translations.areYouSure)) {
					driverObj.destroy();
				}
			},
		});
driverObj.drive();

setTimeout(() => {
	driverObj.refresh();
}, 1000);
