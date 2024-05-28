/**
 * Additional loading of widget settings.
 *
 * @package Packetery
 */

import { useEffect, useState } from 'react';
import { useTranslateCountry } from "./useTranslateCountry";

export const useDynamicSettings = ( adminAjaxUrl ) => {
	let [ dynamicSettings, setDynamicSettings ] = useState( null );
	let [ loading, setLoading ] = useState( false );

	[ dynamicSettings, loading ] = useTranslateCountry(
		adminAjaxUrl,
		dynamicSettings,
		setDynamicSettings,
		loading,
		setLoading,
	);

	useEffect( () => {
		if ( ! loading && dynamicSettings === null ) {
			setLoading( true );
			fetch( adminAjaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams( {
					action: 'get_settings',
				} ),
			} )
				.then( ( response ) => response.json() )
				.then( ( data ) => {
					const { weight, isAgeVerificationRequired } = data;
					setDynamicSettings( prevState => ( {
						...prevState,
						weight,
						isAgeVerificationRequired,
					} ) );
				} )
				.catch( ( error ) => {
					console.error( 'Error:', error );
					setDynamicSettings( false );
				} )
				.finally( () => {
					setLoading( false );
				} );
		}
	}, [ dynamicSettings, adminAjaxUrl, loading ] );

	return [ dynamicSettings, loading ];
};
