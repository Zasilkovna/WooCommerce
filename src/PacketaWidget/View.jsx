import { __ } from "@wordpress/i18n";
import { getSetting } from '@woocommerce/settings';
import {Fragment, useEffect} from "react";
import {useSessionStorageState} from "./useSessionStorageState";
import { ValidatedTextInput } from '@woocommerce/blocks-components';

export const View = ({cart}) => {
    const {shippingRates} = cart;
    const {
        carrierConfig: packetaWidgetCarrierConfig,
        translations,
        logo,
        country,
        language,
        packeteryApiKey,
        appIdentity,
        weight,
        isAgeVerificationRequired,
        widgetAutoOpen,
        nonce,
        saveSelectedPickupPointUrl,
        pickupPointAttrs,
    } = getSetting( 'packeta-widget_data' );

    const [viewState, setViewState] = useSessionStorageState('packeta-widget-view', null);

    console.log('initial', shippingRates);
    if (!shippingRates || shippingRates.length === 0) {
        console.log('no shipping rates', cart);
        return null;
    }

    const availableShippingRates = shippingRates[0].shipping_rates;
    if (!availableShippingRates || availableShippingRates.length === 0) {
        console.log('no available shipping rates');
        return null;
    }

    const packetaShippingRate = availableShippingRates.find((shippingRate) => {
        const rateId = shippingRate.rate_id.split(':').pop();
        return shippingRate.selected
            && packetaWidgetCarrierConfig[rateId]
            && packetaWidgetCarrierConfig[rateId].is_pickup_points;
    });

    if (!packetaShippingRate) {
        console.log('no packeta shipping rate', availableShippingRates, packetaWidgetCarrierConfig);
        return null;
    }

    const onWidgetButtonClicked = () => {
        const rateId = packetaShippingRate.rate_id.split(':').pop();

        let widgetOptions = {
            country: country,
            language: language,
            appIdentity: appIdentity,
            weight: weight,
        };
        if ( packetaWidgetCarrierConfig[ rateId ].carriers ) {
            widgetOptions.carriers = packetaWidgetCarrierConfig[ rateId ].carriers;
        }
        if ( packetaWidgetCarrierConfig[ rateId ].vendors ) {
            widgetOptions.vendors = packetaWidgetCarrierConfig[ rateId ].vendors;
        }
        if ( isAgeVerificationRequired ) {
            widgetOptions.livePickupPoint = true; // Pickup points with real person only.
        }

        const encodeFormData = function ( data ) {
            return Object.keys( data )
                .map( key => encodeURIComponent( key ) + '=' + encodeURIComponent( data[ key ] ) )
                .join( '&' );
        }

        const fillRateAttrValues = function ( carrierRateId, data, source ) {
            for ( let attrKey in data ) {
                if ( !data.hasOwnProperty( attrKey ) ) {
                    continue;
                }

                if ( false === data[ attrKey ].isWidgetResultField ) {
                    continue;
                }

                let widgetField = data[ attrKey ].widgetResultField || attrKey;
                let addressFieldValue = source[ widgetField ];

                rateAttrValues[ carrierRateId ] = rateAttrValues[ carrierRateId ] || {};
                rateAttrValues[ carrierRateId ][ data[ attrKey ].name ] = addressFieldValue;
            }
        };

        // Storage to store settings of all Packeta shipping methods displayed at checkout.
        let rateAttrValues = {};

        Packeta.Widget.pick( packeteryApiKey, ( pickupPoint ) => {
            setViewState({
                pickupPoint,
            });

            fillRateAttrValues( rateId, pickupPointAttrs, pickupPoint );
            let pickupPointDataToSave = rateAttrValues[ rateId ];
            pickupPointDataToSave.packetery_rate_id = rateId;
            fetch( saveSelectedPickupPointUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-WP-Nonce': nonce,
                },
                body: encodeFormData( pickupPointDataToSave ),
            } )
                .then( response => {
                    if ( !response.ok ) {
                        throw new Error( 'HTTP error ' + response.status );
                    }
                } )
                .catch( ( error ) => {
                    console.error( 'Failed to save pickup point data:', error );
                } );
        }, widgetOptions );
    };

    /*
    useEffect( () => {
        if ( widgetAutoOpen ) {
            onWidgetButtonClicked();
        }
    }, [
        widgetAutoOpen,
        onWidgetButtonClicked,
    ] );

     */

    console.log('state', viewState);

    // same as latte, different renderer, packetery-hidden removed
    return <div className="packetery-widget-button-wrapper">
        <div className="form-row packeta-widget blocks">
            <div className="packetery-widget-button-row packeta-widget-button">
                <img className="packetery-widget-button-logo" src={ logo } alt={ translations.packeta }/>
                <a onClick={ onWidgetButtonClicked }
                   className="button alt components-button wc-block-components-button wp-element-button contained">{ translations.choosePickupPoint }</a>
            </div>
            <p className="packeta-widget-selected-address"></p>
            <ValidatedTextInput
                style={ {
                    opacity: 0,
                    width: 0,
                    padding: 0,
                    float: 'left',
                } }
                required={ true }
            />
            { viewState && viewState.pickupPoint && <Fragment>
                <p className="packeta-widget-info">{ viewState.pickupPoint.name }</p>
            </Fragment> }
        </div>
    </div>
}
