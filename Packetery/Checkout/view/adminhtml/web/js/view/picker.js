define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/abstract',
    'ko',
    'mage/translate',
    'mage/storage',
    'mage/url',
    'jquery',
], function(
    _,
    uiRegistry,
    Component,
    ko,
    $t,
    storage,
    frontUrlBuilder,
    jQuery
) {
    'use strict';

    frontUrlBuilder.setBaseUrl(window.packetery.baseUrl);

    var config = {};
    var loadConfig = function(onSuccess) {
        jQuery('body').trigger('processStart');
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
        ).always(function() {
            jQuery('body').trigger('processStop');
        });
    };

    var mixin = {
        isStoreConfigLoaded: ko.observable(false),
        errorValidationMessage: ko.observable(''),
        pickedValidatedAddress: ko.observable(''),
        buttonLabel: ko.observable($t('Check delivery address')),

        isPickupPointDelivery: function() {
            return uiRegistry.get('inputName = general[misc][isPickupPointDelivery]').value() === '1';
        },

        isAnyAddressDelivery: function() {
            return uiRegistry.get('inputName = general[misc][isAnyAddressDelivery]').value() === '1';
        },

        getPacketaSymbolUrl: function() {
            return window.packetery.packetaSymbolUrl;
        },

        initialize: function() {

            var fieldset = uiRegistry.get('index = general');
            uiRegistry.get('inputName = general[address_validated]', function(item) {
                if (item.value() === '1') {
                    mixin.buttonLabel($t('Change delivery address'));
                }
                if (item.value() === '0') {
                    mixin.buttonLabel($t('Check delivery address'));
                }
            });

            if (mixin.isPickupPointDelivery()) {
                fieldset.label = $t('Pickup point selection');
            }

            if (mixin.isAnyAddressDelivery()) {
                fieldset.label = $t('Shipping address validation');
            }

            return this._super();
        },

        packetaButtonClick: function() {
            var packetaApiKey = config.apiKey;
            var countryId = uiRegistry.get('inputName = general[recipient_country_id]').value();

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
        },

        packetaHDButtonClick: function() {
            var getDestinationAddress = function() {
                return {
                    country: uiRegistry.get('inputName = general[recipient_country_id]').value().toLocaleLowerCase(),
                    street: uiRegistry.get('inputName = general[recipient_street]').value(),
                    houseNumber: uiRegistry.get('inputName = general[recipient_house_number]').value(),
                    city: uiRegistry.get('inputName = general[recipient_city]').value(),
                    postcode: uiRegistry.get('inputName = general[recipient_zip]').value()
                };
            };

            var packetaApiKey = config.apiKey;
            var destinationAddress = getDestinationAddress();
            var countryId = uiRegistry.get('inputName = general[recipient_country_id]').value();
            var pointId = uiRegistry.get('inputName = general[point_id]').value();

            var options = {
                country: destinationAddress.country,
                language: config.packetaOptions.language,
                layout: 'hd',
                street: destinationAddress.street,
                city: destinationAddress.city,
                postcode: destinationAddress.postcode,
                carrierId: pointId,
            };

            if (destinationAddress.houseNumber) {
                options.houseNumber = destinationAddress.houseNumber;
            }

            var addressSelected = function(result) {
                mixin.errorValidationMessage('');

                if (!result) {
                    mixin.errorValidationMessage($t("Address validation is out of order"));
                    return;
                }

                if (!result.address) {
                    return; // widget closed
                }

                var address = result.address;

                if (address.country !== countryId.toLocaleLowerCase()) {
                    mixin.errorValidationMessage($t("Please select address from specified country"));
                    return;
                }

                uiRegistry.get('inputName = general[address_validated]').value('1');
                uiRegistry.get('inputName = general[recipient_street]').value(address.street || null);
                uiRegistry.get('inputName = general[recipient_house_number]').value(address.houseNumber || null);
                uiRegistry.get('inputName = general[recipient_city]').value(address.city || null);
                uiRegistry.get('inputName = general[recipient_zip]').value(address.postcode || null);
                uiRegistry.get('inputName = general[recipient_county]').value(address.county || null);
                uiRegistry.get('inputName = general[recipient_country_id]').value(countryId);
                uiRegistry.get('inputName = general[recipient_longitude]').value(address.longitude || null);
                uiRegistry.get('inputName = general[recipient_latitude]').value(address.latitude || null);

                mixin.pickedValidatedAddress(
                    [ address.street, address.houseNumber, address.city ].filter(function(value) {
                        return !!value;
                    }).join(' ')
                );
            };

            Packeta.Widget.pick(packetaApiKey, addressSelected, options);
        }
    };

    loadConfig(function() {
        mixin.isStoreConfigLoaded(true);
    });

    return Component.extend(mixin);
});
