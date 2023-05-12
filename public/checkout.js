var packeteryLoadCheckout = function( $, settings ) {
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

		var showDeliveryAddress = function(carrierRateId) {
			resetWidgetInfoClasses();
			if (getRateAttrValue( carrierRateId, settings.homeDeliveryAttrs[ 'isValidated' ].name, '0' ) === '1') {
				$widgetDiv.find( '.packeta-widget-selected-address' ).html(
					getRateAttrValue( carrierRateId, 'packetery_address_street', '' )
					+ ' ' +
					getRateAttrValue( carrierRateId, 'packetery_address_houseNumber', '' )
					+ ', ' +
					getRateAttrValue( carrierRateId, 'packetery_address_city', '' )
					+ ', ' +
					getRateAttrValue( carrierRateId, 'packetery_address_postCode', '' )
				);
				$widgetDiv.find( '.packeta-widget-info' ).addClass('packeta-widget-info-success').html(settings.translations.addressIsValidated);
			} else if ( 'required' === getAddressValidation( carrierRateId ) ) {
				$widgetDiv.find( '.packeta-widget-info' ).addClass( 'packeta-widget-info-error' ).html( settings.translations.addressIsNotValidatedAndRequiredByCarrier );
			} else {
				$widgetDiv.find( '.packeta-widget-info' ).addClass( 'packeta-widget-info-error' ).html( settings.translations.addressIsNotValidated );
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

			return !hasPickupPoints( carrierRateId );
		};

		var fillHiddenField = function( carrierRateId, name, addressFieldValue ) {
			$( '#' + name ).val( addressFieldValue );
			rateAttrValues[ carrierRateId ] = rateAttrValues[ carrierRateId ] || {};
			rateAttrValues[ carrierRateId ][ name ] = addressFieldValue;
		};

		var updateWidgetButtonVisibility = function( carrierRateId, useAutoOpen ) {
			$widgetDiv = getPacketaWidget();
			$( '.packeta-widget' ).addClass( 'packetery-hidden' );
			var $widgetButtonRow = $( '.packetery-widget-button-table-row' );
			$widgetButtonRow.addClass( 'packetery-hidden' );
			resetInfo( settings.pickupPointAttrs ); // clear active hidden field values
			resetInfo( settings.homeDeliveryAttrs );
			resetWidgetInfo();

			if ( !hasCarrierConfig( carrierRateId ) ) {
				return;
			}

			var _hasPickupPoints = hasPickupPoints( carrierRateId ),
				_hasHomeDelivery = !_hasPickupPoints;

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
				showDeliveryAddress( carrierRateId );
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
			if ( hasPickupPoints( initialCarrierRateId ) ) {
				if ( initialDestinationAddress.country !== destinationAddress.country ) {
					clearSavedData = true;
				}
			} else if ( hasHomeDelivery( initialCarrierRateId ) ) {
				if (
					initialDestinationAddress.country !== destinationAddress.country ||
					initialDestinationAddress.street !== destinationAddress.street ||
					initialDestinationAddress.city !== destinationAddress.city ||
					initialDestinationAddress.postCode !== destinationAddress.postCode
				) {
					clearSavedData = true;
				}
			}
			if ( clearSavedData === true ) {
				clearInfo( settings.pickupPointAttrs );
				clearInfo( settings.homeDeliveryAttrs );

				$.post(
					settings.removeSavedDataUrl, {}
				).fail( function ( xhr, status, error ) {
					console.log( 'Packeta: Failed to remove saved selected pickup point or validated address data: ' + error );
				} );

				resetWidgetInfo();

				initialDestinationAddress = destinationAddress;
				initialCarrierRateId = carrierRateId;
				settings.country = destinationAddress.country;
			}

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

		var fillHiddenFields = function( carrierRateId, data, target ) {
			for ( var attrKey in data ) {
				if ( !data.hasOwnProperty( attrKey ) ) {
					continue;
				}

				if ( false === data[ attrKey ].isWidgetResultField ) {
					continue;
				}

				var widgetField = data[ attrKey ].widgetResultField || attrKey;
				var addressFieldValue = target[ widgetField ];

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
					showDeliveryAddress( carrierRateId );

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

					// todo save selected address to shipping address

					fillHiddenField( carrierRateId, settings.homeDeliveryAttrs[ 'isValidated' ].name, '1' );
					fillHiddenFields( carrierRateId, settings.homeDeliveryAttrs, selectedAddress );
					showDeliveryAddress( carrierRateId );

					var addressDataToSave = rateAttrValues[ carrierRateId ];
					addressDataToSave.packetery_rate_id = carrierRateId;
					$.post(
						settings.saveValidatedAddressUrl,
						addressDataToSave
					).fail( function ( xhr, status, error ) {
						console.log( 'Packeta: Failed to save validated address data: ' + error );
					} );
				}, widgetOptions );
			}

			if ( hasPickupPoints( carrierRateId ) ) {
				widgetOptions.appIdentity = settings.appIdentity;
				widgetOptions.weight = settings.weight;
				widgetOptions.defaultPrice = settings.carrierConfig[ carrierRateId ].defaultPrice;
				widgetOptions.defaultCurrency = settings.carrierConfig[ carrierRateId ].defaultCurrency;
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
					$.post(
						settings.saveSelectedPickupPointUrl,
						pickupPointDataToSave
					).fail( function ( xhr, status, error ) {
						console.log( 'Packeta: Failed to save pickup point data: ' + error );
					} );
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
