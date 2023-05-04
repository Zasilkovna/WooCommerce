( function( $ ) {

	$( document ).ready(function( ) {
		$( 'input[name="packetery_deliver_on"]' ).datepicker( 'option', 'minDate', datePickerSettings.deliverOnMinDate );
		$( '.packetery-customs-declaration-fields input[name="invoice_issue_date"]' ).datepicker(
			{
				dateFormat: datePickerSettings.dateFormat,
				onSelect: function() {
					Nette.validateControl(this);
				}
			}
		);
	});

	new PacketeryMultiplier('[data-packetery-customs-declaration-item]');

} )( jQuery );
