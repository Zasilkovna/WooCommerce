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

			Packeta.Widget.pick( settings.packeteryApiKey, function( point ) {
				if ( point == null ) {
					return;
				}

				$widgetDiv.find( '[name=packetery_point_id]' ).val(point.id || '');
				$widgetDiv.find( '[name=packetery_point_name]' ).val(point.name || '');
				$widgetDiv.find( '[name=packetery_point_city]' ).val(point.city || '');
				$widgetDiv.find( '[name=packetery_point_zip]' ).val(point.zip || '');
				$widgetDiv.find( '[name=packetery_point_street]' ).val(point.street || '');
				$widgetDiv.find( '[name=packetery_point_url]' ).val(point.url || '');
				$widgetDiv.find( '[name=packetery_carrier_id]' ).val(point.carrierId || '');

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
