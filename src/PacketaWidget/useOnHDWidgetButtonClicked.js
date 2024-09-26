/**
 * Widget button callback.
 *
 * @package Packetery
 */

import { useCallback } from 'react';
import { fillRateAttrValues } from './fillRateAttrValues';
import { stringifyOptions } from "./stringifyOptions";

export const useOnHDWidgetButtonClicked = (
	packetaShippingRate,
	settings,
	dynamicSettings,
	setViewState,
	shippingAddress,
) => {
	const {
		carrierConfig,
		language,
		packeteryApiKey,
		appIdentity,
		nonce,
		saveValidatedAddressUrl,
		homeDeliveryAttrs,
		translations,
	} = settings;

	const onHDWidgetButtonClicked = useCallback( () => {
		const rateId = packetaShippingRate.rate_id.split( ':' ).pop();

		let widgetOptions = { language, appIdentity };
		widgetOptions.layout = 'hd';
		widgetOptions.country = shippingAddress.country.toLowerCase();
		widgetOptions.street = shippingAddress.address_1;
		widgetOptions.city = shippingAddress.city;
		widgetOptions.postcode = shippingAddress.postcode;
		widgetOptions.carrierId = carrierConfig[ rateId ].id;

		console.log( 'Address widget options: apiKey: ' + packeteryApiKey + ', ' + stringifyOptions( widgetOptions ) );

		// Storage to store settings of all Packeta shipping methods displayed at checkout.
		let rateAttrValues = {};

		Packeta.Widget.pick(
			packeteryApiKey,
			( result ) => {
				if ( ! result || ! result.address ) {
					return;
				}

				if ( result.address.country !== widgetOptions.country ) {
					setViewState( { deliveryAddressError: settings.translations.invalidAddressCountrySelected } );
					return;
				}

				const deliveryAddressInfo = translations.deliveryAddressNotification + ' ' + result.address.name;
				setViewState( { deliveryAddressInfo } );

				rateAttrValues = fillRateAttrValues( rateId, homeDeliveryAttrs, result.address, rateAttrValues );
				let homeDeliveryDataToSave = rateAttrValues[ rateId ];
				homeDeliveryDataToSave.packetery_rate_id = rateId;
				homeDeliveryDataToSave.packetery_address_isValidated = 1;

				fetch( saveValidatedAddressUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
						'X-WP-Nonce': nonce,
					},
					body: new URLSearchParams( homeDeliveryDataToSave ),
				} )
					.then( ( response ) => {
						if ( ! response.ok ) {
							throw new Error( 'HTTP error ' + response.status );
						}
					} )
					.catch( ( error ) => {
						console.error( 'Failed to save validated address data:', error );
					} );
			},
			widgetOptions
		);
	}, [ packetaShippingRate, dynamicSettings ] );

	return onHDWidgetButtonClicked;
};
