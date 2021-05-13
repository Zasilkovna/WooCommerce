define([
    'jquery',
    'mage/utils/wrapper'
], function ($, wrapper) {
    'use strict';

    return function (placeOrderAction) {

        // Overrides default place order model and adds packetery data to payload
        return wrapper.wrap(placeOrderAction, function (originalAction, serviceUrl, payload, messageContainer) {

            payload.packetery = {
                point: window.packetaPoint
            };

            return originalAction(serviceUrl, payload, messageContainer);
        });
    };
});
