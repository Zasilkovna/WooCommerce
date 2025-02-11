export const getShippingMethodOptionId = function ( rateId ) {
	if ( rateId.startsWith( 'packeta_method_' ) ) {
		const [ methodId, instanceId ] = rateId.split( ':' );
		return 'packetery_carrier_' + methodId.replace( 'packeta_method_', '' );
	}

	return rateId.replace( 'packetery_shipping_method:', '' );
};
