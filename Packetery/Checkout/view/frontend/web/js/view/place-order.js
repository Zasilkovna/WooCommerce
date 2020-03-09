define([
	'Magento_Checkout/js/model/quote',
	'Magento_Checkout/js/model/url-builder',
	'Magento_Customer/js/model/customer',
	'Magento_Checkout/js/model/place-order'
], function (quote, urlBuilder, customer, placeOrderService) {
	'use strict';

	return function (paymentData, messageContainer) {
		var serviceUrl, payload;

		let pointId = "";
		let pointName = "";

		if( window.packetaPointId !== undefined ){
			if ( window.packetaPointId != "" ){
				pointId = window.packetaPointId;
			}
		}
		if( window.packetaPointName !== undefined ){
			if ( window.packetaPointName != "" ){
				pointName = window.packetaPointName;
			}
		}

		payload = {
			cartId: quote.getQuoteId(),
			billingAddress: quote.billingAddress(),
			paymentMethod: paymentData,
			packetery:{
				id: pointId,
				name: pointName
			}
		};

		if (customer.isLoggedIn()) {
			serviceUrl = urlBuilder.createUrl('/carts/mine/payment-information', {});
		} else {
			serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/payment-information', {
				quoteId: quote.getQuoteId()
			});
			payload.email = quote.guestEmail;
		}

		return placeOrderService(serviceUrl, payload, messageContainer);
	};
});
