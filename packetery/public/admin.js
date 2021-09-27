(function( $ ) {

	$( function() {
		$( 'body' ).on( 'click', '.packetery-order-inline-edit', function( e ) {
			var $target = $( e.target );

			$( e.target ).WCBackboneModal( {
				template: "wc-packetery-modal-view-order",
				variable: {
					"data": $target.data( 'order-data' ),
					"nonce": $target.data( 'nonce' )
				}
			} );
		} ).on( 'click', '.packetery-save-button', function( e ) {
			var $target = $( e.target );
			if ( $target.hasClass( 'disabled' ) ) {
				return;
			}

			var data = $target.data( 'order-data' );
			var nonce = $target.data( 'nonce' );
			var $packeteryModal = $target.closest( '[data-packetery-modal]' );
			var packeteryWeight = $packeteryModal.find( '[name="packetery_weight"]' ).val();

			$packeteryModal.find( '.spinner' ).addClass( 'is-active' );
			$target.addClass( 'disabled' );
			$.ajax( {
				type: "POST",
				dataType: "json",
				url: `${packetery.baseUrl}/wp-json/packetery/v1/order/${data.id}`,
				data: {
					orderId: data.id,
					packeteryWeight: packeteryWeight,
					nonce: nonce
				}
			} ).done( function( response ) {
				if ( response.message ) {
					$packeteryModal.find( '.notice p' ).text(response.message).removeClass( 'hidden' ); // todo redesign messages?
				}
			} ).always( function( response ) {
				$target.removeClass( 'disabled' );
				$packeteryModal.find( '.spinner' ).removeClass( 'is-active' );
			} );
		} ).on( 'click', '[data-packetery-modal] .notice-dismiss', function( e ) {
			var $target = $(e.target);
			$target.closest( '.notice' ).addClass( 'hidden' );
		} );
	} );

})( jQuery );
