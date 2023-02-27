( function( $, settings ) {
	var $widgetDiv = $( '[data-packetery-order-metabox]' );
	if ( 0 === $widgetDiv.length ) {
		return;
	}

	var stringifyOptions = function (widgetOptions) {
		var widgeOptionsArray = [];
		for (const property in widgetOptions) {
			if (!widgetOptions.hasOwnProperty(property)) {
				continue;
			}
			if (typeof widgetOptions[property] === 'object') {
				widgeOptionsArray.push(property + ': ' + stringifyOptions(widgetOptions[property]));
			} else {
				widgeOptionsArray.push(property + ': ' + widgetOptions[property]);
			}
		}
		return widgeOptionsArray.join(', ');
	};

	$widgetDiv.on( 'click', '[name=packetery_pick_pickup_point]', function( e ) {
		e.preventDefault();

		console.log('Widget options: ' + stringifyOptions(settings.widgetOptions));

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
		}, settings.widgetOptions );
	} );

})( jQuery, packeteryPickupPointPickerSettings );
