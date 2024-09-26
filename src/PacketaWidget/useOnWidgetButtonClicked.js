/**
 * Widget button callback.
 *
 * @package Packetery
 */

import { useCallback } from 'react';
import { fillRateAttrValues } from './fillRateAttrValues';
import { stringifyOptions } from "./stringifyOptions";

export const useOnWidgetButtonClicked = (
	packetaShippingRate,
	settings,
	dynamicSettings,
	setViewState,
	shippingAddress,
	cartItemsWeight,
) => {
	const {
		carrierConfig,
		language,
		packeteryApiKey,
		appIdentity,
		nonce,
		saveSelectedPickupPointUrl,
		pickupPointAttrs,
	} = settings;

	const onWidgetButtonClicked = useCallback( () => {
		const rateId = packetaShippingRate.rate_id.split( ':' ).pop();

		let weight = +( cartItemsWeight / 1000 ).toFixed( 2 );
		let widgetOptions = { language, appIdentity, weight };
		widgetOptions.country = shippingAddress.country.toLowerCase();
		if ( carrierConfig[ rateId ].carriers ) {
			widgetOptions.carriers = carrierConfig[ rateId ].carriers;
		}
		if ( carrierConfig[ rateId ].vendors ) {
			widgetOptions.vendors = carrierConfig[ rateId ].vendors;
		}
		if ( dynamicSettings && dynamicSettings.isAgeVerificationRequired ) {
			widgetOptions.livePickupPoint = true; // Pickup points with real person only.
		}

		console.log( 'Pickup point widget options: apiKey: ' + packeteryApiKey + ', ' + stringifyOptions( widgetOptions ) );

		// Storage to store settings of all Packeta shipping methods displayed at checkout.
		let rateAttrValues = {};

		Packeta.Widget.pick(
			packeteryApiKey,
			( pickupPoint ) => {
				if ( ! pickupPoint ) {
					return;
				}

				setViewState( { pickupPoint } );

				rateAttrValues = fillRateAttrValues( rateId, pickupPointAttrs, pickupPoint, rateAttrValues );
				let pickupPointDataToSave = rateAttrValues[ rateId ];
				pickupPointDataToSave.packetery_rate_id = rateId;

				fetch( saveSelectedPickupPointUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
						'X-WP-Nonce': nonce,
					},
					body: new URLSearchParams( pickupPointDataToSave ),
				} )
					.then( ( response ) => {
						if ( ! response.ok ) {
							throw new Error( 'HTTP error ' + response.status );
						}
					} )
					.catch( ( error ) => {
						console.error(
							'Failed to save pickup point data:',
							error
						);
					} );
			},
			widgetOptions
		);
	}, [ packetaShippingRate, dynamicSettings ] );

	return onWidgetButtonClicked;
};
