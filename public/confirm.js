(function( $ ) {
	var confirmed = false;
	var $confirmTarget;

	$( 'body' ).on( 'click', '[data-packetery-confirm]', function( e ) {
		if ( confirmed ) {
			$confirmTarget = null;
			confirmed = false;
			return;
		}

		$confirmTarget = $( e.target );
		$confirmTarget.WCBackboneModal( {
			template: 'wc-packetery-confirm-modal',
			variable: {
				'text': $confirmTarget.data( 'packetery-confirm' )
			}
		} );

		e.preventDefault();
		return false;

	} ).on( 'click', '[data-packetery-confirm-modal] [data-packetery-confirm-yes]', function() {
		if ( $confirmTarget ) {
			confirmed = true;
			$confirmTarget[ 0 ].click();
		}
	} );

})( jQuery );
