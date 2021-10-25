var packeteryLoadCheckout = function( $, settings ) {
	var packeteryCheckout = function( settings ) {
		var $widgetDiv = $( '.packeta-widget' );
		var currentRateValues = {};
		var ratesWithInfo = {};

		var getCurrentRateValue = function( carrierRateId, attribute, defaultValue ) {
			if ( typeof currentRateValues[ carrierRateId + '-' + attribute ] === 'undefined' ) {
				return defaultValue;
			}

			return currentRateValues[ carrierRateId + '-' + attribute ];
		}

		var getShippingRateId = function() {
			var $selectedRadio = $( '#shipping_method input[type="radio"]:checked' );
			if ( $selectedRadio.length ) {
				return $selectedRadio.val();
			}
			return $( '#shipping_method input[type="hidden"]' ).val();
		};

		var loadInfoForCarrierRate = function( carrierRateId, attrs ) {
			for ( var attributeKey in attrs ) {
				if ( !attrs.hasOwnProperty( attributeKey ) ) {
					continue;
				}

				var attribute = attrs[ attributeKey ].name;
				$( '#' + attribute ).val( getCurrentRateValue( carrierRateId, attribute, '' ) );
			}
		};

		var clearInfo = function( attrs ) {
			for ( var carrierRateId in ratesWithInfo ) {
				if ( !ratesWithInfo.hasOwnProperty( carrierRateId ) ) {
					continue;
				}

				for ( var attributeKey in attrs ) {
					if ( !attrs.hasOwnProperty( attributeKey ) ) {
						continue;
					}

					var attribute = attrs[ attributeKey ].name;
					currentRateValues[ carrierRateId + '-' + attribute ] = '';
					$( '#' + attribute ).val( '' );
				}
			}
			$widgetDiv.find( '.packeta-widget-info' ).html( '' );
		};

		var resetHDInfo = function() {
			for ( var attributeKey in settings.homeDeliveryAttrs ) {
				if ( !settings.homeDeliveryAttrs.hasOwnProperty( attributeKey ) ) {
					continue;
				}

				var attribute = settings.homeDeliveryAttrs[ attributeKey ].name;
				$( '#' + attribute ).val( '' );
			}

			$widgetDiv.find( '.packeta-widget-info' ).html( '' );
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
			resetHDInfo();

			if ( !hasCarrierConfig( carrierRateId ) ) {
				return;
			}

			var _hasPickupPoints = hasPickupPoints( carrierRateId ),
				_hasHomeDelivery = !_hasPickupPoints;

			if ( _hasPickupPoints ) {
				loadInfoForCarrierRate( carrierRateId, settings.pickupPointAttrs );
				$widgetDiv.find( '.packeta-widget-info' ).html( '' ).html( getCurrentRateValue( carrierRateId, 'packetery_point_name', '' ) );
				$widgetDiv.find( 'button' ).html( settings.translations.choosePickupPoint );
				$widgetDiv.show();
			}

			if ( _hasHomeDelivery ) {
				loadInfoForCarrierRate( carrierRateId, settings.homeDeliveryAttrs );
				$widgetDiv.find( '.packeta-widget-info' ).html( '' ).html( getCurrentRateValue( carrierRateId, 'packetery_address_street', '' ) );
				$widgetDiv.find( 'button' ).html( settings.translations.chooseAddress );
				$widgetDiv.show();
			}
		};

		updateWidgetButtonVisibility( getShippingRateId() );

		var getDestinationAddress = function() {
			var extractDestination = function( section ) {
				var address = {};

				address.street = $( '#' + section + '_address_1' ).val();
				address.city = $( '#' + section + '_city' ).val();
				address.country = $( '#' + section + '_country' ).val().toLowerCase();
				address.postCode = $( '#' + section + '_postcode' ).val();

				return address;
			};

			if ( $( '#shipping_country:visible' ).length === 1 ) {
				return extractDestination( 'shipping' );
			} else {
				return extractDestination( 'billing' );
			}
		};

		$( document ).on( 'updated_checkout', function() {
			var shippingCountry;
			if ( $( '#shipping_country:visible' ).length === 1 ) {
				shippingCountry = $( '#shipping_country' ).val().toLowerCase();
			} else {
				shippingCountry = $( '#billing_country' ).val().toLowerCase();
			}
			if ( shippingCountry !== settings.country ) {
				clearInfo( settings.pickupPointAttrs );
				clearInfo( settings.homeDeliveryAttrs );
				settings.country = shippingCountry;
			}
			updateWidgetButtonVisibility( getShippingRateId() );
		} );

		$( document ).on( 'change', '#shipping_method input[type="radio"], #shipping_method input[type="hidden"]', function() {
			updateWidgetButtonVisibility( this.value );
		} );

		var fillHiddenField = function( carrierRateId, name, addressFieldValue ) {
			$( '#' + name ).val( addressFieldValue );
			currentRateValues[ carrierRateId + '-' + name ] = addressFieldValue;
			ratesWithInfo[ carrierRateId ] = true;
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

				var destinationAddress = getDestinationAddress();
				widgetOptions.street = destinationAddress.street;
				widgetOptions.city = destinationAddress.city;
				widgetOptions.postcode = destinationAddress.postCode;
				widgetOptions.carrierId = settings.carrierConfig[ carrierRateId ][ 'id' ];

				PacketaHD.Widget.pick( settings.packeteryApiKey, function( result ) {
					if ( !result || !result.address ) {
						$widgetDiv.find( '.packeta-widget-info' ).html( settings.translations.addressValidationIsOutOfOrder );
						return;
					}

					var selectedAddress = result.address;

					if ( selectedAddress.country !== widgetOptions.country ) {
						$widgetDiv.find( '.packeta-widget-info' ).html( settings.translations.invalidAddressCountrySelected );
						return;
					}

					// todo save selected address to shipping address

					fillHiddenField( carrierRateId, settings.homeDeliveryAttrs[ 'active' ].name, '1' );
					fillHiddenFields( getShippingRateId(), settings.homeDeliveryAttrs, selectedAddress );
					$widgetDiv.find( '.packeta-widget-info' ).html( settings.translations.addressSaved );
				}, widgetOptions );
			}

			if ( hasPickupPoints( carrierRateId ) ) {
				widgetOptions.appIdentity = settings.appIdentity;
				widgetOptions.weight = settings.weight;
				widgetOptions.carriers = settings.carrierConfig[ carrierRateId ].carriers;

				Packeta.Widget.pick( settings.packeteryApiKey, function( pickupPoint ) {
					if ( pickupPoint == null ) {
						return;
					}

					fillHiddenFields( carrierRateId, settings.pickupPointAttrs, pickupPoint );
					$widgetDiv.find( '.packeta-widget-info' ).html( pickupPoint.name );
				}, widgetOptions );
			}
		} );

		$( document ).on( 'change', '#payment input[type="radio"]', function() {
			$( 'body' ).trigger( 'update_checkout' );
		} );
	};

	var dependencies = [];
	dependencies.push( $.getScript( "https://widget.packeta.com/v6/www/js/library.js" ) );
	dependencies.push( $.getScript( "https://widget-hd.packeta.com/www/js/library-hd.js" ) );

	dependencies.push(
		$.Deferred( function( deferred ) {
			$( deferred.resolve ); // wait for DOM to be loaded
		} )
	);

	$.when.apply( null, dependencies ).done( function() {
		packeteryCheckout( settings );
	} );
};
