define([], function () {
    'use strict';

    return {
        getPacketaPoint: function(defaultReturnValue) {
            if (localStorage.packetaPoint) {
                return JSON.parse(localStorage.packetaPoint); // TODO: Store data in backend to avoid direct edit by unwanted actor. Issue was communicated with manager.
            }

            return defaultReturnValue;
        },

        getPacketaValidatedAddress: function(defaultReturnValue) {
            if (localStorage.packetaValidatedAddress) {
                return JSON.parse(localStorage.packetaValidatedAddress); // TODO: Store data in backend to avoid direct edit by unwanted actor. Issue was communicated with manager.
            }

            return defaultReturnValue;
        }
    }
});
