( function( $, settings ) {
	var $widgetDiv = $( '[data-packetery-order-metabox]' );
	if ( 0 === $widgetDiv.length ) {
		return;
	}

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

	$widgetDiv.on( 'click', '[name=packetery_pick_pickup_point]', function( e ) {
		e.preventDefault();

		console.log('Widget options: apiKey: ' + settings.packeteryApiKey + ', ' + stringifyOptions(settings.widgetOptions));

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
