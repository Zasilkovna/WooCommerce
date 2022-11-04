( function( $, settings ) {

	var init = function( settings ) {
		var $widgetDiv = $( '[data-packetery-order-metabox]' );
		if ( 0 === $widgetDiv.length ) {
			return;
		}

		$widgetDiv.on( 'click', '[name=packetery_pick_address]', function( e ) {
			e.preventDefault();

			PacketaHD.Widget.pick( settings.packeteryApiKey, function( result ) {
				if ( !result ) {
					$widgetDiv.find( '.packeta-widget-info' ).html( settings.translations.addressValidationIsOutOfOrder );
					return;
				}

				if ( !result.address ) {
					return; // Widget was closed.
				}

				var selectedAddress = result.address;

				if ( selectedAddress.country !== settings.widgetOptions.country ) {
					$widgetDiv.find( '.packeta-widget-info' ).html( settings.translations.invalidAddressCountrySelected );
					return;
				}

				for ( var attrKey in settings.homeDeliveryAttrs ) {
					if ( false === settings.homeDeliveryAttrs.hasOwnProperty( attrKey ) ) {
						continue;
					}

					if ( false === settings.homeDeliveryAttrs[ attrKey ].isWidgetResultField ) {
						continue;
					}

					var attr = settings.homeDeliveryAttrs[ attrKey ];
					var widgetField = attr.widgetResultField || attrKey;

					$widgetDiv.find( '[name=' + attr.name + ']' ).val( selectedAddress[ widgetField ] || '' );
				}

				$widgetDiv.find( '[name=packetery_address_isValidated]' ).val( '1' );
				$widgetDiv.find( '[data-packetery-widget-info]' ).html(
					$widgetDiv.find( '[name=packetery_address_street]' ).val() + ' ' +
					$widgetDiv.find( '[name=packetery_address_houseNumber]' ).val() + ', ' +
					$widgetDiv.find( '[name=packetery_address_city]' ).val() + ', ' +
					$widgetDiv.find( '[name=packetery_address_postCode]' ).val()
				);
			}, settings.widgetOptions );
		} );
	};

	var dependencies = [];
	dependencies.push( $.getScript( "https://hd.widget.packeta.com/www/js/library-hd.js" ) );

	dependencies.push(
		$.Deferred( function( deferred ) {
			$( deferred.resolve ); // wait for DOM to be loaded
		} )
	);

	$.when.apply( null, dependencies ).done( function() {
		init( settings );
	} );

} )( jQuery, packeteryAddressPickerSettings );
