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

const saveShippingAndPaymentMethods = function ( shipping, payment ) {
	let paymentId = null;
	if ( payment ) {
		paymentId = payment.value;
	} else {
		const paymentMethodInput = document.querySelector( 'input[name="radio-control-wc-payment-method-options"]:checked' );
		if ( paymentMethodInput !== null ) {
			paymentId = paymentMethodInput.value;
		}
	}

	let shippingId = null;
	if ( shipping ) {
		shippingId = shipping.shippingRateId;
	} else {
		let radioInputs = document.querySelectorAll( '.wc-block-components-shipping-rates-control input[type="radio"]' );
		for ( let i = 0; i < radioInputs.length; i++ ) {
			if ( radioInputs[ i ].checked ) {
				shippingId = radioInputs[ i ].value;
				break;
			}
		}
	}

	let data = {};
	if ( shippingId ) {
		data.shipping_method = shippingId;
	}
	if ( paymentId ) {
		data.payment_method = paymentId;
	}

	if ( JSON.stringify( data ) !== '{}' ) {
		extensionCartUpdate( {
			namespace: 'packetery-js-hooks',
			data: data,
		} );
	}
}

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
