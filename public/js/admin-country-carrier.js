(function ($) {

	$(function () {
		var language = {};
		if ( typeof packeteryCountryCarrier !== 'undefined' && packeteryCountryCarrier.translations ) {
			var t = packeteryCountryCarrier.translations;
			if ( t.noResults ) {
				language.noResults = function () {
					return t.noResults;
				};
			}
		}
		$( '[data-packetery-select2]' ).select2( { language: language } );

		if ( $('.packetery-carrier-options-page').length === 0 ) {
			return;
		}

		new PacketeryMultiplier('.js-weight-rules');
		new PacketeryMultiplier('.js-product-value-rules');
		new PacketeryMultiplier('.js-surcharge-rules');
	});

})(jQuery);
