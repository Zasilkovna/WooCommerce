define(
    [
        'Magento_Checkout/js/model/quote',
        'mage/translate',
        'mage/storage',
        'mage/url',
        'ko'
    ], function(
        quote,
        $t,
        storage,
        url,
        ko) {
        'use strict';

        var config = null;
        var mixin = {
            isStoreConfigLoaded: ko.observable(false),
            pickedDeliveryPlace: ko.observable(''),

            packetaButtonClick: function() {
                if(config === null) {
                    return; // config not yet loaded
                }

                var packetaApiKey = config.apiKey;
                var countryCode = (quote.shippingAddress().countryId).toLocaleLowerCase();

                var options = {
                    webUrl: config.packetaOptions.webUrl,
                    appIdentity: config.packetaOptions.appIdentity,
                    country: countryCode,
                    language: config.packetaOptions.language,
                };

                Packeta.Widget.pick(packetaApiKey, showSelectedPickupPoint, options);
            },

            setShippingInformation: function() {
                var packetaPoint = window.packetaPoint || {};
                if(packeteryPickupPointSelected() && !packetaPoint.pointId) {
                    var message = $t("Please select pickup point");
                    alert(message);
                    return;
                }

                return this._super();
            },
        };

        var resetPickedPacketaPoint = function() {
            mixin.pickedDeliveryPlace('');
            window.packetaPoint = {
                pointId: null,
                name: null,
                pickupPointType: null,
                carrierId: null,
                carrierPickupPointId: null
            };
        };

        resetPickedPacketaPoint();
        quote.shippingAddress.subscribe(function() {
            resetPickedPacketaPoint();
        });

        var packeteryPickupPointSelected = function() {
            var shippingMethod = quote.shippingMethod();
            if(shippingMethod && shippingMethod['method_code'] === 'pickupPointDelivery') {
                return true;
            }

            return false;
        };

        var showSelectedPickupPoint = function(point) {
            if(point) {
                var pointId = point.pickupPointType === 'external' ? point.carrierId : point.id;
                mixin.pickedDeliveryPlace(point ? point.name : "");

                // nastavíme, aby si pak pro založení objednávky převzal place-order.js, resp. OrderPlaceAfter.php
                window.packetaPoint = {
                    pointId: pointId ? pointId : null,
                    name: point.name ? point.name : null,
                    pickupPointType: point.pickupPointType ? point.pickupPointType : null,
                    carrierId: point.carrierId ? point.carrierId : null,
                    carrierPickupPointId: point.carrierPickupPointId ? point.carrierPickupPointId : null
                };
            } else {
                resetPickedPacketaPoint();
            }
        }

        var loadStoreConfig = function() {
            return new Promise(function(resolve) {
                var serviceUrl = url.build('packetery/config/storeconfig');
                storage.get(serviceUrl).done(
                    function(response) {
                        if(response.success) {
                            config = JSON.parse(response.value);
                            resolve(config);
                        }
                    }
                ).fail(
                    function(response) {
                        return response.value
                    }
                );
            });
        };

        loadStoreConfig().then(function() {
            mixin.isStoreConfigLoaded(true);
        });

        return function(target) { // target == Result that Magento_Ui/.../default returns.
            return target.extend(mixin); // new result that all other modules receive
        };
    });
