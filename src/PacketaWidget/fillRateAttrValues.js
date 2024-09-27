export const fillRateAttrValues = function ( carrierRateId, data, source, rateAttrValues ) {
	for ( let attrKey in data ) {
		if ( ! data.hasOwnProperty( attrKey ) ) {
			continue;
		}

		const { name, widgetResultField, isWidgetResultField } =
			data[ attrKey ];

		if ( false === isWidgetResultField ) {
			continue;
		}

		let widgetField = widgetResultField || attrKey;
		let addressFieldValue = source[ widgetField ];

		rateAttrValues[ carrierRateId ] =
			rateAttrValues[ carrierRateId ] || {};
		rateAttrValues[ carrierRateId ][ name ] = addressFieldValue;
	}

	return rateAttrValues;
};
