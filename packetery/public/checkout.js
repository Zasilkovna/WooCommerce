var packeteryLoadCheckout = function( settings ) {
	var packeteryCheckout = function( settings ) {
		var $ = settings.jQuery;

		var $widgetDiv = $( '.packeta-widget' );

		var getShippingRateId = function() {
			var $radios = $( '#shipping_method input[type="radio"]' );
			if ( $radios.length ) {
				return $radios.filter( ':checked' ).val();
			}
			return $( '#shipping_method input[type="hidden"]' ).val();
		};

		var ratesWithInfo = [];
		var saveInfoForCarrierRate = function( carrierRateId ) {
			for ( var attribute in settings.pickupPointAttsReformatted ) {
				$widgetDiv.data( carrierRateId + '-' + attribute, $( '#' + attribute ).val() );
			}
			ratesWithInfo.push( carrierRateId );
		};

		var hdRatesWithInfo = [];
		var saveInfoForHDCarrierRate = function( carrierRateId ) {
			for ( var attributeKey in settings.homeDeliveryAttrs ) {
				if ( !settings.homeDeliveryAttrs.hasOwnProperty( attributeKey ) ) {
					continue;
				}

				var attribute = settings.homeDeliveryAttrs[ attributeKey ].name;
				$widgetDiv.data( carrierRateId + '-' + attribute, $( '#' + attribute ).val() );
			}
			hdRatesWithInfo.push( carrierRateId );
		};

		var loadInfoForCarrierRate = function( carrierRateId ) {
			for ( var attribute in settings.pickupPointAttsReformatted ) {
				$( '#' + attribute ).val( $widgetDiv.data( carrierRateId + '-' + attribute ) );
			}
			$widgetDiv.find( '.packeta-widget-info' ).html( '' ).html( $widgetDiv.data( carrierRateId + '-packetery_point_name' ) );
		};

		var loadInfoForHDCarrierRate = function( carrierRateId ) {
			for ( var attributeKey in settings.homeDeliveryAttrs ) {
				if ( !settings.homeDeliveryAttrs.hasOwnProperty( attributeKey ) ) {
					continue;
				}

				var attribute = settings.homeDeliveryAttrs[ attributeKey ].name;
				$( '#' + attribute ).val( $widgetDiv.data( carrierRateId + '-' + attribute ) );
			}
			$widgetDiv.find( '.packeta-widget-info' ).html( '' ).html( $widgetDiv.data( carrierRateId + '-packetery_address_street' ) );
		};

		var clearPickupPointInfo = function() {
			for ( var carrierRateId of ratesWithInfo ) {
				for ( var attribute in settings.pickupPointAttsReformatted ) {
					$widgetDiv.data( carrierRateId + '-' + attribute, '' );
					$( '#' + attribute ).val( '' );
				}
			}
			$widgetDiv.find( '.packeta-widget-info' ).html( '' );
		};

		var clearHDInfo = function() {
			for ( var carrierRateIdKey in hdRatesWithInfo ) {
				if ( !hdRatesWithInfo.hasOwnProperty( carrierRateIdKey ) ) {
					continue;
				}

				var carrierRateId = hdRatesWithInfo[ carrierRateIdKey ];
				for ( var attributeKey in settings.homeDeliveryAttrs ) {
					if ( !settings.homeDeliveryAttrs.hasOwnProperty( attributeKey ) ) {
						continue;
					}

					var attribute = settings.homeDeliveryAttrs[ attributeKey ].name;
					$widgetDiv.data( carrierRateId + '-' + attribute, '' );
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

			$widgetDiv.data( 'carriers', settings.carrierConfig[ carrierRateId ][ 'carriers' ] );

			if ( _hasPickupPoints ) {
				loadInfoForCarrierRate( carrierRateId );
				$widgetDiv.find( 'button' ).html( settings.translations.choosePickupPoint );
				$widgetDiv.show();
			}

			if ( _hasHomeDelivery ) {
				loadInfoForHDCarrierRate( carrierRateId );
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
			if ( shippingCountry !== $widgetDiv.data( 'country' ) ) {
				clearPickupPointInfo();
				clearHDInfo();
				$widgetDiv.data( 'country', shippingCountry );
			}
			updateWidgetButtonVisibility( getShippingRateId() );
		} );

		var shippingMethodSelector = '#shipping_method input[type="radio"], #shipping_method input[type="hidden"]';
		$( document ).on( 'change', shippingMethodSelector, function() {
			updateWidgetButtonVisibility( this.value );
		} );

		var fillHiddenFields = function( data, target ) {
			for ( var attrKey in data ) {
				if ( !data.hasOwnProperty( attrKey ) ) {
					continue;
				}

				var addressFieldValue, $hiddenPacketeryFormField;
				var widgetField = data[ attrKey ].widgetResultField || attrKey;

				addressFieldValue = target[ widgetField ];
				$hiddenPacketeryFormField = $( '#' + data[ attrKey ].name );

				$hiddenPacketeryFormField.val( addressFieldValue );
			}
		};

		$( '.packeta-widget-button' ).click( function( e ) {
			e.preventDefault();

			var widgetOptions = {
				country: $widgetDiv.data( 'country' ),
				language: $widgetDiv.data( 'language' )
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

					fillHiddenFields( settings.homeDeliveryAttrs, selectedAddress );
					saveInfoForHDCarrierRate( getShippingRateId() );
					$widgetDiv.find( '.packeta-widget-info' ).html( settings.translations.addressSaved );
				}, widgetOptions );
			}

			if ( hasPickupPoints( carrierRateId ) ) {
				widgetOptions.appIdentity = settings.appIdentity;
				widgetOptions.weight = $widgetDiv.data( 'weight' );
				widgetOptions.carriers = $widgetDiv.data( 'carriers' );

				Packeta.Widget.pick( settings.packeteryApiKey, function( pickupPoint ) {
					if ( pickupPoint == null ) {
						return;
					}

					fillHiddenFields( settings.pickupPointAttrs, pickupPoint );
					saveInfoForCarrierRate( getShippingRateId() );
					$widgetDiv.find( '.packeta-widget-info' ).html( pickupPoint.name );
				}, widgetOptions );
			}
		} );

		$( document ).on( 'change', '#payment input[type="radio"]', function() {
			$( 'body' ).trigger( 'update_checkout' );
		} );
	};

	var $ = settings.jQuery;
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
