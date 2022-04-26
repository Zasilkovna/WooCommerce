var packeteryLoadCheckout = function( $, settings ) {
	var packeteryCheckout = function( settings ) {
		var $widgetDiv = $( '.packeta-widget' );
		var rateAttrValues = {};

		var getDestinationAddress = function() {
			var extractDestination = function( section ) {
				return {
					street: $( '#' + section + '_address_1' ).val(),
					city: $( '#' + section + '_city' ).val(),
					country: $( '#' + section + '_country' ).val().toLowerCase(),
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
		}

		var getShippingRateId = function() {
			var $selectedRadio = $( '#shipping_method input[type="radio"]:checked' );
			if ( $selectedRadio.length ) {
				return $selectedRadio.val();
			}
			return $( '#shipping_method input[type="hidden"]' ).val();
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

		var updateWidgetButtonVisibility = function( carrierRateId ) {
			$widgetDiv.hide();
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
				$widgetDiv.show();
			}

			if ( _hasHomeDelivery && 'none' === getAddressValidation( carrierRateId ) ) {
				return;
			}

			if ( _hasHomeDelivery ) {
				loadInfoForCarrierRate( carrierRateId, settings.homeDeliveryAttrs );
				showDeliveryAddress( carrierRateId );
				$widgetDiv.find( 'button' ).html( settings.translations.chooseAddress );
				$widgetDiv.show();
			}
		};

		updateWidgetButtonVisibility( getShippingRateId() );

		var currentPaymentMethod = $('#payment input[type="radio"]:checked').val();
		var checkPaymentChange = function() {
			var targetPaymentMethod = $('#payment input[type="radio"]:checked').val();
			if ( currentPaymentMethod === targetPaymentMethod ) {
				return;
			}

			currentPaymentMethod = targetPaymentMethod;
			jQuery('body').trigger('update_checkout');
		};

		$(document).on('change', '#payment input[type="radio"]', checkPaymentChange);
		$( document ).on( 'updated_checkout', function() {
			var destinationAddress = getDestinationAddress();
			if ( destinationAddress.country !== settings.country ) {
				clearInfo( settings.pickupPointAttrs );
				clearInfo( settings.homeDeliveryAttrs );
				resetWidgetInfo();
				settings.country = destinationAddress.country;
			}

			updateWidgetButtonVisibility( getShippingRateId() );
			checkPaymentChange(); // If Packeta shipping method with COD is selected, then switch to non-COD shipping method does not trigger payment method input change.
		} );

		$( document ).on( 'change', '#shipping_method input[type="radio"], #shipping_method input[type="hidden"]', function() {
			updateWidgetButtonVisibility( this.value );
		} );

		var fillHiddenField = function( carrierRateId, name, addressFieldValue ) {
			$( '#' + name ).val( addressFieldValue );
			rateAttrValues[ carrierRateId ] = rateAttrValues[ carrierRateId ] || {};
			rateAttrValues[ carrierRateId ][ name ] = addressFieldValue;
		};

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

		$( '.packeta-widget-button' ).click( function( e ) {
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

				PacketaHD.Widget.pick( settings.packeteryApiKey, function( result ) {
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
				}, widgetOptions );
			}

			if ( hasPickupPoints( carrierRateId ) ) {
				widgetOptions.appIdentity = settings.appIdentity;
				widgetOptions.weight = settings.weight;
				widgetOptions.carriers = settings.carrierConfig[ carrierRateId ].carriers;

				if ( settings.isAgeVerificationRequired ) {
					widgetOptions.livePickupPoint = true; // Pickup points with real person only.
				}

				Packeta.Widget.pick( settings.packeteryApiKey, function( pickupPoint ) {
					if ( pickupPoint == null ) {
						return;
					}
					resetWidgetInfo();

					fillHiddenFields( carrierRateId, settings.pickupPointAttrs, pickupPoint );
					$widgetDiv.find( '.packeta-widget-info' ).html( pickupPoint.name );
				}, widgetOptions );
			}
		} );
	};

	var dependencies = [];
	dependencies.push( $.getScript( "https://widget.packeta.com/v6/www/js/library.js" ) );
	dependencies.push( $.getScript( "https://hd.widget.packeta.com/www/js/library-hd.js" ) );

	dependencies.push(
		$.Deferred( function( deferred ) {
			$( deferred.resolve ); // wait for DOM to be loaded
		} )
	);

	$.when.apply( null, dependencies ).done( function() {
		packeteryCheckout( settings );
	} );
};

if ( typeof packeteryCheckoutSettings !== 'undefined' ) {
	packeteryLoadCheckout( jQuery, packeteryCheckoutSettings );
}
