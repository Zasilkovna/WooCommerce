(function( $ ) {

	$( function() {
		$( 'body' ).on( 'click', '.packetery-order-inline-edit', function( e ) {
			var $target = $( e.target );

			$target.WCBackboneModal( {
				template: "wc-packetery-modal-view-order",
				variable: {
					"data": $target.data( 'order-data' ),
					"nonce": $target.data( 'nonce' ),
					"orderSaveUrl": $target.data( 'order-save-url' ),
				}
			} );
		} ).on( 'click', '.packetery-save-button', function( e ) {
			var $target = $( e.target );
			if ( $target.hasClass( 'disabled' ) ) {
				return;
			}

			var data = $target.data( 'order-data' );
			var orderSaveUrl = $target.data( 'order-save-url' );
			var nonce = $target.data( 'nonce' );
			var $packeteryModal = $target.closest( '[data-packetery-modal]' );
			var packeteryWeight = $packeteryModal.find( '[name="packetery_weight"]' ).val();

			var flashMessage = function( type, message ) {
				$packeteryModal.find( '.notice' ).removeClass( 'notice-success' ).removeClass( 'notice-error' ).addClass( 'notice-' + type ).removeClass( 'hidden' ).find( 'p' ).text( message );
			};

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
					orderId: data.id,
					packeteryWeight: packeteryWeight
				}
			} ).fail( function( response ) {
				var message = (response.responseJSON && response.responseJSON.message) || 'Error';
				flashMessage( 'error', message );
			} ).done( function( response ) {
				flashMessage( 'success', response.message );
			} ).always( function() {
				$target.removeClass( 'disabled' );
				$packeteryModal.find( '.spinner' ).removeClass( 'is-active' );
			} );
		} ).on( 'click', '[data-packetery-modal] .notice-dismiss', function( e ) {
			var $target = $( e.target );
			$target.closest( '.notice' ).addClass( 'hidden' );
		} );
	} );

})( jQuery );
