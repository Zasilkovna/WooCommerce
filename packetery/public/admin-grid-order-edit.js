(function( $ ) {

	$( function() {
		var $lastModalButtonClicked;

		$( 'body' ).on( 'click', '[data-packetery-order-inline-edit]', function( e ) {
			var $target = $( e.target );
			$lastModalButtonClicked = $target;

			$target.WCBackboneModal( {
				template: "wc-packetery-modal-view-order",
				variable: {
					"order": $target.data( 'order-data' )
				}
			} );

			setTimeout(function() {
				Nette.init();
			}, 200);

		} ).on( 'submit', '#order-modal-edit-form', function( e ) {
			var $target = $( e.target );
			if ( $target.hasClass( 'disabled' ) ) {
				return false;
			}

			var orderId = $target.data( 'order-id' );
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
					orderId: orderId,
					packeteryWeight: packeteryWeight
				}
			} ).fail( function( response ) {
				var message = (response.responseJSON && response.responseJSON.message) || 'Error';
				flashMessage( 'error', message );
			} ).done( function( response ) {
				flashMessage( 'success', response.message );
				$packeteryModal.find( '[name="packetery_weight"]' ).val( response.data.packetery_weight );
				var orderData = $lastModalButtonClicked.data( 'order-data' );
				orderData.packetery_weight = response.data.packetery_weight;
				$lastModalButtonClicked.data( 'order-data', orderData );
			} ).always( function() {
				$target.removeClass( 'disabled' );
				$packeteryModal.find( '.spinner' ).removeClass( 'is-active' );
			} );

			return false;
		} ).on( 'click', '[data-packetery-modal] .notice-dismiss', function( e ) {
			var $target = $( e.target );
			$target.closest( '.notice' ).addClass( 'hidden' );
		} );
	} );

})( jQuery );
