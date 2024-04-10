( function ( $ ) {

	$( function () {

		if ( $( '.packetery-carrier-link-container' ).length === 0 ) {
			return;
		}

		var toggleCarrierSettingsButton = function ( $carrierSelect ) {
			var carrierId = $carrierSelect.val();
			var $link = $( '.packetery-carrier-link-container a' );
			if ( carrierId === '' ) {
				$link.hide();
			} else {
				$link.attr( 'href', $link.data( 'base' ) + carrierId );
				$link.show();
			}
		};

		$( 'fieldset' ).on( 'change', 'select[name="packetery_shipping_method"]', function () {
			toggleCarrierSettingsButton( $( this ) );
		} );
		toggleCarrierSettingsButton( $( 'select[name="packetery_shipping_method"]' ) );
	} );

} )( jQuery );
