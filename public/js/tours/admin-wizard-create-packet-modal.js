window.startWizardTour = function () {
	const driver = window.driver.js.driver;

	const steps = [
		{
			element: '.packetery-js-wizard-modal-weight',
			popover: {
				title: wizardTourConfig.translations.modalWeight.title,
				description: wizardTourConfig.translations.modalWeight.description,
				side: 'right'
			}
		},
		{
			element: '.packetery-js-wizard-modal-length',
			popover: {
				title: wizardTourConfig.translations.modalLength.title,
				description: wizardTourConfig.translations.modalLength.description,
				side: 'right'
			}
		},
		{
			element: '.packetery-js-wizard-modal-width',
			popover: {
				title: wizardTourConfig.translations.modalWidth.title,
				description: wizardTourConfig.translations.modalWidth.description,
				side: 'right'
			}
		},
		{
			element: '.packetery-js-wizard-modal-height',
			popover: {
				title: wizardTourConfig.translations.modalHeight.title,
				description: wizardTourConfig.translations.modalHeight.description,
				side: 'right'
			}
		},
		{
			element: '.packetery-js-wizard-modal-adult-content',
			popover: {
				title: wizardTourConfig.translations.modalAdultContent.title,
				description: wizardTourConfig.translations.modalAdultContent.description,
				side: 'right'
			}
		},
		{
			element: '.packetery-js-wizard-modal-cod',
			popover: {
				title: wizardTourConfig.translations.modalCod.title,
				description: wizardTourConfig.translations.modalCod.description,
				side: 'right'
			}
		},
		{
			element: '.packetery-js-wizard-modal-value',
			popover: {
				title: wizardTourConfig.translations.modalValue.title,
				description: wizardTourConfig.translations.modalValue.description,
				side: 'right'
			}
		},
		{
			element: '.packetery-js-wizard-modal-deliver-on',
			popover: {
				title: wizardTourConfig.translations.modalDeliverOn.title,
				description: wizardTourConfig.translations.modalDeliverOn.description,
				side: 'right'
			}
		},
	];

	const filteredSteps = steps.filter(step => document.querySelector(step.element));

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
		}
	});
	driverObj.drive();
	setTimeout(() => {
		driverObj.refresh();
	}, 1000);
};
