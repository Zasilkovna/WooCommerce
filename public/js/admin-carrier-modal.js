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

		$( 'fieldset' ).on( 'change', 'select[name="woocommerce_packetery_shipping_method_carrier_id"]', function () {
			toggleCarrierSettingsButton( $( this ) );
		} );
		toggleCarrierSettingsButton( $( 'select[name="woocommerce_packetery_shipping_method_carrier_id"]' ) );
	} );

} )( jQuery );
