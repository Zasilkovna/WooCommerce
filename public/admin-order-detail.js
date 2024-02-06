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
	}).on('change', '.packetery-customs-declaration-fields [name="packetery_customs_declaration[ead]"]', function( e ) {
		var $form = $(e.target).closest('form');

		Nette.validateControl( $form.find('input[name="packetery_customs_declaration[invoice_file]"]')[0] );
		Nette.validateControl( $form.find('input[name="packetery_customs_declaration[ead_file]"]')[0] );
		Nette.validateControl( $form.find('input[name="packetery_customs_declaration[mrn]"]')[0] );
	}).on( 'click', '[data-packetery-open-modal]', function( e ) {
		var $target = $( e.target );

		$target.WCBackboneModal( {
			template: $target.closest( '[data-packetery-open-modal]' ).data( 'packetery-open-modal' )
		} );
	});

	$( window ).on( 'beforeunload', function() {
		$( '[data-packetery-label-print-modal] .modal-close:visible:first' ).click();
	} );

	new PacketeryMultiplier('[data-packetery-customs-declaration-item]');

	$( 'body' ).on( 'wc_backbone_modal_loaded', function( e ) {
		var $target = $( e.target );
		var packeteryModal = $target.find( '[data-packetery-carrier-modal]' );
		if ( packeteryModal.length > 0 ) {
			Nette.init();
		}
	} ).on( 'submit', '#order-carrier-modal-form', function( e ) {
		var $target = $( e.target );
		if ( $target.hasClass( 'disabled' ) ) {
			return false;
		}

		var nonce = $target.data( 'nonce' );
		var $packeteryModal = $target.closest( '[data-packetery-carrier-modal]' );
		var carrierId = $packeteryModal.find( '[name="carrierId"]' ).val();

		$packeteryModal.find( '.spinner' ).addClass( 'is-active' );
		$target.addClass( 'disabled' );
		$.ajax( {
			type: 'POST',
			dataType: 'json',
			url: orderSaveUrl,
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', nonce );
			},
			data: {
				carrierId : carrierId,
			}
		} ).fail( function( response ) {
			var message = (response.responseJSON && response.responseJSON.message) || 'Error';
			flashMessage( $packeteryModal, 'error', message );
		} ).done( function( response ) {
			flashMessage( $packeteryModal, 'success', response.message );

			var orderData = $lastModalButtonClicked.data( 'order-data' );
			orderData.carrierId = response.data.carrierId;
			$lastModalButtonClicked.data( 'order-data', orderData );

			replaceFragmentsWith( response.data.fragments );
			$( '[data-packetery-modal] .modal-close:first' ).trigger( 'click' );
		} ).always( function() {
			$target.removeClass( 'disabled' );
			$packeteryModal.find( '.spinner' ).removeClass( 'is-active' );
		} );

		return false;
	} );

} )( jQuery );
