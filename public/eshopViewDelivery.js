var packeteryChangeDeliveryAddressDetails = function( $, settings ) {

	var $infoDiv = $( '.packeta-widget-info' );
	var postWithNonce = function ( url, data, errorMessage ) {
		$.ajax( {
			type: 'POST',
			url: url,
			data: data,
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', settings.nonce );
			},
		} )
		.done( function ( responseData ) {
			var message = responseData.message;
			var responseType = responseData.type;

			$infoDiv.text( message );
			if ( responseType === 'success' ) {
				$infoDiv.addClass( 'success' ).removeClass( 'error' );
				$infoDiv.css( 'color', 'green' );
			} else if ( responseType === 'error' ) {
				$infoDiv.addClass( 'error' ).removeClass( 'success' );
				$infoDiv.css( 'color', 'rgb( 186,27,1 )' );
			}

			$infoDiv.removeAttr( 'hidden' );
		} )
		.fail( function ( xhr, status, error ) {
			console.log( 'Packeta: ' + errorMessage + error );
		} );
	}
	var stringifyOptions = function ( widgetOptions ) {
		var widgetOptionsArray = [];
		for ( const property in widgetOptions ) {
			if ( !widgetOptions.hasOwnProperty( property ) ) {
				continue;
			}
			var propertyValue;
			if ( typeof widgetOptions[ property ] === 'object' ) {
				propertyValue = stringifyOptions( widgetOptions[ property ] );
			} else {
				propertyValue = widgetOptions[ property ];
			}
			widgetOptionsArray.push( property + ': ' + propertyValue );
		}
		return widgetOptionsArray.join( ', ' );
	};

	var mapAddressFields = function(data, source ) {
		for ( var attrKey in data ) {
			if ( !data.hasOwnProperty( attrKey ) ) {
				continue;
			}

			if ( false === data[ attrKey ].isWidgetResultField ) {
				continue;
			}

			var widgetField = data[ attrKey ].widgetResultField || attrKey;
			var addressFieldValue = source[ widgetField ];

			addressDataToSave[ data[ attrKey ].name = addressFieldValue ];
		}
	};
	var addressDataToSave = {};


	var resetWidgetInfoClasses = function() {
		$infoDiv.removeClass( 'error' ).removeClass( 'success' ).attr( 'hidden', 'hidden' );
	};

	$( document ).on( 'click', '.packeta-widget-button', function ( e ) {
		e.preventDefault();
		resetWidgetInfoClasses();

		var widgetOptions = {
			language: settings.language,
			layout: 'cd',
			expeditionDay: settings.expeditionDay,
			sample: settings.isCarDeliverySampleEnabled === '1'
		};

		console.log( 'Car delivery widget options: apiKey: ' + settings.packeteryApiKey + ', ' + stringifyOptions( widgetOptions ) );
		Packeta.Widget.pick( settings.packeteryApiKey, function( result ) {

			if ( ! result || ! result.id ) {
				return; // Widget was closed.
			}

			var selectedAddress = result.location.address;
			mapAddressFields( settings.carDeliveryAttrs, selectedAddress );
			addressDataToSave[ settings.carDeliveryAttrs[ 'carDeliveryId' ].name ] = result.id;
			addressDataToSave[ 'orderId' ] = settings.orderId; // Potential security risk!

			postWithNonce(
				settings.updateCarDeliveryAddressUrl,
				addressDataToSave,
				'Failed to update delivery address data: '
			);
		}, widgetOptions );
	});
};

packeteryChangeDeliveryAddressDetails( jQuery, packeteryCarDeliverySettings );

