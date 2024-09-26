var packeteryLoadCheckout = function( $, settings ) {
	var postWithNonce = function ( url, data, errorMessage ) {
		$.ajax( {
			type: 'POST',
			url: url,
			data: data,
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', settings.nonce );
			},
		} )
		.fail( function ( xhr, status, error ) {
			console.log( 'Packeta: ' + errorMessage + error );
		} );
	}

	var getCheckoutAddress = function () {
		var section = '';
		if ( $( '#ship-to-different-address-checkbox:checked' ).length === 1 ) {
			section = 'shipping';
		} else {
			section = 'billing';
		}
		return {
			street: $( '#' + section + '_address_1' ),
			city: $( '#' + section + '_city' ),
			postCode: $( '#' + section + '_postcode' )
		}
	}

	var packeteryCheckout = function( settings ) {
		var rateAttrValues = {};
		if ( settings.savedData ) {
			rateAttrValues = settings.savedData;
		}

		var getPacketaWidget = function() {
			var $widgetDiv = $( '#shipping_method input[type="radio"]:checked' ).parent().find( '.packeta-widget' );
			if ( $widgetDiv.length > 0 ) {
				return $widgetDiv;
			}

			return $( '.packeta-widget' );
		};

		var $widgetDiv = getPacketaWidget();

		var getDestinationAddress = function() {
			var extractDestination = function( section ) {
				var countryValue;
				var $country = $( '#' + section + '_country' );
				if ( $country.length >= 1 ) {
					countryValue = $country.val().toLowerCase();
				}
				return {
					street: $( '#' + section + '_address_1' ).val(),
					city: $( '#' + section + '_city' ).val(),
					country: countryValue,
					postCode: $( '#' + section + '_postcode' ).val()
				};
			};

			if ( $( '#shipping_country:visible' ).length === 1 ) {
				return extractDestination( 'shipping' );
			} else {
				return extractDestination( 'billing' );
			}
		};

		var getRateAttrValue = function( carrierRateId, attribute, defaultValue ) {
			if ( typeof rateAttrValues[ carrierRateId ] === 'undefined' || typeof rateAttrValues[ carrierRateId ][ attribute ] === 'undefined' ) {
				return defaultValue;
			}

			return rateAttrValues[ carrierRateId ][ attribute ];
		};

		var shortenShippingRateId = function( rateId ) {
			var rateIdArray = rateId.split(":");
			return rateIdArray[rateIdArray.length - 1];
		};

		var getShippingRateId = function() {
			var $selectedRadio = $( '#shipping_method input[type="radio"]:checked' );
			if ( $selectedRadio.length ) {
				return shortenShippingRateId( $selectedRadio.val() );
			}

			var $selectedHiddenInput = $( '#shipping_method input[type="hidden"]' );
			if ( $selectedHiddenInput.length ) {
				return shortenShippingRateId( $selectedHiddenInput.val() );
			}

			return null;
		};

		var resetWidgetInfoClasses = function() {
			$widgetDiv.find( '.packeta-widget-info' ).removeClass('packeta-widget-info-error').removeClass('packeta-widget-info-success');
		};

		var resetWidgetInfo = function() {
			resetWidgetInfoClasses();
			$widgetDiv.find( '.packeta-widget-info' ).html('');
			$widgetDiv.find( '.packeta-widget-selected-address' ).html('');
		};

		var rewriteDestinationAddress = function ( carrierRateId ) {
			var destinationAddress = getCheckoutAddress();
			destinationAddress.street.val( getRateAttrValue( carrierRateId, 'packetery_address_street', '' ) + ' ' + getRateAttrValue( carrierRateId, 'packetery_address_houseNumber', '' ) );
			destinationAddress.city.val( getRateAttrValue( carrierRateId, 'packetery_address_city', '' ) );
			destinationAddress.postCode.val( getRateAttrValue( carrierRateId, 'packetery_address_postCode', '' ) );
		};

		var showHomeDeliveryAddress = function(carrierRateId) {
			resetWidgetInfoClasses();
			if (getRateAttrValue( carrierRateId, settings.homeDeliveryAttrs[ 'isValidated' ].name, '0' ) === '1') {
				rewriteDestinationAddress( carrierRateId );
			}
		};

		var showCarDeliveryAddress = function(carrierRateId) {
			resetWidgetInfoClasses();
			if ( getRateAttrValue( carrierRateId, settings.carDeliveryAttrs[ 'carDeliveryId' ].name, '' ) !== '' ) {
				rewriteDestinationAddress( carrierRateId );
				var $estimatedDeliveryDateSection = $('.estimated-delivery-date');
				$estimatedDeliveryDateSection.removeClass('packetery-hidden');
				$estimatedDeliveryDateSection.find('.packetery-car-delivery-estimated-date').html(
					getRateAttrValue(carrierRateId, 'packetery_car_delivery_from', '')
					+ ' - ' +
					getRateAttrValue(carrierRateId, 'packetery_car_delivery_to', '')
				);
			}
		};

		var loadInfoForCarrierRate = function( carrierRateId, attrs ) {
			for ( var attributeKey in attrs ) {
				if ( !attrs.hasOwnProperty( attributeKey ) ) {
					continue;
				}

				var attribute = attrs[ attributeKey ].name;
				$( '#' + attribute ).val( getRateAttrValue( carrierRateId, attribute, '' ) );
			}
		};

		var clearInfo = function( attrs ) {
			for ( var carrierRateId in rateAttrValues ) {
				if ( !rateAttrValues.hasOwnProperty( carrierRateId ) ) {
					continue;
				}

				for ( var attributeKey in attrs ) {
					if ( !attrs.hasOwnProperty( attributeKey ) ) {
						continue;
					}

					var attribute = attrs[ attributeKey ].name;
					rateAttrValues[ carrierRateId ] = rateAttrValues[ carrierRateId ] || {};
					rateAttrValues[ carrierRateId ][ attribute ] = '';
					$( '#' + attribute ).val( '' );
				}
			}
			$widgetDiv.find( '.packeta-widget-info' ).html( '' );
		};

		var resetInfo = function( attrs ) {
			for ( var attributeKey in attrs ) {
				if ( !attrs.hasOwnProperty( attributeKey ) ) {
					continue;
				}

				var attribute = attrs[ attributeKey ].name;
				$( '#' + attribute ).val( '' );
			}

			$widgetDiv.find( '.packeta-widget-info' ).html( '' );
		};

		var getAddressValidation = function( carrierRateId ) {
			return settings.carrierConfig[ carrierRateId ][ 'address_validation' ];
		};

		var hasCarrierConfig = function( carrierRateId ) {
			return typeof settings.carrierConfig[ carrierRateId ] !== 'undefined';
		};

		var hasPickupPoints = function( carrierRateId ) {
			if ( !hasCarrierConfig( carrierRateId ) ) {
				return false;
			}

			return parseInt( settings.carrierConfig[ carrierRateId ][ 'is_pickup_points' ] ) === 1;
		};

		var hasHomeDelivery = function( carrierRateId ) {
			if ( !hasCarrierConfig( carrierRateId ) ) {
				return false;
			}

			return !hasPickupPoints( carrierRateId ) && !hasCarDelivery( carrierRateId);
		};

		var carDeliveryCarriers = [];
		settings.carDeliveryCarriers.forEach(function(carrier) {
			var prefixedCarrier = 'packetery_carrier_' + carrier;
			carDeliveryCarriers.push(prefixedCarrier);
		});

		var hasCarDelivery = function( carrierRateId ) {
			if ( !hasCarrierConfig( carrierRateId ) ) {
				return false;
			}

			return carDeliveryCarriers.includes( carrierRateId );
		};

		var fillHiddenField = function( carrierRateId, name, addressFieldValue ) {
			$( '#' + name ).val( addressFieldValue );
			rateAttrValues[ carrierRateId ] = rateAttrValues[ carrierRateId ] || {};
			rateAttrValues[ carrierRateId ][ name ] = addressFieldValue;
		};

		var updateWidgetButtonVisibility = function( carrierRateId, useAutoOpen ) {
			$widgetDiv = getPacketaWidget();
			$( '.packeta-widget' ).addClass( 'packetery-hidden' );
			$( '.estimated-delivery-date' ).addClass( 'packetery-hidden' );
			var $widgetButtonRow = $( '.packetery-widget-button-table-row' );
			$widgetButtonRow.addClass( 'packetery-hidden' );
			resetInfo( settings.pickupPointAttrs ); // clear active hidden field values
			resetInfo( settings.homeDeliveryAttrs );
			resetWidgetInfo();

			if ( !hasCarrierConfig( carrierRateId ) ) {
				return;
			}

			var _hasPickupPoints = hasPickupPoints( carrierRateId ),
				_hasHomeDelivery = hasHomeDelivery( carrierRateId ),
				_hasCarDelivery = hasCarDelivery( carrierRateId );

			if ( _hasPickupPoints ) {
				loadInfoForCarrierRate( carrierRateId, settings.pickupPointAttrs );
				$widgetDiv.find( '.packeta-widget-info' ).html( getRateAttrValue( carrierRateId, 'packetery_point_name', '' ) );
				$widgetDiv.find( 'button' ).html( settings.translations.choosePickupPoint );
				$widgetButtonRow.removeClass( 'packetery-hidden' );
				$widgetDiv.removeClass( 'packetery-hidden' );
			}

			if (
				useAutoOpen &&
				_hasPickupPoints &&
				settings.widgetAutoOpen &&
				$('iframe#packeta-widget').length === 0 &&
				!isPickupPointChosen($widgetDiv)
			) {
				$('.packeta-widget-button').click();
			}

			if ( _hasHomeDelivery && 'none' === getAddressValidation( carrierRateId ) ) {
				return;
			}

			if ( _hasHomeDelivery ) {
				loadInfoForCarrierRate( carrierRateId, settings.homeDeliveryAttrs );
				showHomeDeliveryAddress( carrierRateId );
				$widgetDiv.find( 'button' ).html( settings.translations.chooseAddress );
				$widgetButtonRow.removeClass( 'packetery-hidden' );
				$widgetDiv.removeClass( 'packetery-hidden' );
			}

			if ( _hasCarDelivery ) {
				loadInfoForCarrierRate( carrierRateId, settings.carDeliveryAttrs );
				showCarDeliveryAddress( carrierRateId );
				$widgetDiv.find( 'button' ).html( settings.translations.chooseAddress );
				$widgetButtonRow.removeClass( 'packetery-hidden' );
				$widgetDiv.removeClass( 'packetery-hidden' );
			}
		};

		var isPickupPointChosen = function ($widgetDiv) {
			var $pickupPointInfo = $widgetDiv.find('.packeta-widget-info');
			if ($pickupPointInfo.length === 0) {
				return false;
			}
			return $pickupPointInfo.html() !== '';
		}

		updateWidgetButtonVisibility( getShippingRateId(), true );

		var currentPaymentMethod = $('#payment input[type="radio"]:checked').val();
		var checkPaymentChange = function() {
			var targetPaymentMethod = $('#payment input[type="radio"]:checked').val();
			if ( currentPaymentMethod === targetPaymentMethod ) {
				return;
			}

			currentPaymentMethod = targetPaymentMethod;
			jQuery('body').trigger('update_checkout');
		};

		$( document ).on( 'change', '#payment input[type="radio"]', checkPaymentChange );

		var initialDestinationAddress = getDestinationAddress();
		var initialCarrierRateId = getShippingRateId();
		$( document ).on( 'updated_checkout', function () {
			$widgetDiv = getPacketaWidget();
			var destinationAddress = getDestinationAddress();
			var carrierRateId = getShippingRateId();
			if ( carrierRateId === null ) {
				carrierRateId = initialCarrierRateId;
			}

			var clearSavedData = false;
			if (
				hasPickupPoints( initialCarrierRateId ) ||
				hasHomeDelivery( initialCarrierRateId ) ||
				hasCarDelivery( initialCarrierRateId )
			) {
				if ( initialDestinationAddress.country !== destinationAddress.country ) {
					clearSavedData = true;
				}
			}

			if ( clearSavedData === true ) {
				clearInfo( settings.pickupPointAttrs );
				clearInfo( settings.homeDeliveryAttrs );
				clearInfo( settings.carDeliveryAttrs );

				postWithNonce(
					settings.removeSavedDataUrl,
					{},
					'Failed to remove saved selected pickup point or validated address data: '
				);
				resetWidgetInfo();
			}

			var clearSavedDataForCurrentCarrier = false;
			if ( (
					hasHomeDelivery( initialCarrierRateId ) ||
					hasCarDelivery( initialCarrierRateId )
				) && (
					initialDestinationAddress.street !== destinationAddress.street ||
					initialDestinationAddress.city !== destinationAddress.city ||
					initialDestinationAddress.postCode !== destinationAddress.postCode
				) &&
				initialCarrierRateId === carrierRateId
			) {
				clearSavedDataForCurrentCarrier = true;
			}
			if ( clearSavedDataForCurrentCarrier === true ) {
				if ( hasHomeDelivery( initialCarrierRateId ) ) {
					clearInfo( settings.homeDeliveryAttrs );
				}
				if ( hasCarDelivery( initialCarrierRateId ) ) {
					clearInfo( settings.carDeliveryAttrs );
				}

				postWithNonce(
					settings.removeSavedDataUrl,
					{
						'carrierId': initialCarrierRateId,
					},
					'Failed to remove saved selected pickup point or validated address data: '
				);
				resetWidgetInfo();
			}

			initialDestinationAddress = destinationAddress;
			initialCarrierRateId = carrierRateId;
			settings.country = destinationAddress.country;
			updateWidgetButtonVisibility( carrierRateId, true );
			checkPaymentChange(); // If Packeta shipping method with COD is selected, then switch to non-COD shipping method does not trigger payment method input change.
		} );

		$( document ).on( 'change', '#shipping_method input[type="radio"], #shipping_method input[type="hidden"]', function() {
			updateWidgetButtonVisibility( this.value, true );
		} );

		$( document ).on( 'ajaxStop', function() {
			updateWidgetButtonVisibility( getShippingRateId(), false );

			setTimeout( function() {
				updateWidgetButtonVisibility( getShippingRateId(), false );
			}, 1000 );
		} );

		var fillHiddenFields = function( carrierRateId, data, source ) {
			for ( var attrKey in data ) {
				if ( !data.hasOwnProperty( attrKey ) ) {
					continue;
				}

				if ( false === data[ attrKey ].isWidgetResultField ) {
					continue;
				}

				var widgetField = data[ attrKey ].widgetResultField || attrKey;
				var addressFieldValue = source[ widgetField ];

				fillHiddenField( carrierRateId, data[ attrKey ].name, addressFieldValue );
			}
		};

		var stringifyOptions = function (widgetOptions) {
			var widgetOptionsArray = [];
			for (const property in widgetOptions) {
				if (!widgetOptions.hasOwnProperty(property)) {
					continue;
				}
				var propertyValue;
				if (typeof widgetOptions[property] === 'object') {
					propertyValue = stringifyOptions(widgetOptions[property]);
				} else {
					propertyValue = widgetOptions[property];
				}
				widgetOptionsArray.push(property + ': ' + propertyValue);
			}
			return widgetOptionsArray.join(', ');
		};

		$( document ).on( 'click', '.packeta-widget-button', function( e ) {
			e.preventDefault();

			var widgetOptions = {
				country: settings.country,
				language: settings.language
			};

			var carrierRateId = getShippingRateId();
			if ( hasHomeDelivery( carrierRateId ) ) {
				widgetOptions.layout = 'hd';
				widgetOptions.appIdentity = settings.appIdentity;

				var destinationAddress = getDestinationAddress();
				widgetOptions.street = destinationAddress.street;
				widgetOptions.city = destinationAddress.city;
				widgetOptions.postcode = destinationAddress.postCode;
				widgetOptions.carrierId = settings.carrierConfig[ carrierRateId ][ 'id' ];

				console.log('Address widget options: apiKey: ' + settings.packeteryApiKey + ', ' + stringifyOptions(widgetOptions));
				Packeta.Widget.pick( settings.packeteryApiKey, function( result ) {
					resetWidgetInfo();
					showHomeDeliveryAddress( carrierRateId );

					if ( !result ) {
						resetWidgetInfoClasses();
						$widgetDiv.find( '.packeta-widget-info' ).addClass('packeta-widget-info-error').html( settings.translations.addressValidationIsOutOfOrder );
						return;
					}

					if ( !result.address ) {
						return; // Widget was closed.
					}

					var selectedAddress = result.address;

					if ( selectedAddress.country !== widgetOptions.country ) {
						resetWidgetInfoClasses();
						$widgetDiv.find( '.packeta-widget-info' ).addClass('packeta-widget-info-error').html( settings.translations.invalidAddressCountrySelected );
						return;
					}

					fillHiddenField( carrierRateId, settings.homeDeliveryAttrs[ 'isValidated' ].name, '1' );
					fillHiddenFields( carrierRateId, settings.homeDeliveryAttrs, selectedAddress );
					showHomeDeliveryAddress( carrierRateId );

					var addressDataToSave = rateAttrValues[ carrierRateId ];
					addressDataToSave.packetery_rate_id = carrierRateId;

					postWithNonce(
						settings.saveValidatedAddressUrl,
						addressDataToSave,
						'Failed to save validated address data: '
					);
				}, widgetOptions );
			}

			if ( hasCarDelivery( carrierRateId ) ) {
				widgetOptions.layout = 'cd';
				widgetOptions.appIdentity = settings.appIdentity;
				widgetOptions.expeditionDay = settings.expeditionDay;
				widgetOptions.sample = settings.isCarDeliverySampleEnabled === '1';

				console.log('Car delivery widget options: apiKey: ' + settings.packeteryApiKey + ', ' + stringifyOptions(widgetOptions));
				Packeta.Widget.pick( settings.packeteryApiKey, function( result ) {
					resetWidgetInfo();
					showCarDeliveryAddress( carrierRateId );

					if ( !result || !result.id ) {
						return; // Widget was closed.
					}

					var selectedAddress = result.location.address;
					var destinationAddress = getDestinationAddress();

					if ( selectedAddress.country !== destinationAddress.country ) {
						resetWidgetInfoClasses();
						$widgetDiv.find( '.packeta-widget-info' ).addClass('packeta-widget-info-error').html( settings.translations.invalidAddressCountrySelected );
						return;
					}

					fillHiddenFields( carrierRateId, settings.carDeliveryAttrs, selectedAddress );
					fillHiddenField( carrierRateId, settings.carDeliveryAttrs['carDeliveryId'].name, result.id );
					fillHiddenField( carrierRateId, settings.carDeliveryAttrs['expectedDeliveryDayFrom'].name, result.expectedDeliveryDayFrom );
					fillHiddenField( carrierRateId, settings.carDeliveryAttrs['expectedDeliveryDayTo'].name, result.expectedDeliveryDayTo );
					showCarDeliveryAddress( carrierRateId );

					var addressDataToSave = rateAttrValues[ carrierRateId ];
					addressDataToSave.packetery_rate_id = carrierRateId;

					postWithNonce(
						settings.saveCarDeliveryDetailsUrl,
						addressDataToSave,
						'Failed to save delivery address data: '
					);
				}, widgetOptions );
			}

			if ( hasPickupPoints( carrierRateId ) ) {
				widgetOptions.appIdentity = settings.appIdentity;
				widgetOptions.weight = settings.weight;
				if ( settings.carrierConfig[ carrierRateId ].carriers ) {
					widgetOptions.carriers = settings.carrierConfig[ carrierRateId ].carriers;
				}
				if ( settings.carrierConfig[ carrierRateId ].vendors ) {
					widgetOptions.vendors = settings.carrierConfig[ carrierRateId ].vendors;
				}

				if ( settings.isAgeVerificationRequired ) {
					widgetOptions.livePickupPoint = true; // Pickup points with real person only.
				}

				console.log('Pickup point widget options: apiKey: ' + settings.packeteryApiKey + ', ' + stringifyOptions(widgetOptions));
				Packeta.Widget.pick( settings.packeteryApiKey, function( pickupPoint ) {
					if ( pickupPoint == null ) {
						return;
					}
					resetWidgetInfo();

					fillHiddenFields( carrierRateId, settings.pickupPointAttrs, pickupPoint );
					$widgetDiv.find( '.packeta-widget-info' ).html( pickupPoint.name );

					var pickupPointDataToSave = rateAttrValues[ carrierRateId ];
					pickupPointDataToSave.packetery_rate_id = carrierRateId;

					postWithNonce(
						settings.saveSelectedPickupPointUrl,
						pickupPointDataToSave,
						'Failed to save pickup point data: '
					);
				}, widgetOptions );
			}
		} );
	};

	var dependencies = [];
	dependencies.push( $.getScript( "https://widget.packeta.com/v6/www/js/library.js" ) );

	dependencies.push(
		$.Deferred( function( deferred ) {
			$( deferred.resolve ); // wait for DOM to be loaded
		} )
	);

	$.when.apply( null, dependencies ).done( function() {
		packeteryCheckout( settings );
	} );
};

packeteryLoadCheckout( jQuery, packeteryCheckoutSettings );
