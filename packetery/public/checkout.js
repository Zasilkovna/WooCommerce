var packeteryLoadCheckout = function( $, settings ) {
	var packeteryCheckout = function( settings ) {
		var $widgetDiv = $( '.packeta-widget' );
		var rateAttrValues = {};

		var getRateAttrValue = function( carrierRateId, attribute, defaultValue ) {
			if ( typeof rateAttrValues[ carrierRateId ][ attribute ] === 'undefined' ) {
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
				$widgetDiv.find( '.packeta-widget-info' ).html( getRateAttrValue( carrierRateId, 'packetery_address_street', '' ) );
				$widgetDiv.find( 'button' ).html( settings.translations.chooseAddress );
				$widgetDiv.show();
			}
		};

		updateWidgetButtonVisibility( getShippingRateId() );

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

		$( document ).on( 'updated_checkout', function() {
			var destinationAddress = getDestinationAddress();
			if ( destinationAddress.country !== settings.country ) {
				clearInfo( settings.pickupPointAttrs );
				clearInfo( settings.homeDeliveryAttrs );
				settings.country = destinationAddress.country;
			}

			updateWidgetButtonVisibility( getShippingRateId() );
		} );

		$( document ).on( 'change', '#shipping_method input[type="radio"], #shipping_method input[type="hidden"]', function() {
			updateWidgetButtonVisibility( this.value );
		} );

		var fillHiddenField = function( carrierRateId, name, addressFieldValue ) {
			$( '#' + name ).val( addressFieldValue );
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

				var destinationAddress = getDestinationAddress();
				widgetOptions.street = destinationAddress.street;
				widgetOptions.city = destinationAddress.city;
				widgetOptions.postcode = destinationAddress.postCode;
				widgetOptions.carrierId = settings.carrierConfig[ carrierRateId ][ 'id' ];

				PacketaHD.Widget.pick( settings.packeteryApiKey, function( result ) {
					if ( !result ) {
						$widgetDiv.find( '.packeta-widget-info' ).html( settings.translations.addressValidationIsOutOfOrder );
						return;
					}

					if ( !result.address ) {
						return; // Widget was closed.
					}

					var selectedAddress = result.address;

					if ( selectedAddress.country !== widgetOptions.country ) {
						$widgetDiv.find( '.packeta-widget-info' ).html( settings.translations.invalidAddressCountrySelected );
						return;
					}

					// todo save selected address to shipping address

					fillHiddenField( carrierRateId, settings.homeDeliveryAttrs[ 'isAddressValidated' ].name, '1' );
					fillHiddenFields( carrierRateId, settings.homeDeliveryAttrs, selectedAddress );
					$widgetDiv.find( '.packeta-widget-info' ).html( getRateAttrValue( carrierRateId, 'packetery_address_street', '' ) );
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
