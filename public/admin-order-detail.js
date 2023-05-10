( function( $ ) {

	$( document ).ready(function( ) {
		$( 'input[name="packetery_deliver_on"]' ).datepicker( 'option', 'minDate', datePickerSettings.deliverOnMinDate );
		var $invoiceIssueDate = $( '.packetery-customs-declaration-fields input[name="packetery_customs_declaration[invoice_issue_date]"]' );
		$invoiceIssueDate.datepicker(
			{
				dateFormat: datePickerSettings.dateFormat,
				onSelect: function() {
					Nette.validateControl(this);
				}
			}
		);
		$invoiceIssueDate.closest('form').attr('enctype', 'multipart/form-data');
	});

	new PacketeryMultiplier('[data-packetery-customs-declaration-item]');

} )( jQuery );
