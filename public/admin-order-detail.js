( function( $ ) {

	$( document ).ready(function( ) {
		$( 'input[name="packetery_deliver_on"]' ).datepicker( 'option', 'minDate', datePickerSettings.deliverOnMinDate );
	});

	new PacketeryMultiplier('.packetery-customs-declaration-item');

} )( jQuery );
