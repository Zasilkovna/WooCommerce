(function( $ ) {

	$( function() {
		var $lastModalButtonClicked;

		var replaceFragmentsWith = function( fragments ) {
			$.each( fragments, function ( key, value ) {
				$( key ).replaceWith( value );
			} );
		}

		var flashMessage = function( $packeteryModal, type, message ) {
			$packeteryModal.find( '.notice' ).removeClass( 'notice-success' ).removeClass( 'notice-error' ).addClass( 'notice-' + type ).removeClass( 'hidden' ).find( 'p' ).text( message );
		};

		$( 'body' ).on( 'wc_backbone_modal_loaded', function( e ) {
			var $target = $( e.target );
			var packeteryModal = $target.find( '[data-packetery-modal]' );
			if ( packeteryModal.length > 0 ) {
				packeteryModal.find( '[name="packetery_weight"]' ).focus().select();
				Nette.init();
				$(document.body).trigger( 'wc-init-datepickers' );
				$( 'input[name="packetery_deliver_on"]' ).datepicker( 'option', 'minDate', datePickerSettings.deliverOnMinDate );

				if ( $lastModalButtonClicked.data( 'order-data' ).hasToFillCustomsDeclaration ) {
					flashMessage( packeteryModal, 'error', settings.translations.hasToFillCustomsDeclaration )
				}

				if ( ! $lastModalButtonClicked.data( 'order-data' ).orderIsSubmittable ) {
					var orderWarningFields = $lastModalButtonClicked.data( 'order-data' ).orderWarningFields;
					for ( var invalidFieldNameKey in orderWarningFields ) {
						if ( ! orderWarningFields.hasOwnProperty( invalidFieldNameKey ) ) {
							continue;
						}

						packeteryModal.find( '[name="' + orderWarningFields[ invalidFieldNameKey ] + '"]' ).addClass('packetery-has-warning');
					}

					flashMessage( packeteryModal, 'warning', settings.translations.packetSubmissionNotPossible );
				}
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
			var packeteryDeliverOn = $packeteryModal.find( '[name="packetery_deliver_on"]' ).val();
			var packeteryCOD = $packeteryModal.find( '[name="packetery_COD"]' ).val();
			var packeteryValue = $packeteryModal.find( '[name="packetery_value"]' ).val();
			var hasPacketeryAdultContent = $packeteryModal.find('[name="packetery_adult_content"]').prop('checked');

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
					packeteryWidth: packeteryWidth,
					packeteryHeight : packeteryHeight,
					packeteryDeliverOn : packeteryDeliverOn,
					packeteryCOD : packeteryCOD,
					packeteryValue : packeteryValue,
					hasPacketeryAdultContent : hasPacketeryAdultContent,
				}
			} ).fail( function( response ) {
				var message = (response.responseJSON && response.responseJSON.message) || 'Error';
				flashMessage( $packeteryModal, 'error', message );
			} ).done( function( response ) {
				flashMessage( $packeteryModal, 'success', response.message );

				var orderData = $lastModalButtonClicked.data( 'order-data' );
				orderData.packetery_weight = response.data.packetery_weight;
				orderData.packetery_original_weight = response.data.packetery_weight;
				orderData.packetery_length = response.data.packetery_length;
				orderData.packetery_width = response.data.packetery_width;
				orderData.packetery_height = response.data.packetery_height;
				orderData.packetery_deliver_on = response.data.packetery_deliver_on;
				orderData.packetery_COD = response.data.packetery_COD;
				orderData.packetery_value = response.data.packetery_value;
				orderData.packetery_adult_content = response.data.packetery_adult_content;
				orderData.orderIsSubmittable = response.data.orderIsSubmittable;
				orderData.orderWarningFields = response.data.orderWarningFields;
				orderData.manualWeightIconExtraClass = response.data.hasOrderManualWeight === true ? '' : 'packetery-hidden ';
				$lastModalButtonClicked.data( 'order-data', orderData );

				replaceFragmentsWith( response.data.fragments );

				$lastModalButtonClicked.removeClass( 'dashicons-warning dashicons-edit' );
				var $submitPacketButton = $lastModalButtonClicked.siblings('.packetery-submit-button-inline');
				if ( response.data.orderIsSubmittable === true ) {
					$lastModalButtonClicked.addClass( 'dashicons-edit' );
					$submitPacketButton.removeClass('hidden');
				} else {
					$lastModalButtonClicked.addClass( 'dashicons-warning' );
					$submitPacketButton.addClass('hidden');
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
