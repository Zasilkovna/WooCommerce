import { __ } from "@wordpress/i18n";
import { getSetting } from '@woocommerce/settings';

export const View = ({cart}) => {
    // TODO: properly load
    jQuery.getScript( "https://widget.packeta.com/v6/www/js/library.js" )

    const {shippingRates} = cart;
    const {
        carrierConfig: packetaWidgetCarrierConfig,
        country,
        language,
        packeteryApiKey,
        appIdentity,
        weight,
        isAgeVerificationRequired,
    } = getSetting( 'packeta-widget_data' );

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

        Packeta.Widget.pick( packeteryApiKey, ( pickupPoint ) => {
            console.log( pickupPoint );

            if ( pickupPoint == null ) {
                return;
            }

        }, widgetOptions );
    };

    console.log('rendered');
    return <tr className="packetery-widget-button-table-row">
        <th>
            <img className="packetery-widget-button-logo" src="/wp-content/plugins/packeta/public/packeta-symbol.png" alt="packeta" width="100" />
            <a onClick={ onWidgetButtonClicked }>{ __( 'Choose pickup point', 'packeta' ) }</a>
        </th>
    </tr>
}
