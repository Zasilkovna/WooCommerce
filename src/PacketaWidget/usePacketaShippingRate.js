export const usePacketaShippingRate = ( shippingRates, carrierConfig ) => {
	if ( !shippingRates || shippingRates.length === 0 ) {
		return null;
	}
	const { shipping_rates } = shippingRates[ 0 ];
	if ( !shipping_rates || shipping_rates.length === 0 ) {
		return null;
	}

	const packetaShippingRate = shipping_rates.find( ( { rate_id, selected } ) => {
		if ( !selected ) {
			return false;
		}

		const rateId = rate_id.split( ':' ).pop();
		const rateCarrierConfig = carrierConfig[ rateId ];
		const { is_pickup_points: isPickupPoints } = rateCarrierConfig;

		return rateCarrierConfig && isPickupPoints;
	} );

	return packetaShippingRate || null;
}
