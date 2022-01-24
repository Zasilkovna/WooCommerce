(function ($) {

	$( 'body' ).on( 'click', '[data-packetery-confirm]', function( e ) {
		if ( ! confirm( jQuery(e.target).attr( 'data-packetery-confirm' ) ) ) {
			e.preventDefault();
			return false;
		}
	} );

})(jQuery);
