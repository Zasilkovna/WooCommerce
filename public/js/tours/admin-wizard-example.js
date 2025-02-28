const driver = window.driver.js.driver;

const driverObj = driver(
	{
		showProgress: true,
		progressText: '{{current}} ' + adminWizardTourSettings.translations.of + ' {{total}}',
		showButtons: [
			'next',
			'previous',
			'close'
		],
		nextBtnText: adminWizardTourSettings.translations.next,
		prevBtnText: adminWizardTourSettings.translations.previous,
		doneBtnText: adminWizardTourSettings.translations.close,
		steps: [
			{ element: '#frm-packetery-api_password', popover: { title: adminWizardTourSettings.translations.apiPassword.title, description: adminWizardTourSettings.translations.apiPassword.description } },
			{ element: '#frm-packetery-sender', popover: { title: adminWizardTourSettings.translations.apiSender.title, description: adminWizardTourSettings.translations.apiSender.description } },
			{ element: '.sidebar', popover: { title: 'Title', description: 'Description' } },
			{ element: '.footer', popover: { title: 'Title', description: 'Description' } },
		],
		onDestroyStarted: function () {
			if (!driverObj.hasNextStep() || window.confirm("Are you sure?")) {
				driverObj.destroy();
			}
		}
	});
driverObj.drive();
