config = {
	map: {
		'*': {
			'Magento_Checkout/js/action/place-order':'Packetery_Checkout/js/view/place-order'
		}
	},
	config: {
		mixins: {
			'Magento_Checkout/js/view/shipping': {
				'Packetery_Checkout/js/view/shipping': true
			}
		}
	}
}
