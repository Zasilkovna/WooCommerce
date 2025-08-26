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
				element: '.packetery-js-wizard-new-carrier-enabled',
				popover: {
					title: wizardTourConfig.translations.newCarrierEnabled.title,
					description: wizardTourConfig.translations.newCarrierEnabled.description
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
