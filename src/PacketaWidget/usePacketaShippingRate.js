/**
 * Shipping rate getter.
 *
 * @package Packetery
 */

export const usePacketaShippingRate = ( shippingRates, carrierConfig ) => {
	if ( ! shippingRates || shippingRates.length === 0 ) {
		return null;
	}
	const { shipping_rates } = shippingRates[ 0 ];
	if ( ! shipping_rates || shipping_rates.length === 0 ) {
		return null;
	}

	const findShippingRate = ( callback ) => {
		return shipping_rates.find(
			( { rate_id, selected } ) => {
				if ( ! selected ) {
					return false;
				}

				const rateId = rate_id.split( ':' ).pop();
				const rateCarrierConfig = carrierConfig[ rateId ];
				if ( ! rateCarrierConfig ) {
					return false;
				}

				return callback( rateCarrierConfig );
			}
		);
	};

	const packetaPickupPointShippingRate = findShippingRate( rateCarrierConfig => {
		const { is_pickup_points: isPickupPoints } = rateCarrierConfig;
		return rateCarrierConfig && isPickupPoints;
	} );

	const packetaHomeDeliveryShippingRate = findShippingRate( rateCarrierConfig => {
		const { is_pickup_points: isPickupPoints } = rateCarrierConfig;
		return rateCarrierConfig && ! isPickupPoints;
	} );

	const chosenShippingRate = shipping_rates.find(
		( { selected } ) => {
			return selected;
		}
	);

	return {
		packetaPickupPointShippingRate: packetaPickupPointShippingRate || null,
		packetaHomeDeliveryShippingRate: packetaHomeDeliveryShippingRate || null,
		chosenShippingRate: chosenShippingRate || null,
	};
};
