var packeteryLoadPickupPointPicker = function( $, settings ) {
	var init = function( settings ) {
		var $widgetDiv = $( '[data-packetery-order-metabox]' );

		$widgetDiv.on( 'click', '[name=packetery_pick_pickup_point]', function( e ) {
			e.preventDefault();

			var widgetOptions = {
				country: settings.country,
				language: settings.language,
				appIdentity: settings.appIdentity,
				weight: settings.weight,
				carriers: settings.carriers
			};

			if ( settings.isAgeVerificationRequired ) {
				widgetOptions.livePickupPoint = true; // Pickup points with real person only.
			}

			Packeta.Widget.pick( settings.packeteryApiKey, function( point ) {
				if ( point == null ) {
					return;
				}

				for ( var attrKey in settings.pickupPointAttrs ) {
					if ( !settings.pickupPointAttrs.hasOwnProperty( attrKey ) ) {
						continue;
					}

					var attr = settings.pickupPointAttrs[ attrKey ];

					$widgetDiv.find( '[name=' + attr.name + ']' ).val( point[ attrKey ] || '' );
				}

				$widgetDiv.find( '[data-packetery-widget-info]' ).html( point.name );
			}, widgetOptions );
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
		init( settings );
	} );
};
