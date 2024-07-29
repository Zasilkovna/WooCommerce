/**
 * Widget button callback.
 *
 * @package Packetery
 */

import { useCallback } from 'react';

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

		const fillRateAttrValues = function ( carrierRateId, data, source ) {
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
		};

		// Storage to store settings of all Packeta shipping methods displayed at checkout.
		let rateAttrValues = {};

		Packeta.Widget.pick(
			packeteryApiKey,
			( pickupPoint ) => {
				if ( ! pickupPoint ) {
					return;
				}

				setViewState( { pickupPoint } );

				fillRateAttrValues( rateId, pickupPointAttrs, pickupPoint );
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
