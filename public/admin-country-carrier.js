(function ($) {

    $(function () {
        // are we on the right page?
        if ($('.packetery-carrier-options-page').length === 0) {
            return;
        }

        new PacketeryMultiplier('.js-weight-rules');
        new PacketeryMultiplier('.js-surcharge-rules');
    });

})(jQuery);
