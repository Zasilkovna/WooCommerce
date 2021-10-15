var packeteryLoadCheckout = function( settings ) {
	var packeteryCheckout = function( settings ) {
		var $ = settings.jQuery,
			pickupPointAttsReformatted = settings.pickupPointAttsReformatted,
			pickupPointAttrs = settings.pickupPointAttrs,
			homeDeliveryAttrs = settings.homeDeliveryAttrs,
			carrierConfig = settings.carrierConfig,
			packeteryApiKey = settings.packeteryApiKey,
			translations = settings.translations,
			appIdentity = settings.appIdentity
		;

		var $widgetDiv = $( '.packeta-widget' );

		var getShippingRateId = function() {
			var $radios = $( '#shipping_method input[type="radio"]' );
			if ( $radios.length ) {
				return $radios.filter( ':checked' ).val();
			}
			return $( '#shipping_method input[type="hidden"]' ).val();
		};

		var homeDeliveryAttributes = homeDeliveryAttrs;

		var ratesWithInfo = [];
		var saveInfoForCarrierRate = function( carrierRateId ) {
			for ( var attribute in pickupPointAttsReformatted ) {
				$widgetDiv.data( carrierRateId + '-' + attribute, $( '#' + attribute ).val() );
			}
			ratesWithInfo.push( carrierRateId );
		};

		var hdRatesWithInfo = [];
		var saveInfoForHDCarrierRate = function( carrierRateId ) {
			for ( var attributeKey in homeDeliveryAttributes ) {
				if ( !homeDeliveryAttributes.hasOwnProperty( attributeKey ) ) {
					continue;
				}

				var attribute = homeDeliveryAttributes[ attributeKey ].name;
				$widgetDiv.data( carrierRateId + '-' + attribute, $( '#' + attribute ).val() );
			}
			hdRatesWithInfo.push( carrierRateId );
		};

		var loadInfoForCarrierRate = function( carrierRateId ) {
			for ( var attribute in pickupPointAttsReformatted ) {
				$( '#' + attribute ).val( $widgetDiv.data( carrierRateId + '-' + attribute ) );
			}
			$widgetDiv.find( '.packeta-widget-info' ).html( '' ).html( $widgetDiv.data( carrierRateId + '-packetery_point_name' ) );
		};

		var loadInfoForHDCarrierRate = function( carrierRateId ) {
			for ( var attributeKey in homeDeliveryAttributes ) {
				if ( !homeDeliveryAttributes.hasOwnProperty( attributeKey ) ) {
					continue;
				}

				var attribute = homeDeliveryAttributes[ attributeKey ].name;
				$( '#' + attribute ).val( $widgetDiv.data( carrierRateId + '-' + attribute ) );
			}
			$widgetDiv.find( '.packeta-widget-info' ).html( '' ).html( $widgetDiv.data( carrierRateId + '-packetery_address_street' ) );
		};

		var clearPickupPointInfo = function() {
			for ( var carrierRateId of ratesWithInfo ) {
				for ( var attribute in pickupPointAttsReformatted ) {
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
				for ( var attributeKey in homeDeliveryAttributes ) {
					if ( !homeDeliveryAttributes.hasOwnProperty( attributeKey ) ) {
						continue;
					}

					var attribute = homeDeliveryAttributes[ attributeKey ].name;
					$widgetDiv.data( carrierRateId + '-' + attribute, '' );
					$( '#' + attribute ).val( '' );
				}
			}

			$widgetDiv.find( '.packeta-widget-info' ).html( '' );
		};

		var resetHDInfo = function() {
			for ( var attributeKey in homeDeliveryAttributes ) {
				if ( !homeDeliveryAttributes.hasOwnProperty( attributeKey ) ) {
					continue;
				}

				var attribute = homeDeliveryAttributes[ attributeKey ].name;
				$( '#' + attribute ).val( '' );
			}

			$widgetDiv.find( '.packeta-widget-info' ).html( '' );
		};

		var hasPickupPoints = function( carrierRateId ) {
			return parseInt( carrierConfig[ carrierRateId ][ 'is_pickup_points' ] ) === 1;
		};

		var hasHomeDelivery = function( carrierRateId ) {
			return !hasPickupPoints( carrierRateId );
		};

		var updateWidgetButtonVisibility = function( carrierRateId ) {
			$widgetDiv.hide();
			resetHDInfo();

			if ( typeof carrierConfig[ carrierRateId ] === 'undefined' ) {
				return;
			}

			var _hasPickupPoints = hasPickupPoints( carrierRateId ),
				_hasHomeDelivery = !_hasPickupPoints;

			$widgetDiv.data( 'carriers', carrierConfig[ carrierRateId ][ 'carriers' ] );

			if ( _hasPickupPoints ) {
				loadInfoForCarrierRate( carrierRateId );
				$widgetDiv.find( 'button' ).html( translations.choosePickupPoint );
				$widgetDiv.show();
			}

			if ( _hasHomeDelivery ) {
				loadInfoForHDCarrierRate( carrierRateId );
				$widgetDiv.find( 'button' ).html( translations.chooseAddress );
				$widgetDiv.show();
			}
		};

		updateWidgetButtonVisibility( getShippingRateId() );

		var getDestinationAddress = function() {
			var extractDestination = function( section ) {
				var address = {};

				address.street = $( '#' + section + '_address_1' ).val();
				address.street = address.street + ' ' + $( '#' + section + '_address_2' ).val();
				address.houseNumber = '';
				address.city = $( '#' + section + '_city' ).val();
				address.country = $( '#' + section + '_country' ).val().toLowerCase();
				address.postCode = $( '#' + section + '_postcode' ).val();
				address.longitude = '';
				address.latitude = '';
				address.county = '';

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

		$( '.packeta-widget-button' ).click( function( e ) {
			e.preventDefault();

			var widgetOptions = {
				appIdentity: appIdentity,
				country: $widgetDiv.data( 'country' ),
				language: $widgetDiv.data( 'language' )
			};

			var carrierRateId = getShippingRateId();
			if ( hasHomeDelivery( carrierRateId ) ) {
				widgetOptions.layout = 'hd';

				var destinationAddress = getDestinationAddress();
				widgetOptions.houseNumber = destinationAddress.houseNumber;
				widgetOptions.street = destinationAddress.street;
				widgetOptions.city = destinationAddress.city;
				widgetOptions.postcode = destinationAddress.postCode;
				widgetOptions.carrierId = carrierConfig[ carrierRateId ][ 'id' ];

				Packeta.WidgetHD.pick( packeteryApiKey, function( result ) {
					if ( !result || !result.address ) {
						$widgetDiv.find( '.packeta-widget-info' ).html( translations.addressValidationIsOutOfOrder );
						return;
					}

					var selectedAddress = result.address;

					// todo save selected address to shipping address

					// show selected address
					$widgetDiv.find( '.packeta-widget-info' ).html( translations.addressSaved );

					for ( var homeDeliveryAttrKey in homeDeliveryAttributes ) {
						if ( !homeDeliveryAttributes.hasOwnProperty( homeDeliveryAttrKey ) ) {
							continue;
						}

						var addressFieldValue, $hiddenPacketeryFormField;
						var widgetField = homeDeliveryAttributes[ homeDeliveryAttrKey ].widgetResultField || homeDeliveryAttrKey;

						addressFieldValue = selectedAddress[ widgetField ];
						$hiddenPacketeryFormField = $( '#' + homeDeliveryAttributes[ homeDeliveryAttrKey ].name );

						$hiddenPacketeryFormField.val( addressFieldValue );
					}

					saveInfoForHDCarrierRate( getShippingRateId() );
				}, widgetOptions );
			}

			if ( hasPickupPoints( carrierRateId ) ) {
				widgetOptions.weight = $widgetDiv.data( 'weight' );
				widgetOptions.carriers = $widgetDiv.data( 'carriers' );

				Packeta.Widget.pick( packeteryApiKey, function( pickupPoint ) {
					if ( pickupPoint != null ) {

						// show selected pickup point
						$widgetDiv.find( '.packeta-widget-info' ).html( pickupPoint.name );

						// fill hidden inputs
						for ( var pickupPointAttrKey in pickupPointAttrs ) {
							if ( !pickupPointAttrs.hasOwnProperty( pickupPointAttrKey ) ) {
								continue;
							}

							var addressFieldValue, $hiddenPacketeryFormField;
							var widgetField = pickupPointAttrs[ pickupPointAttrKey ].widgetResultField || pickupPointAttrKey;

							addressFieldValue = pickupPoint[ widgetField ];
							$hiddenPacketeryFormField = $( '#' + pickupPointAttrs[ pickupPointAttrKey ].name );

							$hiddenPacketeryFormField.val( addressFieldValue );
						}

						saveInfoForCarrierRate( getShippingRateId() );
					}
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

	dependencies.push(
		$.Deferred( function( deferred ) {
			$( deferred.resolve ); // wait for DOM to be loaded
		} )
	);

	$.when.apply( null, dependencies ).done( function() {
		packeteryCheckout( settings );
	} );
};
