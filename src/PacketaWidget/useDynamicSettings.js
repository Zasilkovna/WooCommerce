import {useEffect, useState} from "react";

export const useDynamicSettings = ( getSettingsUrl ) => {
    const [ dynamicSettings, setDynamicSettings ] = useState( null );
    const [ loading, setLoading ] = useState( false );

    useEffect( () => {
        if ( !loading && dynamicSettings === null ) {
            setLoading(true);
            fetch( getSettingsUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams( {
                    action: 'get_settings',
                } ),
            } )
                .then( response => response.json() )
                .then( data => {
                        const {
                            weight,
                            isAgeVerificationRequired,
                        } = data;
                        setDynamicSettings( { weight, isAgeVerificationRequired } );
                    }
                )
                .catch( ( error ) => {
                    console.error( 'Error:', error );
                    setDynamicSettings( false );
                } )
                .finally( () => {
                    setLoading( false );
                } );
        }
    }, [ dynamicSettings, getSettingsUrl, loading ] );

    return [ dynamicSettings, loading ];
}
