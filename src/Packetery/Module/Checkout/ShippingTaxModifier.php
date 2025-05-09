<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Shipping\ShippingProvider;
use WC_Order_Item_Shipping;

class ShippingTaxModifier {

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	public function __construct(
		WpAdapter $wpAdapter,
		OptionsProvider $optionsProvider
	) {
		$this->optionsProvider = $optionsProvider;
		$this->wpAdapter       = $wpAdapter;
	}

	public function register(): void {
		$this->wpAdapter->addAction(
			'woocommerce_order_item_shipping_after_calculate_taxes',
			[
				$this,
				'afterCalculateTaxes',
			]
		);
	}

	public function afterCalculateTaxes( WC_Order_Item_Shipping $shippingItem ): void {
		if ( ! $this->optionsProvider->arePricesTaxInclusive() ) {
			return;
		}

		if ( ! ShippingProvider::isPacketaMethod( $shippingItem->get_method_id() ) ) {
			return;
		}

		$taxes = $shippingItem->get_meta( 'packetaTaxes' );
		if ( ! is_array( $taxes ) ) {
			return;
		}

		$shippingItem->set_taxes( [ 'total' => $taxes ] );
	}
}
