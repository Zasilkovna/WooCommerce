const driver = window.driver.js.driver;

const steps = [
	{
		element: '.packetery-js-wizard-metabox-packeteryWeight',
		popover: {
			title: wizardTourConfig.translations.weight.title,
			description: wizardTourConfig.translations.weight.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-packeteryLength',
		popover: {
			title: wizardTourConfig.translations.length.title,
			description: wizardTourConfig.translations.length.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-packeteryWidth',
		popover: {
			title: wizardTourConfig.translations.width.title,
			description: wizardTourConfig.translations.width.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-packeteryHeight',
		popover: {
			title: wizardTourConfig.translations.height.title,
			description: wizardTourConfig.translations.height.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-packeteryAdultContent',
		popover: {
			title: wizardTourConfig.translations.adultContent.title,
			description: wizardTourConfig.translations.adultContent.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-packeteryCOD',
		popover: {
			title: wizardTourConfig.translations.cod.title,
			description: wizardTourConfig.translations.cod.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-packeteryValue',
		popover: {
			title: wizardTourConfig.translations.value.title,
			description: wizardTourConfig.translations.value.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-packeteryDeliverOn',
		popover: {
			title: wizardTourConfig.translations.deliverOn.title,
			description: wizardTourConfig.translations.deliverOn.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-pickup-point',
		popover: {
			title: wizardTourConfig.translations.pickupPoint.title,
			description: wizardTourConfig.translations.pickupPoint.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-pickup-address',
		popover: {
			title: wizardTourConfig.translations.pickupAddress.title,
			description: wizardTourConfig.translations.pickupAddress.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-tracking-url',
		popover: {
			title: wizardTourConfig.translations.trackingUrl.title,
			description: wizardTourConfig.translations.trackingUrl.description
		}
	},{
		element: '.packetery-js-wizard-metabox-packet-status',
		popover: {
			title: wizardTourConfig.translations.packetStatus.title,
			description: wizardTourConfig.translations.packetStatus.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-claim-tracking-url',
		popover: {
			title: wizardTourConfig.translations.claimTrackingUrl.title,
			description: wizardTourConfig.translations.claimTrackingUrl.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-claim-password',
		popover: {
			title: wizardTourConfig.translations.claimPassword.title,
			description: wizardTourConfig.translations.claimPassword.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-logs-link',
		popover: {
			title: wizardTourConfig.translations.logsLink.title,
			description: wizardTourConfig.translations.logsLink.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-button-submit-packet',
		popover: {
			title: wizardTourConfig.translations.buttonSubmitPacket.title,
			description: wizardTourConfig.translations.buttonSubmitPacket.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-print',
		popover: {
			title: wizardTourConfig.translations.print.title,
			description: wizardTourConfig.translations.print.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-stored-until',
		popover: {
			title: wizardTourConfig.translations.storedUnitl.title,
			description: wizardTourConfig.translations.storedUnitl.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-button-cancel',
		popover: {
			title: wizardTourConfig.translations.buttonCancel.title,
			description: wizardTourConfig.translations.buttonCancel.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-claim-url',
		popover: {
			title: wizardTourConfig.translations.claimUrl.title,
			description: wizardTourConfig.translations.claimUrl.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-print-claim-label',
		popover: {
			title: wizardTourConfig.translations.claimLabel.title,
			description: wizardTourConfig.translations.claimLabel.description
		}
	},
	{
		element: '.packetery-js-wizard-metabox-button-cancel-claim',
		popover: {
			title: wizardTourConfig.translations.cancelClaim.title,
			description: wizardTourConfig.translations.cancelClaim.description
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
