window.startWizardTour = function () {
			console.log('startWizardTourInside');
			const driver = window.driver.js.driver;

			const steps = [
				{
					element: '.packetery-js-wizard-modal-weight',
					popover: {
						title: wizardTourConfig.translations.apiPassword.title,
						description: wizardTourConfig.translations.apiPassword.description
					}
				},
				{
					element: '.packetery-js-wizard-modal-length',
					popover: {
						title: wizardTourConfig.translations.apiSender.title,
						description: wizardTourConfig.translations.apiSender.description
					}
				},
				{
					element: '.packetery-js-wizard-modal-width',
					popover: {
						title: wizardTourConfig.translations.apiSender.title,
						description: wizardTourConfig.translations.apiSender.description
					}
				},
				{
					element: '.packetery-js-wizard-modal-height',
					popover: {
						title: wizardTourConfig.translations.apiSender.title,
						description: wizardTourConfig.translations.apiSender.description
					}
				},
				{
					element: '.packetery-js-wizard-modal-adult-content',
					popover: {
						title: wizardTourConfig.translations.apiSender.title,
						description: wizardTourConfig.translations.apiSender.description
					}
				},
				{
					element: '.packetery-js-wizard-modal-cod',
					popover: {
						title: wizardTourConfig.translations.apiSender.title,
						description: wizardTourConfig.translations.apiSender.description
					}
				},
				{
					element: '.packetery-js-wizard-modal-value',
					popover: {
						title: wizardTourConfig.translations.apiSender.title,
						description: wizardTourConfig.translations.apiSender.description
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
};
