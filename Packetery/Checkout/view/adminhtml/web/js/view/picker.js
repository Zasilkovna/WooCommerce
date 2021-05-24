define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/abstract',
    'ko',
    'mage/translate',
    'mage/storage',
    'mage/url'
], function(
    _,
    uiRegistry,
    Component,
    ko,
    $t,
    storage,
    frontUrlBuilder
) {
    'use strict';

    frontUrlBuilder.setBaseUrl(window.packetery.baseUrl);

    var config = {};
    var loadConfig = function(onSuccess) {
        var configUrl = frontUrlBuilder.build('packetery/config/storeconfig');
        storage.get(configUrl).done(
            function(response) {
                if(response.success) {
                    config = JSON.parse(response.value);
                    onSuccess(config);
                } else {
                    console.error('Endpoint for packeta config returned non-success response');
                }
            }
        ).fail(
            function() {
                console.error('Endpoint for packeta config failed');
            }
        );
    };

    var mixin = {
        isStoreConfigLoaded: ko.observable(false),

        packetaButtonClick: function() {
            var packetaApiKey = config.apiKey;
            var countryId = uiRegistry.get('inputName = general[misc][country_id]').value();

            var options = {
                webUrl: config.packetaOptions.webUrl,
                appIdentity: config.packetaOptions.appIdentity,
                country: countryId.toLocaleLowerCase(),
                language: config.packetaOptions.language,
            };

            var pickupPointSelected = function(point) {
                if(!point) {
                    return;
                }

                var pointId = (point.pickupPointType === 'external' ? point.carrierId : point.id);
                var pointName = (point.name ? point.name : null);
                var carrierId = (point.carrierId ? point.carrierId : null);
                var carrierPickupPointId = (point.carrierPickupPointId ? point.carrierPickupPointId : null);

                uiRegistry.get('inputName = general[point_id]').value(pointId);
                uiRegistry.get('inputName = general[point_name]').value(pointName);
                uiRegistry.get('inputName = general[is_carrier]').value(carrierId ? 1 : 0);
                uiRegistry.get('inputName = general[carrier_pickup_point]').value(carrierPickupPointId);
            };

            Packeta.Widget.pick(packetaApiKey, pickupPointSelected, options);
        }
    };

    loadConfig(function() {
        mixin.isStoreConfigLoaded(true);
    });

    return Component.extend(mixin);
});
