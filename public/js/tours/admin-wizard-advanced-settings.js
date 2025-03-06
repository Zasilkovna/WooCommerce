const driver = window.driver.js.driver;

const driverObj = driver(
	{
		showProgress: true,
		progressText: '{{current}} ' + wizardTourConfig.translations.of + ' {{total}}',
		showButtons: [
			'close'
		],
		doneBtnText: wizardTourConfig.translations.close,
		steps: [
			{
				element: '.packetery-js-wizard-new-carrier-enabled',
				popover: {
					title: wizardTourConfig.translations.newCarrierEnabled.title,
					description: wizardTourConfig.translations.newCarrierEnabled.description
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
