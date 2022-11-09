(function( $ ) {

	$( function() {
		var $lastModalButtonClicked;

		var replaceFragmentsWith = function( fragments ) {
			$.each( fragments, function ( key, value ) {
				$( key ).replaceWith( value );
			} );
		}

		$( 'body' ).on( 'wc_backbone_modal_loaded', function( e ) {
			var $target = $( e.target );
			var packeteryModal = $target.find( '[data-packetery-modal]' );
			if ( packeteryModal.length > 0 ) {
				packeteryModal.find( '[name="packetery_weight"]' ).focus().select();
				Nette.init();
			}
		} ).on( 'click', '[data-packetery-order-inline-edit]', function( e ) {
			var $target = $( e.target );
			$lastModalButtonClicked = $target;

			$target.WCBackboneModal( {
				template: "wc-packetery-modal-view-order",
				variable: {
					"order": $target.data( 'order-data' )
				}
			} );

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
			var packeteryOriginalWeight = $packeteryModal.find( '[name="packetery_original_weight"]' ).val();
			var packeteryLength = $packeteryModal.find( '[name="packetery_length"]' ).val();
			var packeteryWidth  = $packeteryModal.find( '[name="packetery_width"]' ).val();
			var packeteryHeight = $packeteryModal.find( '[name="packetery_height"]' ).val();

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
					packeteryWeight: packeteryWeight,
					packeteryOriginalWeight: packeteryOriginalWeight,
					packeteryLength : packeteryLength,
					packeteryWidth: packeteryWidth ,
					packeteryHeight : packeteryHeight,
				}
			} ).fail( function( response ) {
				var message = (response.responseJSON && response.responseJSON.message) || 'Error';
				flashMessage( 'error', message );
			} ).done( function( response ) {
				flashMessage( 'success', response.message );
				$packeteryModal.find( '[name="packetery_weight"]' ).val( response.data.packetery_weight );
				$packeteryModal.find( '[name="packetery_original_weight"]' ).val( response.data.packetery_weight );
				$packeteryModal.find( '[name="packetery_length"]' ).val( response.data.packetery_length );
				$packeteryModal.find( '[name="packetery_width"]' ).val( response.data.packetery_width );
				$packeteryModal.find( '[name="packetery_height"]' ).val( response.data.packetery_height );

				var orderData = $lastModalButtonClicked.data( 'order-data' );
				orderData.packetery_weight = response.data.packetery_weight;
				orderData.packetery_original_weight = response.data.packetery_weight;
				orderData.packetery_length = response.data.packetery_length;
				orderData.packetery_width = response.data.packetery_width;
				orderData.packetery_height = response.data.packetery_height;
				orderData.manualWeightIconExtraClass = response.data.hasOrderManualWeight === true ? '' : 'packetery-hidden ';
				$lastModalButtonClicked.data( 'order-data', orderData );

				replaceFragmentsWith( response.data.fragments );

				$lastModalButtonClicked.removeClass( 'dashicons-warning dashicons-edit' );
				if ( response.data.orderIsSubmittable === true ) {
					$lastModalButtonClicked.addClass( 'dashicons-edit' ).next().removeClass('hidden');
				} else {
					$lastModalButtonClicked.addClass( 'dashicons-warning' ).next().addClass('hidden');
				}
				$( '[data-packetery-modal] .modal-close:first' ).trigger( 'click' );
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
