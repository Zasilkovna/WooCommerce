define([
    'uiComponent'
], function(
    Component
) {
    'use strict';

    var changeVisibility = function(dataContainer, visible) {
        if(dataContainer.initChildCount !== dataContainer.elems().length) {

            if(dataContainer.visibilityHandler) {
                dataContainer.visibilityHandler.dispose();
            }

            dataContainer.visibilityHandler = dataContainer.elems.subscribe(function() {
                if(dataContainer.initChildCount === dataContainer.elems().length) {
                    dataContainer.visibilityHandler.dispose();
                    changeVisibility(dataContainer, visible);
                }
            });

            return;
        }

        var elems = dataContainer.elems();
        for(var key in elems) {
            if(elems.hasOwnProperty(key)) {
                if(elems[key].elems) {
                    elems[key].visible(visible);
                    changeVisibility(elems[key], visible);
                } else {
                    elems[key].visible(visible);
                }
            }
        }
    };

    var mixin = {
        visibilityHandler: null,
        hide: function() {
            changeVisibility(this, false);
        },
        show: function() {
            changeVisibility(this, true);
        }
    };

    return Component.extend(mixin);
});
