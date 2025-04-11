window.startCustomsDeclarationWizardTour = function () {
	const driver = window.driver.js.driver;

	const steps = [
		{
			element: '.js-packetery-ead',
			popover: {
				title: wizardTourConfig.translations.ead.title,
				description: wizardTourConfig.translations.ead.description,
			}
		},
		{
			element: '.js-packetery-delivery-cost',
			popover: {
				title: wizardTourConfig.translations.cost.title,
				description: wizardTourConfig.translations.cost.description,
			}
		},
		{
			element: '.js-packetery-invoice-number',
			popover: {
				title: wizardTourConfig.translations.number.title,
				description: wizardTourConfig.translations.number.description,
			}
		},
		{
			element: '.js-packetery-invoice-issue-date',
			popover: {
				title: wizardTourConfig.translations.invoiceIssueDate.title,
				description: wizardTourConfig.translations.invoiceIssueDate.description,
			}
		},
		{
			element: '.js-packetery-invoice-file',
			popover: {
				title: wizardTourConfig.translations.invoiceFile.title,
				description: wizardTourConfig.translations.invoiceFile.description,
			}
		},
		{
			element: '.js-packetery-mrn',
			popover: {
				title: wizardTourConfig.translations.mrn.title,
				description: wizardTourConfig.translations.mrn.description,
			}
		},
		{
			element: '.js-packetery-ead-file',
			popover: {
				title: wizardTourConfig.translations.eadFile.title,
				description: wizardTourConfig.translations.eadFile.description,
			}
		},
		{
			element: '.js-packetery-wizard .js-packetery-customs-code',
			popover: {
				title: wizardTourConfig.translations.customsCode.title,
				description: wizardTourConfig.translations.customsCode.description,
			}
		},
		{
			element: '.js-packetery-wizard .js-packetery-value',
			popover: {
				title: wizardTourConfig.translations.value.title,
				description: wizardTourConfig.translations.value.description,
			}
		},
		{
			element: '.js-packetery-wizard .js-packetery-product-name-en',
			popover: {
				title: wizardTourConfig.translations.productNameEn.title,
				description: wizardTourConfig.translations.productNameEn.description,
			}
		},
		{
			element: '.js-packetery-wizard .js-packetery-product-name',
			popover: {
				title: wizardTourConfig.translations.productName.title,
				description: wizardTourConfig.translations.productName.description,
			}
		},
		{
			element: '.js-packetery-wizard .js-packetery-units-count',
			popover: {
				title: wizardTourConfig.translations.unitsCount.title,
				description: wizardTourConfig.translations.unitsCount.description,
			}
		},
		{
			element: '.js-packetery-wizard .js-packetery-country-of-origin',
			popover: {
				title: wizardTourConfig.translations.countryOfOrigin.title,
				description: wizardTourConfig.translations.countryOfOrigin.description,
			}
		},
		{
			element: '.js-packetery-wizard .js-packetery-weight',
			popover: {
				title: wizardTourConfig.translations.weight.title,
				description: wizardTourConfig.translations.weight.description,
			}
		},
		{
			element: '.js-packetery-wizard .js-packetery-is-food-or-book',
			popover: {
				title: wizardTourConfig.translations.isFoodOrBook.title,
				description: wizardTourConfig.translations.isFoodOrBook.description,
			}
		},
		{
			element: '.js-packetery-wizard .js-packetery-is-voc',
			popover: {
				title: wizardTourConfig.translations.isVOC.title,
				description: wizardTourConfig.translations.isVOC.description,
			}
		},
		{
			element: '.js-packetery-wizard .js-packetery-add-declaration',
			popover: {
				title: wizardTourConfig.translations.addDeclaration.title,
				description: wizardTourConfig.translations.addDeclaration.description,
			}
		},
	];

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
			steps: steps,
			onDestroyStarted: function () {
				if ( ! driverObj.hasNextStep() || window.confirm( wizardTourConfig.translations.areYouSure ) ) {
					driverObj.destroy();
				}
			},
		} );
	driverObj.drive();
}
