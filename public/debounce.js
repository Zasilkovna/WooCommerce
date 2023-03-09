/**
 * Function ensures callback triggers once with given minimal delay.
 *
 * @param func Callback.
 * @param wait Minimal delay in milliseconds.
 * @returns {function(...[*]): void}
 */
var packeteryDebounce = function( func, wait ) {
	var timeout;

	return function() {
		var argsToPass = arguments;
		var that = this;

		var later = function() {
			clearTimeout( timeout );
			func.apply( that, argsToPass );
		};

		clearTimeout( timeout );
		timeout = setTimeout( later, wait );
	};
};
