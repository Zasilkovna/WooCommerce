<?php

declare(strict_types=1);

namespace Tests\Module\Checkout;

use Packetery\Module\Checkout\ShippingTaxModifier;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use PHPUnit\Framework\TestCase;
use WC_Order_Item_Shipping;

class ShippingTaxModifierTest extends TestCase {

	private WpAdapter|MockObject $wpAdapterMock;
	private OptionsProvider|MockObject $optionsProviderMock;
	private ShippingTaxModifier $shippingTaxModifier;

	protected function setup(): void {
		$this->wpAdapterMock       = $this->createMock( WpAdapter::class );
		$this->optionsProviderMock = $this->createMock( OptionsProvider::class );

		$this->shippingTaxModifier = new ShippingTaxModifier(
			$this->wpAdapterMock,
			$this->optionsProviderMock
		);
	}

	public function testRegister(): void {
		$this->wpAdapterMock
			->expects( $this->once() )
			->method( 'addAction' )
			->with(
				'woocommerce_order_item_shipping_after_calculate_taxes',
				[ $this->shippingTaxModifier, 'afterCalculateTaxes' ]
			);

		$this->shippingTaxModifier->register();
	}

	public function testAfterCalculateTaxesSkipsWhenPricesAreNotTaxInclusive(): void {
		$this->optionsProviderMock
			->method( 'arePricesTaxInclusive' )
			->willReturn( false );

		$shippingItemMock = $this->createMock( WC_Order_Item_Shipping::class );
		$shippingItemMock->expects( $this->never() )->method( 'get_method_id' );

		$this->shippingTaxModifier->afterCalculateTaxes( $shippingItemMock );
	}

	public function testAfterCalculateTaxesSkipsWhenMethodIsNotPacketa(): void {
		$this->optionsProviderMock
			->method( 'arePricesTaxInclusive' )
			->willReturn( true );

		$shippingItemMock = $this->createMock( WC_Order_Item_Shipping::class );
		$shippingItemMock
			->method( 'get_method_id' )
			->willReturn( 'non_packeta_method' );

		$shippingItemMock->expects( $this->never() )->method( 'get_meta' );

		$this->shippingTaxModifier->afterCalculateTaxes( $shippingItemMock );
	}

	public function testAfterCalculateTaxesSkipsWhenPacketaTaxesIsNotArray(): void {
		$this->optionsProviderMock
			->method( 'arePricesTaxInclusive' )
			->willReturn( true );

		$shippingItemMock = $this->createMock( WC_Order_Item_Shipping::class );
		$shippingItemMock
			->method( 'get_method_id' )
			->willReturn( 'packeta_method_zpointcz' );

		$shippingItemMock
			->method( 'get_meta' )
			->with( 'packetaTaxes' )
			->willReturn( '' );

		$shippingItemMock->expects( $this->never() )->method( 'set_taxes' );

		$this->shippingTaxModifier->afterCalculateTaxes( $shippingItemMock );
	}

	public function testAfterCalculateTaxesSetsTaxes(): void {
		$this->optionsProviderMock
			->method( 'arePricesTaxInclusive' )
			->willReturn( true );

		$shippingItemMock = $this->createMock( WC_Order_Item_Shipping::class );
		$shippingItemMock
			->method( 'get_method_id' )
			->willReturn( 'packeta_method_zpointcz' );

		$shippingItemMock
			->method( 'get_meta' )
			->with( 'packetaTaxes' )
			->willReturn( [ 10 ] );

		$shippingItemMock
			->expects( $this->once() )
			->method( 'set_taxes' )
			->with( [ 'total' => [ 10 ] ] );

		$this->shippingTaxModifier->afterCalculateTaxes( $shippingItemMock );
	}
}
