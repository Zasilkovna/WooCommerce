define([
    'jquery',
    'mage/utils/wrapper',
    'Packetery_Checkout/js/model/service',
], function ($, wrapper, packeteryService) {
    'use strict';

    return function (placeOrderAction) {

        // Overrides default place order model and adds packetery data to payload
        return wrapper.wrap(placeOrderAction, function (originalAction, serviceUrl, payload, messageContainer) {
            payload.packetery = {
                point: packeteryService.getPacketaPoint(null),
                validatedAddress: packeteryService.getPacketaValidatedAddress(null)
            };

            return originalAction(serviceUrl, payload, messageContainer);
        });
    };
});
