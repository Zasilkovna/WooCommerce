config = {
	config: {
		mixins: {
			'Magento_Checkout/js/view/shipping': {
				'Packetery_Checkout/js/view/shipping': true
			},
            'Magento_Checkout/js/model/place-order': {
                'Packetery_Checkout/js/model/place-order-mixin': true
            }
		}
	}
}
