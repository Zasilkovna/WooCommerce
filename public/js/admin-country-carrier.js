(function ($) {

	$(function () {
		// are we on the right page?
		if ($('.packetery-carrier-options-page').length === 0) {
			return;
		}

		new PacketeryMultiplier('.js-weight-rules');
		new PacketeryMultiplier('.js-product-value-rules');
		new PacketeryMultiplier('.js-surcharge-rules');

		$( '[data-packetery-select2]' ).select2();
	});

})(jQuery);
