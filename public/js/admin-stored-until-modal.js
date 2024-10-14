( function ( $ ) {
	$( function () {
		let $lastModalButtonClicked;

		let flashMessage = function ( $packeteryModal, type, message ) {
			$packeteryModal.find( '.notice' ).removeClass( 'notice-success' ).removeClass( 'notice-error' ).addClass( 'notice-' + type ).removeClass( 'hidden' ).find( 'p' ).text( message );
		};

		$( 'body' ).on( 'wc_backbone_modal_loaded', function ( e ) {
			let $target = $( e.target );
			let packeteryModal = $target.find( '[data-packetery-stored-until-modal]' );
			if ( packeteryModal.length > 0 ) {
				Nette.init();
				$( document.body ).trigger( 'wc-init-datepickers' );
			}
		} ).on( 'click', '[data-packetery-stored-until-edit]', function ( e ) {
			let $target = $( e.target );
			$lastModalButtonClicked = $target;

			$target.WCBackboneModal( {
				template: 'wc-packetery-stored-until-modal',
				variable: {
					'order': $target.closest('[data-packetery-stored-until-edit]').data( 'order-data' )
				}
			} );

			$( 'input[name="packetery_stored_until"]' ).datepicker( 'option', 'minDate', $target.closest('[data-packetery-stored-until-edit]').data( 'order-data' ).packetery_stored_until );
		} ).on( 'submit', '#order-stored-until-form', function ( e ) {
			let $target = $( e.target );
			if ( $target.hasClass( 'disabled' ) ) {
				return false;
			}
			let orderId = $target.data( 'order-id' );
			let storedUntilSaveUrl = $target.data( 'stored-until-save-url' );
			let nonce = $target.data( 'nonce' );
			let $packeteryModal = $target.closest( '[data-packetery-stored-until-modal]' );
			let packeteryStoredUntil = $packeteryModal.find( '[name="packetery_stored_until"]' ).val();

			$packeteryModal.find( '.spinner' ).addClass( 'is-active' );
			$target.addClass( 'disabled' );
			console.log(packeteryStoredUntil);
			$.ajax( {
				type: 'POST',
				dataType: 'json',
				url: storedUntilSaveUrl,
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', nonce );
				},
				data: {
					orderId: orderId,
					packeteryStoredUntil: packeteryStoredUntil,
				}
			} ).fail( function ( response ) {
				let message = ( response.responseJSON && response.responseJSON.message ) || 'Error';
				flashMessage( $packeteryModal, 'error', message );
			} ).done( function ( response ) {
				flashMessage( $packeteryModal, 'success', response.message );

				$( '[data-packetery-stored-until-modal] .modal-close:first' ).trigger( 'click' );
				location.reload();
			} ).always( function () {
				$target.removeClass( 'disabled' );
				$packeteryModal.find( '.spinner' ).removeClass( 'is-active' );
			} );

			return false;
		} ).on( 'click', '[data-packetery-stored-until-modal] .notice-dismiss', function ( e ) {
			let $target = $( e.target );
			$target.closest( '.notice' ).addClass( 'hidden' );
		} );
	} );
} )( jQuery );
