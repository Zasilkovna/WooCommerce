define(
    [
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/shipping-service',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/step-navigator',
        'Packetery_Checkout/js/model/service',
        'mage/translate',
        'mage/storage',
        'mage/url',
        'ko'
    ], function(
        quote,
        shippingService,
        fullScreenLoader,
        stepNavigator,
        packeteryService,
        $t,
        storage,
        url,
        ko
    ) {
        'use strict';

        var config = null;

        var getSelectedRateConfig = function() {
            var selectedShippingRateCode = mixin.getShippingRateCode(quote.shippingMethod());
            var config = mixin.shippingRatesConfig();
            return config[selectedShippingRateCode];
        };

        var formatPacketaAddress = function(address) {
            if (!address) {
                return '';
            }

            return [ address.street, address.houseNumber, address.city ].filter(function(value) {
                return !!value;
            }).join(' ');
        };

        var mixin = {
            isStoreConfigLoaded: ko.observable(false),
            pickedDeliveryPlace: ko.observable(packeteryService.getPacketaPoint({name: ''}).name || ''),
            pickedValidatedAddress: ko.observable(formatPacketaAddress(packeteryService.getPacketaValidatedAddress(false))),
            shippingRatesConfig: ko.observable(null),
            errorValidationMessage: ko.observable(''),
            stepNavigatorReady: ko.observable(false),

            initialize: function() {
                this._super();

                mixin.stepNavigatorReady.subscribe(function() {
                    if (!stepNavigator.isProcessed('shipping')) {
                        resetPickedPacketaPoint();
                        resetPickedValidatedAddress();
                    }

                    var createCallbackForShippingStep = function(callback) {
                        return function() {
                            if (!stepNavigator.isProcessed('shipping')) {
                                callback.apply(null, arguments);
                            }
                        }
                    };

                    quote.shippingAddress.subscribe(createChangeSubscriber(createCallbackForShippingStep(resetPickedPacketaPoint), function(lastValue, value) {
                        return lastValue.countryId !== value.countryId;
                    }, quote.shippingAddress()));

                    quote.shippingAddress.subscribe(createChangeSubscriber(createCallbackForShippingStep(resetPickedValidatedAddress), function(lastValue, value) {
                        return lastValue.countryId !== value.countryId;
                    }, quote.shippingAddress()));

                    // TODO: implement selected address history
                    quote.shippingMethod.subscribe(createChangeSubscriber(createCallbackForShippingStep(resetPickedValidatedAddress), function(lastValue, value) {
                        return mixin.getShippingRateCode(lastValue) !== mixin.getShippingRateCode(value);
                    }, quote.shippingMethod()));

                    var address = packeteryService.getPacketaValidatedAddress(null);
                    if (address) {
                        quote.shippingAddress(Object.assign(quote.shippingAddress(), {
                            city: address.city || null,
                            street: [ address.street || '', address.houseNumber || '' ],
                            postcode: address.postcode || null,
                            countryId: address.countryId,
                            region: address.county || null,
                            regionCode: null,
                            regionId: null
                        }));
                    }
                });

                var stepNavigatorReadinessChecker = function() {
                    if (mixin.stepNavigatorReady() === true) {
                        return;
                    }

                    stepNavigator.steps().sort(stepNavigator.sortItems).some(function (element) {
                        if (element.isVisible()) {
                            mixin.stepNavigatorReady(true);
                            return true;
                        }

                        return false;
                    });
                };

                stepNavigator.steps.subscribe(function(steps) {
                    for(var stepKey in steps) {
                        if (!steps.hasOwnProperty(stepKey)) {
                            continue;
                        }

                        var element = steps[stepKey];
                        element.isVisible.subscribe(stepNavigatorReadinessChecker);
                    }

                    stepNavigatorReadinessChecker();
                });

                stepNavigatorReadinessChecker();
            },

            getDestinationAddress: function() {
                var destinationAddress = quote.shippingAddress() || quote.billingAddress();

                var data = {
                    country: (destinationAddress.countryId).toLocaleLowerCase(),
                    countryId: destinationAddress.countryId,
                    houseNumber: null,
                    postcode: destinationAddress.postcode,
                    street: destinationAddress.street.join(' '),
                    city: destinationAddress.city
                };

                destinationAddress = packeteryService.getPacketaValidatedAddress(false);
                if (destinationAddress) {
                    data = Object.assign(data, {
                        country: (destinationAddress.countryId).toLocaleLowerCase(),
                        countryId: destinationAddress.countryId,
                        houseNumber: destinationAddress.houseNumber,
                        postcode: destinationAddress.postcode,
                        street: destinationAddress.street,
                        city: destinationAddress.city
                    });
                }

                return data;
            },

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

            packetaHDButtonClick: function() {
                var packetaApiKey = config.apiKey;
                var destinationAddress = mixin.getDestinationAddress();
                var shippingRateConfig = getSelectedRateConfig();

                var options = {
                    country: destinationAddress.country,
                    language: config.packetaOptions.language,
                    layout: 'hd',
                    street: destinationAddress.street,
                    city: destinationAddress.city,
                    postcode: destinationAddress.postcode,
                    carrierId: shippingRateConfig.directionId,
                };

                if (destinationAddress.houseNumber) {
                    options.houseNumber = destinationAddress.houseNumber;
                }

                Packeta.Widget.pick(packetaApiKey, showSelectedAddress, options);
            },

            validateShippingInformation: function() {
                var packetaPoint = packeteryService.getPacketaPoint({});
                if(packeteryPickupPointSelected() && !packetaPoint.pointId) {
                    var message = $t("Please select pickup point");
                    this.errorValidationMessage(message);
                    return false;
                }

                var selectedShippingRateConfig = getSelectedRateConfig();
                if(packeteryHDSelected() && selectedShippingRateConfig.addressValidation === 'required' && !packeteryService.getPacketaValidatedAddress(false)) {
                    this.errorValidationMessage($t("Please select address via Packeta widget"));
                    return false;
                }

                return this._super();
            },

            getShippingRateCode: function(shippingRate) {
                shippingRate = shippingRate || {};
                return shippingRate.carrier_code + '_' + shippingRate.method_code;
            },

            getRateConfig: function(method) {
                var config = mixin.shippingRatesConfig();
                return config[mixin.getShippingRateCode(method)] || {};
            }
        };

        var resetPickedPacketaPoint = function() {
            mixin.pickedDeliveryPlace('');
            localStorage.packetaPoint = JSON.stringify({
                pointId: null,
                name: null,
                pickupPointType: null,
                carrierId: null,
                carrierPickupPointId: null
            });
        };

        var resetPickedValidatedAddress = function() {
            mixin.pickedValidatedAddress('');
            localStorage.packetaValidatedAddress = '';
        };

        var createChangeSubscriber = function(callback, comparator, lastVal) {
            return function (value) {
                if(comparator(lastVal, value)) {
                    lastVal = value;
                    callback(value);
                }

                lastVal = value;
            };
        };

        var updateShippingRates = function(rates) {
            mixin.shippingRatesConfig(null);
            loadShippingRatesConfig(rates,function (responseValue) {
                mixin.shippingRatesConfig(responseValue.rates);
            });
        };

        var getShippingRateCollectionIdentificator = function(rates) {
            if (!rates) {
                return '';
            }

            return rates.map(function(item) {
                return mixin.getShippingRateCode(item);
            }).join('+');
        };

        var shippingRatesSubscriber = createChangeSubscriber(updateShippingRates, function(lastValue, value) {
            return getShippingRateCollectionIdentificator(lastValue) !== getShippingRateCollectionIdentificator(value);
        }, shippingService.getShippingRates()());

        shippingService.getShippingRates().subscribe(shippingRatesSubscriber);

        var packeteryPickupPointSelected = function() {
            var shippingMethod = quote.shippingMethod();
            var selectedRateConfig = getSelectedRateConfig();
            if(shippingMethod && selectedRateConfig && selectedRateConfig.isPacketaRate && shippingMethod['method_code'] === 'pickupPointDelivery') {
                return true;
            }

            return false;
        };

        var packeteryHDSelected = function() {
            var selectedRateConfig = getSelectedRateConfig();
            if(selectedRateConfig && selectedRateConfig.isPacketaRate && selectedRateConfig.isAnyAddressDelivery) {
                return true;
            }

            return false;
        };

        var showSelectedPickupPoint = function(point) {
            if(point) {
                var pointId = point.pickupPointType === 'external' ? point.carrierId : point.id;
                mixin.pickedDeliveryPlace(point ? point.name : "");

                // nastavíme, aby si pak pro založení objednávky převzal place-order.js, resp. OrderPlaceAfter.php
                localStorage.packetaPoint = JSON.stringify({
                    pointId: pointId ? pointId : null,
                    name: point.name ? point.name : null,
                    pickupPointType: point.pickupPointType ? point.pickupPointType : null,
                    carrierId: point.carrierId ? point.carrierId : null,
                    carrierPickupPointId: point.carrierPickupPointId ? point.carrierPickupPointId : null
                });
            } else {
                resetPickedPacketaPoint();
            }
        }

        var showSelectedAddress = function(result) {
            mixin.errorValidationMessage('');

            if (!result) {
                mixin.errorValidationMessage($t("Address validation is out of order"));
                return;
            }

            if (!result.address) {
                return; // widget closed
            }

            resetPickedValidatedAddress();
            var destinationAddress = mixin.getDestinationAddress();
            var address = result.address;

            if (address.country !== destinationAddress.country) {
                mixin.errorValidationMessage($t("Please select address from specified country"));
                return;
            }

            localStorage.packetaValidatedAddress = JSON.stringify({
                city: address.city || null,
                street: address.street || null,
                houseNumber: address.houseNumber || null,
                postcode: address.postcode || null,
                countryId: destinationAddress.countryId,
                county: address.county || null,
                longitude: address.longitude || null,
                latitude: address.latitude || null,
            });

            mixin.pickedValidatedAddress(formatPacketaAddress(packeteryService.getPacketaValidatedAddress('')));
            quote.shippingAddress(Object.assign(quote.shippingAddress(), {
                city: address.city || null,
                street: [ address.street || '', address.houseNumber || '' ],
                postcode: address.postcode || null,
                countryId: destinationAddress.countryId,
                region: address.county || null,
                regionCode: null,
                regionId: null
            }));
        }

        var loadStoreConfig = function(onSuccess) {
            fullScreenLoader.startLoader();
            var serviceUrl = url.build('packetery/config/storeconfig');
            storage.get(serviceUrl).done(
                function(response) {
                    if(response.success) {
                        config = JSON.parse(response.value);
                        onSuccess(config);
                    }
                }
            ).fail(
                function(response) {
                    return response.value;
                }
            ).always(
                function() {
                    fullScreenLoader.stopLoader();
                }
            );
        };

        var loadShippingRatesConfig = function(rates, onSuccess) {
            fullScreenLoader.startLoader();
            var serviceUrl = url.build('packetery/config/shippingRatesConfig');
            storage.post(
                serviceUrl,
                JSON.stringify({
                    rates: rates.map(function(rate) {
                        return {
                            rateCode: mixin.getShippingRateCode(rate),
                            carrierCode: rate.carrier_code,
                            methodCode: rate.method_code,
                            countryId: quote.shippingAddress().countryId, // countryId that Magento uses to collect shipping rates
                        };
                    }),
                })
            ).done(
                function(response) {
                    if(response.success) {
                        onSuccess(response.value);
                    }
                }
            ).fail(
                function(response) {
                    return response.value;
                }
            ).always(
                function() {
                    fullScreenLoader.stopLoader();
                }
            );
        };

        loadStoreConfig(function() {
            mixin.isStoreConfigLoaded(true);
        });

        shippingRatesSubscriber(shippingService.getShippingRates()()); // shippingService.getShippingRates() returns observable object

        return function(target) { // target == Result that Magento_Ui/.../default returns.
            return target.extend(mixin); // new result that all other modules receive
        };
    });
