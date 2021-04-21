define(
	[
		'jquery',
		'underscore',
		'Magento_Ui/js/form/form',
		'ko',
		'Magento_Customer/js/model/customer',
		'Magento_Customer/js/model/address-list',
		'Magento_Checkout/js/model/address-converter',
		'Magento_Checkout/js/model/quote',
		'Magento_Checkout/js/action/create-shipping-address',
		'Magento_Checkout/js/action/select-shipping-address',
		'Magento_Checkout/js/model/shipping-rates-validator',
		'Magento_Checkout/js/model/shipping-address/form-popup-state',
		'Magento_Checkout/js/model/shipping-service',
		'Magento_Checkout/js/action/select-shipping-method',
		'Magento_Checkout/js/model/shipping-rate-registry',
		'Magento_Checkout/js/action/set-shipping-information',
		'Magento_Checkout/js/model/step-navigator',
		'Magento_Ui/js/modal/modal',
		'Magento_Checkout/js/model/checkout-data-resolver',
		'Magento_Checkout/js/checkout-data',
		'uiRegistry',
		'mage/translate',
		'mage/storage',
		'mage/url',
		'Magento_Checkout/js/model/shipping-rate-service'
	],function (
		$,
		_,
		Component,
		ko,
		customer,
		addressList,
		addressConverter,
		quote,
		createShippingAddress,
		selectShippingAddress,
		shippingRatesValidator,
		formPopUpState,
		shippingService,
		selectShippingMethodAction,
		rateRegistry,
		setShippingInformationAction,
		stepNavigator,
		modal,
		checkoutDataResolver,
		checkoutData,
		registry,
		$t,
		storage,
		url) {
		'use strict';

		var mixin = {

				setShippingInformation: function () {

					if( !shippingSelected() ){
						var message = $t("Please select shipping method");
						alert(message);
						return;
					}
					if( packeterySelected() && jQuery("#packeta-branch-id").val() == "" ){
						var message = $t("Please select pickup point");
						alert(message);
						return;
					}

					if (this.validateShippingInformation()) {
						quote.billingAddress(null);
						checkoutDataResolver.resolveBillingAddress();
						setShippingInformationAction().done(
							function () {
								stepNavigator.next();
							}
						);
					}
				},

			selectShippingMethod: function (shippingMethod) {
				selectShippingMethodAction(shippingMethod);
				checkoutData.setSelectedShippingRate(shippingMethod.carrier_code + '_' + shippingMethod.method_code);

				return true;
			},
			getconfigValue: function () {
				var serviceUrl = url.build('packetery/config/storeconfig');

                packeteryToggleBoxes();

				//-------------------------

				storage.get(serviceUrl).done(
					function (response) {
						if (response.success) {
							var config = JSON.parse(response.value);
							var packetaButton = jQuery('#open-packeta-widget');
							var countryCode = (quote.shippingAddress().countryId).toLocaleLowerCase();

							packetaButton.attr('data-api-key', config.apiKey);
							packetaButton.attr('data-web-url', config.packetaOptions.webUrl);
							packetaButton.attr('data-app-identity', config.packetaOptions.appIdentity);
							packetaButton.attr('data-language', config.packetaOptions.language);
							packetaButton.attr('data-country-code', countryCode);
						}
					}
				).fail(
					function (response) {
						return response.value
					}
				);
			},
			packetaButtonClick: function () {
				var packetaButton = jQuery('#open-packeta-widget');
				var packetaApiKey = packetaButton.data('api-key');

				var options = {
					webUrl: packetaButton.data('web-url'),
					appIdentity: packetaButton.data('app-identity'),
					country: packetaButton.data('country-code'),
					language: packetaButton.data('language'),
				};

				Packeta.Widget.pick(packetaApiKey, showSelectedPickupPoint, options);
			}

		};

		return function (target) { // target == Result that Magento_Ui/.../default returns.
			return target.extend(mixin); // new result that all other modules receive
		};
	});

window.packeterySelected = function() {
    if(jQuery(".packetery-method-wrapper .radio[value=packetery_pickupPointDelivery]:checked").length > 0) {
        return true;
    }

    return false;
};

window.packeteryToggleBoxes = function() {
    // to make sure our Open widget btn is properly displayed
    var shippingMethodSelected = jQuery(".packetery-method-wrapper .radio[value^=packetery_]");
    shippingMethodSelected.each(function() {
        var $item = jQuery(this);
        var checked = $item.is(':checked');
        if(checked) {
            $item.parents('.packetery-method-wrapper:first').next('.packetery-zas-box').show();
        } else {
            $item.parents('.packetery-method-wrapper:first').next('.packetery-zas-box').hide();
        }
    });
};

jQuery(document).ready(function() {
    jQuery('body').on('change', '.packetery-method-wrapper .radio[value^=packetery_]', function() {
        packeteryToggleBoxes();
    }).on('click', '.packetery-method-wrapper', function() {
        packeteryToggleBoxes();
    });
});

// we create event listener for event of type message which widget uses to communicate with application
window.shippingSelected = function(){
	return( jQuery(".radio:checked").length > 0 );
};

/**
 * Callback po zavreni widgetu zasilkovny
 * @param {name, id, ..} point
 */
function showSelectedPickupPoint(point)
{
	var pickedDeliveryPlace = document.getElementById('picked-delivery-place');
	var packetaBranchId = document.getElementById('packeta-branch-id');

    packetaBranchId.value = null;
    pickedDeliveryPlace.innerText = "";

    if(point)
    {
        var pointId = point.pickupPointType === 'external' ? point.carrierId : point.id;
        pickedDeliveryPlace.innerText = (point ? point.name : "");
        packetaBranchId.value = pointId;

        // nastavíme, aby si pak pro založení objednávky převzal place-order.js, resp. OrderPlaceAfter.php
        window.packetaPoint = {
            pointId: pointId ? pointId : null,
            name: point.name ? point.name : null,
            pickupPointType: point.pickupPointType ? point.pickupPointType : null,
            carrierId: point.carrierId ? point.carrierId : null,
            carrierPickupPointId: point.carrierPickupPointId ? point.carrierPickupPointId : null
        };
	} else {
        window.packetaPoint = {
            pointId: null,
            name: null,
            pickupPointType: null,
            carrierId: null,
            carrierPickupPointId: null
        };
    }
}
