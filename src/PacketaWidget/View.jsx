import { getSetting } from '@woocommerce/settings';

export const View = ({cart}) => {
    const {shippingRates} = cart;
    const {carrierConfig: packetaWidgetCarrierConfig} = getSetting('packeta-widget_data');

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

    console.log('rendered');
    return <tr className="packetery-widget-button-table-row">
        <th>
            <img className="packetery-widget-button-logo" src="/wp-content/plugins/packeta/public/packeta-symbol.png" alt="packeta" width="100" />
        </th>
    </tr>
}
