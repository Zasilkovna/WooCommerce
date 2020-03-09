define([
	'ko',
	'jquery',
	'uiComponent',
	'mage/storage',
	'mage/url'
], function (ko, $, Component, storage, url) {
	'use strict';

	console.log("Yello!");

	return Component.extend({
		defaults: {
			template: 'Packetery_Checkout/custom-method-item-template'
		},
		getconfigValue: function () {
			var serviceUrl = url.build('packetery/custom/storeconfig');

			storage.get(serviceUrl).done(
				function (response) {
					if (response.success) {
						return response.value
					}
				}
			).fail(
				function (response) {
					return response.value
				}
			);
			return false;
		}
	});
});
