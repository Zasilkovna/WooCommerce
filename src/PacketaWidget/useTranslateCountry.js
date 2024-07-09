/**
 * Getting country name from DOM and translating it to ISO code using AJAX.
 *
 * @package Packetery
 */

import { useEffect } from 'react';

export const useTranslateCountry = (
	adminAjaxUrl,
	dynamicSettings,
	setDynamicSettings,
	loading,
	setLoading,
	setViewState,
) => {

	useEffect( () => {
		const getShippingCountryName = function () {
			let inputElement = document.querySelector( '#shipping-country input' );
			if ( ! inputElement ) {
				inputElement = document.querySelector( '#billing-country input' );
			}
			if ( inputElement ) {
				return inputElement.value;
			}
			// keep initial country
			return null;
		};

		const countryName = getShippingCountryName();
		if ( countryName !== null ) {
			if ( dynamicSettings === null || dynamicSettings.countryName === null ) {
				setDynamicSettings( prevState => ( {
					...prevState,
					countryName,
				} ) );
			} else {
				const previousCountryName = dynamicSettings.countryName;
				if ( previousCountryName !== countryName ) {
					setDynamicSettings( { ...dynamicSettings, countryName } );
					setViewState( null );

					if ( ! loading ) {
						setLoading( true );
						fetch( adminAjaxUrl, {
							method: 'POST',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded',
							},
							body: new URLSearchParams( {
								action: 'translate_country_name',
								countryName: countryName,
							} ),
						} )
						.then( ( response ) => response.json() )
						.then( ( country ) => {
							if ( country !== null ) {
								setDynamicSettings( prevState => ( { ...prevState, country } ) );
							}
						} )
						.catch( ( error ) => {
							console.error( 'Error:', error );
							// keep previous country
						} )
						.finally( () => {
							setLoading( false );
						} );
					}
				}
			}
		}
	} );

	return [ dynamicSettings, loading ];
};
