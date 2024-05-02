import {Fragment, useEffect, useState} from "react";
import { getSetting } from '@woocommerce/settings';
import { ValidatedTextInput } from '@woocommerce/blocks-components';

import {usePacketaShippingRate} from "./usePacketaShippingRate";
import {useOnWidgetButtonClicked} from "./useOnWidgetButtonClicked";

export const View = ({cart}) => {
    const [viewState, setViewState] = useState(null);

    const {shippingRates} = cart;

    const settings = getSetting( 'packeta-widget_data' );
    const {
        carrierConfig,
        translations,
        logo,
        widgetAutoOpen,
    } = settings;


    const packetaShippingRate = usePacketaShippingRate(shippingRates, carrierConfig);
    const onWidgetButtonClicked = useOnWidgetButtonClicked(setViewState);

    useEffect( () => {
        if (packetaShippingRate && !viewState && widgetAutoOpen ) {
            onWidgetButtonClicked(packetaShippingRate);
        }
    }, [ packetaShippingRate, widgetAutoOpen, onWidgetButtonClicked ] );

    if (!packetaShippingRate) {
        return null;
    }

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
