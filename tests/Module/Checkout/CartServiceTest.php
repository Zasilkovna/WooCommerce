<?php

declare( strict_types=1 );

namespace Tests\Module\Checkout;

use Exception;
use Packetery\Module\Carrier;
use Packetery\Module\Checkout\CartService;
use Packetery\Module\Exception\ProductNotFoundException;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Product;
use Packetery\Module\Product\ProductEntityFactory;
use Packetery\Module\ProductCategory;
use Packetery\Module\ProductCategory\ProductCategoryEntityFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WC_Product;

class CartServiceTest extends TestCase {
	private CartService|MockObject $cartService;
	private MockObject|WpAdapter $wpAdapter;
	private ProductEntityFactory|MockObject $productEntityFactory;
	private MockObject|WcAdapter $wcAdapter;
	private MockObject|ProductCategoryEntityFactory $productCategoryEntityFactory;
	private MockObject|OptionsProvider $optionsProvider;

	private function createCartServiceMock(): void {
		$this->wcAdapter                    = $this->createMock( WcAdapter::class );
		$this->wpAdapter                    = $this->createMock( WpAdapter::class );
		$this->productEntityFactory         = $this->createMock( ProductEntityFactory::class );
		$this->productCategoryEntityFactory = $this->createMock( ProductCategoryEntityFactory::class );
		$this->optionsProvider              = $this->createMock( OptionsProvider::class );

		$this->cartService = new CartService(
			$this->wpAdapter,
			$this->wcAdapter,
			$this->productEntityFactory,
			$this->productCategoryEntityFactory,
			$this->optionsProvider
		);
	}

	public function testIsAgeVerificationRequiredWithoutWpLoaded(): void {
		$this->createCartServiceMock();
		$this->wpAdapter->method( 'didAction' )->willReturn( 0 );
		$this->assertFalse( $this->cartService->isAgeVerificationRequired() );
	}

	public function testIsAgeVerificationRequiredWithNonPhysicalProduct(): void {
		$this->createCartServiceMock();

		$this->wpAdapter->method( 'didAction' )->willReturn( 1 );
		$this->wcAdapter->method( 'cartGetCartContent' )->willReturn(
			[
				[ 'product_id' => 1 ],
			]
		);

		$productMock = $this->createMock( Product\Entity::class );
		$productMock->method( 'isPhysical' )->willReturn( false );

		$this->productEntityFactory->method( 'fromPostId' )->willReturn( $productMock );
		$this->assertFalse( $this->cartService->isAgeVerificationRequired() );
	}

	public function testIsAgeVerificationRequiredWithVerification(): void {
		$this->createCartServiceMock();

		$this->wpAdapter->method( 'didAction' )->willReturn( 1 );
		$this->wcAdapter->method( 'cartGetCartContent' )->willReturn(
			[
				[ 'product_id' => 1 ],
			]
		);

		$productMock = $this->createMock( Product\Entity::class );
		$productMock->method( 'isPhysical' )->willReturn( true );
		$productMock->method( 'isAgeVerificationRequired' )->willReturn( true );

		$this->productEntityFactory->method( 'fromPostId' )->willReturn( $productMock );
		$this->assertTrue( $this->cartService->isAgeVerificationRequired() );
	}

	public function testIsAgeVerificationRequiredWithoutVerification(): void {
		$this->createCartServiceMock();

		$this->wpAdapter->method( 'didAction' )->willReturn( 1 );
		$this->wcAdapter->method( 'cartGetCartContent' )->willReturn(
			[
				[ 'product_id' => 1 ],
			]
		);

		$productMock = $this->createMock( Product\Entity::class );
		$productMock->method( 'isPhysical' )->willReturn( true );
		$productMock->method( 'isAgeVerificationRequired' )->willReturn( false );

		$this->productEntityFactory->method( 'fromPostId' )->willReturn( $productMock );
		$this->assertFalse( $this->cartService->isAgeVerificationRequired() );
	}

	public function testGetCartWeightKgWithEmptyCart(): void {
		$this->createCartServiceMock();
		$this->wpAdapter->method( 'didAction' )->willReturn( 1 );
		$this->wcAdapter->method( 'cartGetCartContentsWeight' )->willReturn( 0.0 );
		$this->wcAdapter->method( 'getWeight' )->willReturn( 0.0 );
		$this->optionsProvider->method( 'getPackagingWeight' )->willReturn( 0.0 );
		$this->assertSame( 0.0, $this->cartService->getCartWeightKg() );
	}

	public function testGetCartWeightKgWithProductsInCart(): void {
		$this->createCartServiceMock();
		$this->wpAdapter->method( 'didAction' )->willReturn( 1 );
		$this->wcAdapter->method( 'cartGetCartContentsWeight' )->willReturn( 5.0 );
		$this->wcAdapter->method( 'getWeight' )->willReturn( 5.0 );
		$this->optionsProvider->method( 'getPackagingWeight' )->willReturn( 1.0 );
		$this->assertSame( 6.0, $this->cartService->getCartWeightKg() );
	}

	public function testGetCartWeightKgWithoutWpLoaded(): void {
		$this->createCartServiceMock();
		$this->wpAdapter->method( 'didAction' )->willReturn( 0 );
		$this->assertSame( 0.0, $this->cartService->getCartWeightKg() );
	}

	public function testGetCartWeightKgWithExceptionOnGetWeight(): void {
		$this->createCartServiceMock();
		$this->wpAdapter->method( 'didAction' )->willReturn( 1 );
		$this->wcAdapter->method( 'cartGetCartContentsWeight' )->willReturn( 5.0 );
		$this->wcAdapter->method( 'getWeight' )->will( $this->throwException( new Exception() ) );
		$this->expectException( Exception::class );
		$this->cartService->getCartWeightKg();
	}

	public function testGetTotalCartProductValue(): void {
		$this->createCartServiceMock();
		$cartItem1Price    = 10.0;
		$cartItem1Quantity = 2.0;
		$cartItem2Price    = 20.0;
		$cartItem2Quantity = 1.0;
		$item1DataMock     = $this->createMock( WC_Product::class );
		$item1DataMock->method( 'get_price' )->willReturn( $cartItem1Price );
		$item2DataMock = $this->createMock( WC_Product::class );
		$item2DataMock->method( 'get_price' )->willReturn( $cartItem2Price );
		$this->wcAdapter->method( 'cartGetCartContent' )->willReturn(
			[
				[
					'data'     => $item1DataMock,
					'quantity' => $cartItem1Quantity,
				],
				[
					'data'     => $item2DataMock,
					'quantity' => $cartItem2Quantity,
				],
			]
		);
		$totalProductPrice = ( $cartItem1Price * $cartItem1Quantity ) + ( $cartItem2Price * $cartItem2Quantity );
		$this->assertSame( $totalProductPrice, $this->cartService->getTotalCartProductValue() );
	}

	public function testGetDisallowedShippingRateIdsWithPhysicalProduct(): void {
		$this->createCartServiceMock();

		$this->wcAdapter->method( 'cartGetCartContent' )->willReturn(
			[
				[ 'product_id' => 1 ],
			]
		);

		$productMock = $this->createMock( Product\Entity::class );
		$productMock->method( 'isPhysical' )->willReturn( true );
		$productMock->method( 'getDisallowedShippingRateIds' )->willReturn( [ 1, 2, 3 ] );

		$this->productEntityFactory->method( 'fromPostId' )->willReturn( $productMock );
		$this->assertEquals( [ 1, 2, 3 ], $this->cartService->getDisallowedShippingRateIds() );
	}

	public function testGetDisallowedShippingRateIdsWithNonPhysicalProduct(): void {
		$this->createCartServiceMock();

		$this->wcAdapter->method( 'cartGetCartContent' )->willReturn(
			[
				[ 'product_id' => 1 ],
			]
		);

		$productMock = $this->createMock( Product\Entity::class );
		$productMock->method( 'isPhysical' )->willReturn( false );
		$productMock->method( 'getDisallowedShippingRateIds' )->willReturn( [ 1, 2, 3 ] );

		$this->productEntityFactory->method( 'fromPostId' )->willReturn( $productMock );
		$this->assertEquals( [], $this->cartService->getDisallowedShippingRateIds() );
	}

	public function testGetDisallowedShippingRateIdsCombined(): void {
		$this->createCartServiceMock();

		$this->wcAdapter->method( 'cartGetCartContent' )->willReturn(
			[
				[ 'product_id' => 1 ],
				[ 'product_id' => 2 ],
			]
		);

		$productMockPhysical = $this->createMock( Product\Entity::class );
		$productMockPhysical->method( 'isPhysical' )->willReturn( true );
		$productMockPhysical->method( 'getDisallowedShippingRateIds' )->willReturn( [ 1, 2 ] );

		$productMockNonPhysical = $this->createMock( Product\Entity::class );
		$productMockNonPhysical->method( 'isPhysical' )->willReturn( false );
		$productMockNonPhysical->method( 'getDisallowedShippingRateIds' )->willReturn( [ 2, 3 ] );

		$this->productEntityFactory->method( 'fromPostId' )
									->willReturnCallback(
										function ( $id ) use ( $productMockPhysical, $productMockNonPhysical ) {
											if ( $id === 1 ) {
												return $productMockPhysical;
											}
											if ( $id === 2 ) {
												return $productMockNonPhysical;
											}

											return null;
										}
									);
		$this->assertEquals( [ 1, 2 ], $this->cartService->getDisallowedShippingRateIds() );
	}

	public function testGetTaxClassWithMaxRateWithInvalidProduct(): void {
		$this->createCartServiceMock();
		$this->wcAdapter->method( 'cartGetCartContent' )->willReturn(
			[
				[ 'product_id' => 1 ],
			]
		);
		$product = null;
		$this->wcAdapter->expects( $this->once() )
						->method( 'productFactoryGetProduct' )
						->with( $this->equalTo( 1 ) )
						->willReturn( $product );

		$this->expectException( ProductNotFoundException::class );
		$this->cartService->getTaxClassWithMaxRate();
	}

	public function testGetTaxClassWithMaxRateWithNoTaxableProduct(): void {
		$this->createCartServiceMock();
		$this->wcAdapter->method( 'cartGetCartContent' )->willReturn(
			[
				[ 'product_id' => 1 ],
			]
		);
		$product = $this->createMock( WC_Product::class );
		$product->method( 'is_taxable' )->willReturn( false );
		$this->wcAdapter->expects( $this->once() )
						->method( 'productFactoryGetProduct' )
						->with( $this->equalTo( 1 ) )
						->willReturn( $product );
		$this->assertNull( $this->cartService->getTaxClassWithMaxRate() );
	}

	public function testGetTaxClassWithMaxRateWithSingleTaxableProduct(): void {
		$this->createCartServiceMock();
		$this->wcAdapter->method( 'cartGetCartContent' )->willReturn(
			[
				[ 'product_id' => 1 ],
			]
		);
		$product = $this->createMock( WC_Product::class );
		$product->method( 'is_taxable' )->willReturn( true );
		$product->method( 'get_tax_class' )->willReturn( 'standard' );
		$this->wcAdapter->expects( $this->once() )
						->method( 'productFactoryGetProduct' )
						->with( $this->equalTo( 1 ) )
						->willReturn( $product );
		$this->assertEquals( 'standard', $this->cartService->getTaxClassWithMaxRate() );
	}

	public function testGetTaxClassWithMaxRateWithMultipleTaxClasses(): void {
		$this->createCartServiceMock();
		$this->wcAdapter->method( 'cartGetCartContent' )->willReturn(
			[
				[ 'product_id' => 1 ],
				[ 'product_id' => 2 ],
			]
		);
		$product1 = $this->createMock( WC_Product::class );
		$product1->method( 'is_taxable' )->willReturn( true );
		$product1->method( 'get_tax_class' )->willReturn( 'reduced' );
		$product2 = $this->createMock( WC_Product::class );
		$product2->method( 'is_taxable' )->willReturn( true );
		$product2->method( 'get_tax_class' )->willReturn( 'standard' );

		$index = 0;
		$this->wcAdapter->expects( $this->exactly( 2 ) )
						->method( 'productFactoryGetProduct' )
						->with(
							$this->callback(
								function ( $productId ) {
									return in_array( $productId, [ 1, 2 ], true );
								}
							)
						)
						->willReturnCallback(
							function ( $productId ) use ( $product1, $product2, &$index ) {
								$index++;
								if ( $productId === 1 && $index === 1 ) {
									return $product1;
								}

								if ( $productId === 2 && $index === 2 ) {
									return $product2;
								}

								return null;
							}
						);

		$this->wcAdapter->method( 'taxGetRates' )
						->willReturnOnConsecutiveCalls(
							[
								'1' => [
									'rate'     => '10.00',
									'label'    => 'Reduced Rate',
									'shipping' => 'no',
									'compound' => 'no',
								],
							],
							[
								'1' => [
									'rate'     => '20.00',
									'label'    => 'Standard Rate',
									'shipping' => 'no',
									'compound' => 'no',
								],
							]
						);
		$this->assertEquals( 'standard', $this->cartService->getTaxClassWithMaxRate() );
	}

	public function testGetTaxClassWithMaxRateWithMultipleTaxClassesNoRates(): void {
		$this->createCartServiceMock();
		$this->wcAdapter->method( 'cartGetCartContent' )->willReturn(
			[
				[ 'product_id' => 1 ],
				[ 'product_id' => 2 ],
			]
		);
		$product1 = $this->createMock( WC_Product::class );
		$product1->method( 'is_taxable' )->willReturn( true );
		$product1->method( 'get_tax_class' )->willReturn( 'reduced' );
		$product2 = $this->createMock( WC_Product::class );
		$product2->method( 'is_taxable' )->willReturn( true );
		$product2->method( 'get_tax_class' )->willReturn( 'standard' );

		$index = 0;
		$this->wcAdapter->expects( $this->exactly( 2 ) )
						->method( 'productFactoryGetProduct' )
						->with(
							$this->callback(
								function ( $productId ) {
									return in_array( $productId, [ 1, 2 ], true );
								}
							)
						)
						->willReturnCallback(
							function ( $productId ) use ( $product1, $product2, &$index ) {
								$index++;
								if ( $productId === 1 && $index === 1 ) {
									return $product1;
								}

								if ( $productId === 2 && $index === 2 ) {
									return $product2;
								}

								return null;
							}
						);

		$this->wcAdapter->method( 'taxGetRates' )->willReturn( [] );
		$this->assertNull( $this->cartService->getTaxClassWithMaxRate() );
	}

	public function testGetCartContentsTotalIncludingTaxWithNoTaxes(): void {
		$this->createCartServiceMock();

		$this->wcAdapter->method( 'cartGetCartContentsTotal' )->willReturn( 15.00 );
		$this->wcAdapter->method( 'cartGetCartContentsTax' )->willReturn( 0.00 );

		$this->assertSame( 15.00, $this->cartService->getCartContentsTotalIncludingTax() );
	}

	public function testGetCartContentsTotalIncludingTaxWithTaxes(): void {
		$this->createCartServiceMock();

		$this->wcAdapter->method( 'cartGetCartContentsTotal' )->willReturn( 15.00 );
		$this->wcAdapter->method( 'cartGetCartContentsTax' )->willReturn( 5.00 );

		$this->assertSame( 20.00, $this->cartService->getCartContentsTotalIncludingTax() );
	}

	public function testIsShippingRateRestrictedByProductsCategoryWithInvalidProduct(): void {
		$this->createCartServiceMock();

		$this->wcAdapter->method( 'productFactoryGetProduct' )->willReturn( null );

		$this->expectException( ProductNotFoundException::class );
		$this->cartService->isShippingRateRestrictedByProductsCategory( 'testRate', [ [ 'product_id' => 1 ] ] );
	}

	public function testIsShippingRateRestrictedByProductsCategoryWithProductWithoutId(): void {
		$this->createCartServiceMock();

		$this->assertFalse( $this->cartService->isShippingRateRestrictedByProductsCategory( 'testRate', [ [ 'quantity' => 1.0 ] ] ) );
	}

	public function testIsShippingRateRestrictedByProductsCategoryWithNoProductCategories(): void {
		$this->createCartServiceMock();
		$this->wcAdapter->method( 'productFactoryGetProduct' )->willReturn( $this->createMock( WC_Product::class ) );
		$this->assertFalse( $this->cartService->isShippingRateRestrictedByProductsCategory( 'testRate', [] ) );
	}

	public function testIsShippingRateRestrictedByProductsCategoryWithProductNotInCategories(): void {
		$this->createCartServiceMock();

		$productMock = $this->createMock( WC_Product::class );
		$productMock->method( 'get_category_ids' )->willReturn( [ 1, 2 ] );
		$this->wcAdapter->method( 'productFactoryGetProduct' )->willReturn( $productMock );

		$productCategoryEntityMock = $this->createMock( ProductCategory\Entity::class );
		$productCategoryEntityMock->method( 'getDisallowedShippingRateIds' )->willReturn( [ 'rate1', 'rate2' ] );
		$this->productCategoryEntityFactory->method( 'fromTermId' )->willReturn( $productCategoryEntityMock );

		$this->assertFalse( $this->cartService->isShippingRateRestrictedByProductsCategory( 'testRate', [ [ 'product_id' => 1 ] ] ) );
	}

	public function testIsShippingRateRestrictedByProductsCategoryWithProductInRestrictedCategories(): void {
		$this->createCartServiceMock();

		$productMock = $this->createMock( WC_Product::class );
		$productMock->method( 'get_category_ids' )->willReturn( [ 1, 2 ] );
		$this->wcAdapter->method( 'productFactoryGetProduct' )->willReturn( $productMock );

		$productCategoryEntityMock = $this->createMock( ProductCategory\Entity::class );
		$productCategoryEntityMock->method( 'getDisallowedShippingRateIds' )->willReturn( [ 'rate1', 'testRate' ] );
		$this->productCategoryEntityFactory->method( 'fromTermId' )->willReturn( $productCategoryEntityMock );

		$this->assertTrue( $this->cartService->isShippingRateRestrictedByProductsCategory( 'testRate', [ [ 'product_id' => 1 ] ] ) );
	}

	public function testGetBiggestProductSizeBySum(): void {
		$this->createCartServiceMock();
		$productMock1 = $this->createMock( Product\Entity::class );
		$productMock1->method( 'isPhysical' )->willReturn( true );
		$productMock1->method( 'getLengthInCm' )->willReturn( 15.5 );
		$productMock1->method( 'getWidthInCm' )->willReturn( 25.2 );
		$productMock1->method( 'getHeightInCm' )->willReturn( 10.7 );

		$productMock2 = $this->createMock( Product\Entity::class );
		$productMock2->method( 'isPhysical' )->willReturn( true );
		$productMock2->method( 'getLengthInCm' )->willReturn( 35.8 );
		$productMock2->method( 'getWidthInCm' )->willReturn( 20.1 );
		$productMock2->method( 'getHeightInCm' )->willReturn( 5.4 );

		$this->wcAdapter->method( 'cartGetCartContent' )->willReturn(
			[
				[ 'product_id' => 1 ],
				[ 'product_id' => 2 ],
			]
		);

		$this->wcAdapter->method( 'cartGetCartContent' )->willReturn(
			[
				[ 'product_id' => 1 ],
				[ 'product_id' => 2 ],
			]
		);

		$this->productEntityFactory
			->method( 'fromPostId' )
			->willReturnOnConsecutiveCalls( $productMock1, $productMock2 );

		$this->wpAdapter->method( 'didAction' )->willReturn( 1 );

		$result = $this->cartService->getBiggestProductSize( CartService::CRITERIA_BY_SUM );
		$this->assertEquals(
			[
				'length' => 35.8,
				'width'  => 20.1,
				'depth'  => 5.4,
			],
			$result
		);
	}

	public static function cartContainsProductOversizedForCarrier(): array {
		return [
			'all-ok'                            => [
				'sizeRestrictions'   => [
					'maximum_length' => 100,
					'dimensions_sum' => 180,
					'length'         => 100,
					'width'          => 50,
					'height'         => 30,
				],
				'productDimensions1' => [ 80.0, 40.0, 30.0 ],
				'productDimensions2' => [ 30.0, 40.0, 80.0 ],
				'expectedResult'     => false,
			],
			'size-exceeds'                      => [
				'sizeRestrictions'   => [
					'length' => 100,
					'width'  => 50,
					'height' => 30,
				],
				'productDimensions1' => [ 110.0, 40.0, 30.0 ],
				'productDimensions2' => [ 80.0, 40.0, 30.0 ],
				'expectedResult'     => true,
			],
			'size-exceeds-v2'                   => [
				'sizeRestrictions'   => [
					'length' => 100,
					'width'  => 50,
					'height' => 30,
				],
				'productDimensions1' => [ 80.0, 40.0, 30.0 ],
				'productDimensions2' => [ 10.0, 40.0, 110.0 ],
				'expectedResult'     => true,
			],
			'sum-exceeds'                       => [
				'sizeRestrictions'   => [
					'maximum_length' => 100,
					'dimensions_sum' => 180,
				],
				'productDimensions1' => [ 80.0, 80.0, 80.0 ],
				'productDimensions2' => [ 80.0, 40.0, 30.0 ],
				'expectedResult'     => true,
			],
			'length-exceeds'                    => [
				'sizeRestrictions'   => [
					'maximum_length' => 100,
					'dimensions_sum' => 180,
				],
				'productDimensions1' => [ 110.0, 40.0, 30.0 ],
				'productDimensions2' => [ 80.0, 40.0, 30.0 ],
				'expectedResult'     => true,
			],
			'length-exceeds-by-unclean-numeric' => [
				'sizeRestrictions'   => [
					'maximum_length' => ' 100',
					'dimensions_sum' => '180 ',
				],
				'productDimensions1' => [ 110.0, 40.0, 30.0 ],
				'productDimensions2' => [ 80.0, 40.0, 30.0 ],
				'expectedResult'     => true,
			],
			'empty-maximum-length'              => [
				'sizeRestrictions'   => [
					'maximum_length' => '',
				],
				'productDimensions1' => [ 110.0, 40.0, 30.0 ],
				'productDimensions2' => [ 80.0, 40.0, 30.0 ],
				'expectedResult'     => false,
			],
			'empty-maximum-length-with-other'   => [
				'sizeRestrictions'   => [
					'maximum_length' => '',
					'dimensions_sum' => 180,
				],
				'productDimensions1' => [ 10.0, 10.0, 10.0 ],
				'productDimensions2' => [ 8.0, 4.0, 5.0 ],
				'expectedResult'     => false,
			],
			'empty-dimensions-sum'              => [
				'sizeRestrictions'   => [
					'dimensions_sum' => '',
				],
				'productDimensions1' => [ 110.0, 40.0, 30.0 ],
				'productDimensions2' => [ 80.0, 40.0, 30.0 ],
				'expectedResult'     => false,
			],
			'empty-dimensions-sum-with-other'   => [
				'sizeRestrictions'   => [
					'maximum_length' => 100,
					'dimensions_sum' => '',
				],
				'productDimensions1' => [ 100.0, 40.0, 30.0 ],
				'productDimensions2' => [ 80.0, 40.0, 30.0 ],
				'expectedResult'     => false,
			],
			'empty-restrictions-hd'             => [
				'sizeRestrictions'   => [
					'maximum_length' => '',
					'dimensions_sum' => '',
				],
				'productDimensions1' => [ 110.0, 40.0, 30.0 ],
				'productDimensions2' => [ 80.0, 40.0, 30.0 ],
				'expectedResult'     => false,
			],
			'empty-restrictions-zbox'           => [
				'sizeRestrictions'   => [
					'length' => '',
					'width'  => '',
					'height' => '',
				],
				'productDimensions1' => [ 110.0, 40.0, 30.0 ],
				'productDimensions2' => [ 80.0, 40.0, 30.0 ],
				'expectedResult'     => false,
			],
			'semi-empty-restrictions-zbox'      => [
				'sizeRestrictions'   => [
					'length' => 100,
					'width'  => '',
					'height' => '',
				],
				'productDimensions1' => [ 110.0, 40.0, 30.0 ],
				'productDimensions2' => [ 80.0, 40.0, 30.0 ],
				'expectedResult'     => false,
			],
			'length-exceeds-v2'                 => [
				'sizeRestrictions'   => [
					'maximum_length' => 100,
					'dimensions_sum' => 180,
				],
				'productDimensions1' => [ 80.0, 40.0, 30.0 ],
				'productDimensions2' => [ 10.0, 40.0, 110.0 ],
				'expectedResult'     => true,
			],
		];
	}

	/**
	 * @dataProvider cartContainsProductOversizedForCarrier
	 */
	public function testCartContainsProductOversizedForCarrier(
		array $sizeRestrictions,
		array $productDimensions1,
		array $productDimensions2,
		bool $expectedResult,
	): void {
		$this->createCartServiceMock();
		$carrierOptionsMock = $this->createMock( Carrier\Options::class );
		$carrierOptionsMock->method( 'getSizeRestrictions' )->willReturn( $sizeRestrictions );

		$productMock1 = $this->createMock( Product\Entity::class );
		$productMock1->method( 'isPhysical' )->willReturn( true );
		$productMock1->method( 'getLengthInCm' )->willReturn( $productDimensions1[0] );
		$productMock1->method( 'getWidthInCm' )->willReturn( $productDimensions1[1] );
		$productMock1->method( 'getHeightInCm' )->willReturn( $productDimensions1[2] );

		$productMock2 = $this->createMock( Product\Entity::class );
		$productMock2->method( 'isPhysical' )->willReturn( true );
		$productMock2->method( 'getLengthInCm' )->willReturn( $productDimensions2[0] );
		$productMock2->method( 'getWidthInCm' )->willReturn( $productDimensions2[1] );
		$productMock2->method( 'getHeightInCm' )->willReturn( $productDimensions2[2] );

		$this->wcAdapter->method( 'cartGetCartContent' )->willReturn(
			[
				[ 'product_id' => 1 ],
				[ 'product_id' => 2 ],
			]
		);
		$this->productEntityFactory
			->method( 'fromPostId' )
			->willReturnOnConsecutiveCalls( $productMock1, $productMock2, $productMock1, $productMock2 );

		$this->wpAdapter->method( 'didAction' )->willReturn( 1 );

		$result = $this->cartService->cartContainsProductOversizedForCarrier( $carrierOptionsMock );
		$this->assertSame( $result, $expectedResult );
	}
}
