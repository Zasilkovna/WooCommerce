export const stringifyOptions = function ( widgetOptions ) {
	let widgetOptionsArray = [];
	for ( const property in widgetOptions ) {
		if ( ! widgetOptions.hasOwnProperty( property ) ) {
			continue;
		}
		let propertyValue;
		if ( typeof widgetOptions[ property ] === 'object' ) {
			propertyValue = stringifyOptions( widgetOptions[ property ] );
		} else {
			propertyValue = widgetOptions[ property ];
		}
		widgetOptionsArray.push( property + ': ' + propertyValue );
	}
	return widgetOptionsArray.join( ', ' );
};
