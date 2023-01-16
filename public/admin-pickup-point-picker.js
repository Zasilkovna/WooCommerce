( function( $, settings ) {
	var $widgetDiv = $( '[data-packetery-order-metabox]' );
	if ( 0 === $widgetDiv.length ) {
		return;
	}

	$widgetDiv.on( 'click', '[name=packetery_pick_pickup_point]', function( e ) {
		e.preventDefault();

		var widgeOptionsArray = [];
		for (const property in settings.widgetOptions) {
			widgeOptionsArray.push(property + ': ' + settings.widgetOptions[property]);
		}
		console.log('Widget options: ' + widgeOptionsArray.join(', '));

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
