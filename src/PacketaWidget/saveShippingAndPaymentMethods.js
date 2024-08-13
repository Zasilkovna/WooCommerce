
const { extensionCartUpdate } = wc.blocksCheckout;

export const saveShippingAndPaymentMethods = function ( shipping, payment ) {
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
