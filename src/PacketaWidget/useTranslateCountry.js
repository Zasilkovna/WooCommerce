/**
 * Getting country name from DOM and translating it to ISO code using AJAX.
 *
 * @package Packetery
 */

export const useTranslateCountry = (
	adminAjaxUrl,
	dynamicSettings,
	setDynamicSettings,
	loading,
	setLoading,
) => {

	const getShippingCountryName = function () {
		const inputElement = document.querySelector( '#shipping-country input' );
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
						.then( ( data ) => {
							if ( data !== null ) {
								setDynamicSettings( prevState => ( {
									...prevState,
									country: data,
								} ) );
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

				setDynamicSettings( prevState => ( {
					...prevState,
					countryName,
				} ) );
			}
		}
	}

	return [ dynamicSettings, loading ];
};
