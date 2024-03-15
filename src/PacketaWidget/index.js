import { __ } from "@wordpress/i18n";
import { registerPlugin } from "@wordpress/plugins";
import { ExperimentalOrderShippingPackages } from "@woocommerce/blocks-checkout";

import {PacketaWidget} from "./PacketaWidget";

const render = () => {
    console.log('render');
    return (
        <ExperimentalOrderShippingPackages>
            <PacketaWidget />
        </ExperimentalOrderShippingPackages>
    );
}

registerPlugin('packeta-widget', {
    render,
    scope: 'woocommerce-checkout',
});
