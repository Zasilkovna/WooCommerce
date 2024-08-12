/**
 * Block registration.
 *
 * @package Packetery
 */

import { registerBlockType } from '@wordpress/blocks';
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';

import metadata from './block.json';
import { Edit } from './Edit';
import { View } from './View';
import { __ } from '@wordpress/i18n';
import { saveShippingAndPaymentMethods } from './saveShippingAndPaymentMethods';

registerBlockType( metadata, {
	title: __( 'title', 'packeta' ),
	description: __( 'description', 'packeta' ),
	edit: Edit,
} );

registerCheckoutBlock( {
	metadata,
	component: View,
} );

const { extensionCartUpdate } = wc.blocksCheckout;

wp.hooks.addAction( 'experimental__woocommerce_blocks-checkout-set-selected-shipping-rate', 'packetery-js-hooks', function ( shipping ) {
	saveShippingAndPaymentMethods( shipping, null );
} );

wp.hooks.addAction( 'experimental__woocommerce_blocks-checkout-set-active-payment-method', 'packetery-js-hooks', function ( payment ) {
	saveShippingAndPaymentMethods( null, payment );
} );

// Used to clear values upon checkout page reload. It is needed to save something.
wp.hooks.addAction( 'experimental__woocommerce_blocks-checkout-render-checkout-form', 'packetery-js-hooks', function ( form ) {
	extensionCartUpdate( {
		namespace: 'packetery-js-hooks',
		data: {
			shipping_method: 'n/a',
			payment_method: 'n/a',
		},
	} );
} );

wp.hooks.addAction( 'packetery_save_shipping_and_payment_methods', 'packetery-js-hooks', function ( shippingRateId, paymentId ) {
	const shippingObject = shippingRateId ? { shippingRateId: shippingRateId } : null;
	const paymentObject = paymentId !== '' ? { value: paymentId } : null;
	saveShippingAndPaymentMethods( shippingObject, paymentObject );
} );
