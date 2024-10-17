<?php

declare( strict_types=1 );

namespace Tests\Module;

use Packetery\Latte\Engine;
use Packetery\Module\Api;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Checkout;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Order;
use Packetery\Module\Order\PickupPointValidator;
use Packetery\Module\Payment\PaymentHelper;
use Packetery\Module\Product;
use Packetery\Module\Product\ProductEntityFactory;
use Packetery\Module\ProductCategory;
use Packetery\Module\ProductCategory\ProductCategoryEntityFactory;
use Packetery\Module\RateCalculator;
use Packetery\Module\WidgetOptionsBuilder;
use Packetery\Nette\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class CheckoutTest extends TestCase {

	private WpAdapter|MockObject $wpAdapter;
	private WpAdapter|MockObject $wcAdapter;
	private WpAdapter|MockObject $productEntityFactory;
	private WpAdapter|MockObject $productCategoryEntityFactory;
	private WpAdapter|MockObject $carrierOptionsFactory;
	private WpAdapter|MockObject $currencySwitcherFacade;
	private WpAdapter|MockObject $carrierEntityRepository;
	private WpAdapter|MockObject $carDeliveryConfig;
	private WpAdapter|MockObject $provider;

	private Checkout $checkout;

	private function createCheckoutMock(): void {
		$this->wpAdapter = MockFactory::createWpAdapter( $this );
		$this->wcAdapter = $this->createMock( WcAdapter::class );
		$this->productEntityFactory = $this->createMock( ProductEntityFactory::class );
		$this->productCategoryEntityFactory = $this->createMock( ProductCategoryEntityFactory::class );
		$this->carrierOptionsFactory = $this->createMock( CarrierOptionsFactory::class );
		$this->currencySwitcherFacade = MockFactory::createCurrencySwitcherFacade( $this );
		$this->carrierEntityRepository = $this->createMock( Carrier\EntityRepository::class );
		$this->carDeliveryConfig = $this->createMock( CarDeliveryConfig::class );
		$this->provider = $this->createMock( OptionsProvider::class );

		$this->checkout = new Checkout(
			$this->wpAdapter,
			$this->wcAdapter,
			$this->productEntityFactory,
			$this->productCategoryEntityFactory,
			$this->carrierOptionsFactory,
			$this->createMock( Engine::class ),
			$this->provider,
			$this->createMock( Carrier\Repository::class ),
			$this->createMock( Request::class ),
			$this->createMock( Order\Repository::class ),
			$this->currencySwitcherFacade,
			$this->createMock( Order\PacketAutoSubmitter::class ),
			$this->createMock( PickupPointValidator::class ),
			$this->createMock( Order\AttributeMapper::class ),
			new RateCalculator( $this->wpAdapter, $this->currencySwitcherFacade ),
			$this->createMock( PacketaPickupPointsConfig::class ),
			$this->createMock( WidgetOptionsBuilder::class ),
			$this->carrierEntityRepository,
			$this->createMock( Api\Internal\CheckoutRouter::class ),
			$this->carDeliveryConfig,
			$this->createMock(PaymentHelper::class),
		);

	}

	public static function rateCreationDataProvider(): array {
		return [
			'configured carriers must be present in rates'            =>
				[
					'expectedRateCount'                  => 2,
					'carriers'                           =>
						[
							DummyFactory::createCarrierCzechPp(),
							DummyFactory::createCarrierCzechHdRequiresSize(),
						],
					'carriersOptions'                    =>
						[
							'packetery_carrier_zpoint-cz' =>
								[
									'id'                  => 'zpoint-cz',
									'name'                => 'zpoint-cz',
									'active'              => true,
									'free_shipping_limit' => null,
									'weight_limits'       =>
										[
											[
												'weight' => 20.0,
												'price'  => 234.34,
											],
										],
								],
							'packetery_carrier_106'       =>
								[
									'id'                  => '106',
									'name'                => 'hd-cz',
									'active'              => true,
									'free_shipping_limit' => null,
									'weight_limits'       =>
										[
											[
												'weight' => 5.0,
												'price'  => 444.34,
											],
										],
								],
						],
					'productDisallowedRateIds'           => [],
					'productCategoryDisallowedRateIds'   => [],
					'allowedCarrierNames'                =>
						[
							'zpoint-cz' => 'zpoint-cz',
							106         => 'hd-cz',
						],
					'isCarDeliveryEnabled'               => true,
					'isAgeVerificationRequiredByProduct' => false,
					'cartWeight'                         => 5.0,
				],
			'car delivery carrier must not be present in rates'       =>
				[
					'expectedRateCount'                  => 1,
					'carriers'                           =>
						[
							DummyFactory::createCarrierCzechPp(),
							DummyFactory::createCarDeliveryCarrier(),
						],
					'carriersOptions'                    =>
						[
							'packetery_carrier_zpoint-cz' =>
								[
									'id'                  => 'zpoint-cz',
									'name'                => 'zpoint-cz',
									'active'              => true,
									'free_shipping_limit' => null,
									'weight_limits'       =>
										[
											[
												'weight' => 20.0,
												'price'  => 234.34,
											],
										],
								],
							'packetery_carrier_25061'     =>
								[
									'id'                  => '25061',
									'name'                => 'CZ Zásilkovna do auta',
									'active'              => true,
									'free_shipping_limit' => null,
									'weight_limits'       =>
										[
											[
												'weight' => 5.0,
												'price'  => 444.34,
											],
										],
								],
						],
					'productDisallowedRateIds'           => [],
					'productCategoryDisallowedRateIds'   => [],
					'allowedCarrierNames'                => null,
					'isCarDeliveryEnabled'               => false,
					'isAgeVerificationRequiredByProduct' => false,
					'cartWeight'                         => 1.0,
				],
			'only one carrier is active'                              =>
				[
					'expectedRateCount'                  => 1,
					'carriers'                           =>
						[
							DummyFactory::createCarrierCzechPp(),
							DummyFactory::createCarrierCzechHdRequiresSize(),
						],
					'carriersOptions'                    =>
						[
							'packetery_carrier_zpoint-cz' =>
								[
									'id'                  => 'zpoint-cz',
									'name'                => 'zpoint-cz',
									'active'              => true,
									'free_shipping_limit' => null,
									'weight_limits'       =>
										[
											[
												'weight' => 20.0,
												'price'  => 234.34,
											],
										],
								],
							'packetery_carrier_106'       =>
								[
									'id'                  => '106',
									'name'                => 'hd-cz',
									'active'              => false,
									'free_shipping_limit' => null,
									'weight_limits'       =>
										[
											[
												'weight' => 5.0,
												'price'  => 444.34,
											],
										],
								],
						],
					'productDisallowedRateIds'           => [],
					'productCategoryDisallowedRateIds'   => [],
					'allowedCarrierNames'                => null,
					'isCarDeliveryEnabled'               => true,
					'isAgeVerificationRequiredByProduct' => false,
					'cartWeight'                         => 1.0,
				],
			'carrier not supporting over-weight cart must be omitted' =>
				[
					'expectedRateCount'                  => 0,
					'carriers'                           =>
						[
							DummyFactory::createCarrierCzechPp(),
						],
					'carriersOptions'                    =>
						[
							'packetery_carrier_zpoint-cz' =>
								[
									'id'                  => 'zpoint-cz',
									'name'                => 'zpoint-cz',
									'active'              => true,
									'free_shipping_limit' => null,
									'weight_limits'       =>
										[
											[
												'weight' => 20.0,
												'price'  => 100.0,
											],
										],
								],
						],
					'productDisallowedRateIds'           => [],
					'productCategoryDisallowedRateIds'   => [],
					'allowedCarrierNames'                =>
						[
							'zpoint-cz' => 'zpoint-cz',
						],
					'isCarDeliveryEnabled'               => true,
					'isAgeVerificationRequiredByProduct' => false,
					'cartWeight'                         => 21.0,
				],
			'inactive carrier must be omitted'                        =>
				[
					'expectedRateCount'                  => 0,
					'carriers'                           =>
						[
							DummyFactory::createCarrierCzechPp(),
						],
					'carriersOptions'                    =>
						[
							'packetery_carrier_zpoint-cz' =>
								[
									'id'                  => 'zpoint-cz',
									'name'                => 'zpoint-cz',
									'active'              => false,
									'free_shipping_limit' => null,
									'weight_limits'       =>
										[
											[
												'weight' => 20.0,
												'price'  => 100.0,
											],
										],
								],
						],
					'productDisallowedRateIds'           => [],
					'productCategoryDisallowedRateIds'   => [],
					'allowedCarrierNames'                => [],
					'isCarDeliveryEnabled'               => true,
					'isAgeVerificationRequiredByProduct' => false,
					'cartWeight'                         => 1.0,
				],
			'carrier disallowed by product must be omitted'           =>
				[
					'expectedRateCount'                  => 0,
					'carriers'                           =>
						[
							DummyFactory::createCarrierCzechPp(),
						],
					'carriersOptions'                    =>
						[
							'packetery_carrier_zpoint-cz' =>
								[
									'id'     => 'zpoint-cz',
									'name'   => 'zpoint-cz',
									'active' => true,
								],
						],
					'productDisallowedRateIds'           =>
						[
							0 => 'packetery_carrier_zpoint-cz',
						],
					'productCategoryDisallowedRateIds'   => [],
					'allowedCarrierNames'                =>
						[
							'zpoint-cz' => 'zpoint-cz',
						],
					'isCarDeliveryEnabled'               => true,
					'isAgeVerificationRequiredByProduct' => false,
					'cartWeight'                         => 1.0,
				],
			'carrier disallowed by product category must be omitted'  =>
				[
					'expectedRateCount'                  => 0,
					'carriers'                           =>
						[
							DummyFactory::createCarrierCzechPp(),
						],
					'carriersOptions'                    =>
						[
							'packetery_carrier_zpoint-cz' =>
								[
									'id'     => 'zpoint-cz',
									'name'   => 'zpoint-cz',
									'active' => true,
								],
						],
					'productDisallowedRateIds'           => [],
					'productCategoryDisallowedRateIds'   =>
						[
							0 => 'packetery_carrier_zpoint-cz',
						],
					'allowedCarrierNames'                =>
						[
							'zpoint-cz' => 'zpoint-cz',
						],
					'isCarDeliveryEnabled'               => true,
					'isAgeVerificationRequiredByProduct' => false,
					'cartWeight'                         => 1.0,
				],
			'car delivery carriers must be supported'                 =>
				[
					'expectedRateCount'                  => 1,
					'carriers'                           =>
						[
							DummyFactory::createCarDeliveryCarrier(),
						],
					'carriersOptions'                    =>
						[
							'packetery_carrier_25061' =>
								[
									'id'                  => '25061',
									'name'                => 'CZ Zásilkovna do auta',
									'active'              => true,
									'free_shipping_limit' => null,
									'weight_limits'       =>
										[
											[
												'weight' => 20.0,
												'price'  => 234.34,
											],
										],
								],
						],
					'productDisallowedRateIds'           => [],
					'productCategoryDisallowedRateIds'   => [],
					'allowedCarrierNames'                =>
						[
							25061 => 'CZ Zásilkovna do auta',
						],
					'isCarDeliveryEnabled'               => true,
					'isAgeVerificationRequiredByProduct' => false,
					'cartWeight'                         => 1.0,
				],
			'carrier not supporting age verification must be omitted' =>
				[
					'expectedRateCount'                  => 0,
					'carriers'                           =>
						[
							DummyFactory::createCarDeliveryCarrier(),
						],
					'carriersOptions'                    =>
						[
							'packetery_carrier_25061' =>
								[
									'id'                  => '25061',
									'name'                => 'CZ Zásilkovna do auta',
									'active'              => true,
									'free_shipping_limit' => null,
									'weight_limits'       =>
										[
											[
												'weight' => 20.0,
												'price'  => 234.34,
											],
										],
								],
						],
					'productDisallowedRateIds'           => [],
					'productCategoryDisallowedRateIds'   => [],
					'allowedCarrierNames'                =>
						[
							25061 => 'CZ Zásilkovna do auta',
						],
					'isCarDeliveryEnabled'               => true,
					'isAgeVerificationRequiredByProduct' => true,
					'cartWeight'                         => 1.0,
				],
			'allowed carrier names argument must support null'        =>
				[
					'expectedRateCount'                  => 1,
					'carriers'                           =>
						[
							DummyFactory::createCarDeliveryCarrier(),
						],
					'carriersOptions'                    =>
						[
							'packetery_carrier_25061' =>
								[
									'id'                  => '25061',
									'name'                => 'CZ Zásilkovna do auta',
									'active'              => true,
									'free_shipping_limit' => null,
									'weight_limits'       =>
										[
											[
												'weight' => 20.0,
												'price'  => 234.34,
											],
										],
								],
						],
					'productDisallowedRateIds'           => [],
					'productCategoryDisallowedRateIds'   => [],
					'allowedCarrierNames'                => null,
					'isCarDeliveryEnabled'               => true,
					'isAgeVerificationRequiredByProduct' => false,
					'cartWeight'                         => 1.0,
				],
		];
	}

	/**
	 * @dataProvider rateCreationDataProvider
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testGetShippingRates(
		int $expectedRateCount,
		array $carriers,
		array $carriersOptions,
		array $productDisallowedRateIds,
		array $productCategoryDisallowedRateIds,
		?array $allowedCarrierNames,
		bool $isCarDeliveryEnabled,
		bool $isAgeVerificationRequiredByProduct,
		float $cartWeightKg
	): void {
		$this->createCheckoutMock();

		$this->wpAdapter
			->expects( self::atLeast( $expectedRateCount ) )
			->method( 'applyFilters' );
		$this->wpAdapter
			->method( 'didAction' )
			->willReturn( 1 );

		$cart = $this->createMock( \WC_Cart::class );
		$cart
			->method( 'get_coupons' )
			->willReturn( [] );
		$this->wcAdapter
			->method( 'cart' )
			->willReturn( $cart );

		$wcProduct = $this->createMock( \WC_Product::class );
		$wcProduct
			->method( 'get_price' )
			->willReturn( '100.00' );
		$wcProduct
			->method( 'get_category_ids' )
			->willReturn( [ 1 ] );
		$this->wcAdapter
			->method( 'productFactoryGetProduct' )
			->willReturn( $wcProduct );

		$cartItem = [
			'product_id' => 1,
			'quantity'   => 1,
			'data'       => $wcProduct,
		];
		$this->wcAdapter
			->method( 'cartGetCartContents' )
			->willReturn( [ $cartItem ] );
		$this->wcAdapter
			->method( 'cartGetCartContent' )
			->willReturn( [ $cartItem ] );

		$this->wcAdapter
			->method( 'customerGetShippingCountry' )
			->willReturn( 'cz' );
		$this->wcAdapter
			->method( 'cartGetCartContentsTotal' )
			->willReturn( 100.0 );
		$this->wcAdapter
			->method( 'cartGetCartContentsTax' )
			->willReturn( 21.0 );
		$this->wcAdapter
			->method( 'cartGetCartContentsWeight' )
			->willReturn( $cartWeightKg );
		$this->wcAdapter
			->method( 'getWeight' )
			->willReturn( $cartWeightKg );

		$productEntity = $this->createMock( Product\Entity::class );
		$productEntity
			->method( 'isPhysical' )
			->willReturn( true );
		$productEntity
			->method( 'isAgeVerification18PlusRequired' )
			->willReturn( $isAgeVerificationRequiredByProduct );
		$productEntity
			->method( 'getDisallowedShippingRateIds' )
			->willReturn( array_merge( [ Carrier\OptionPrefixer::getOptionId( 'anyDisallowedOnProduct' ) ], $productDisallowedRateIds ) );
		$this->productEntityFactory
			->method( 'fromPostId' )
			->willReturn( $productEntity );

		$productCategory = $this->createMock( ProductCategory\Entity::class );
		$productCategory
			->method( 'getDisallowedShippingRateIds' )
			->willReturn( array_merge( [ Carrier\OptionPrefixer::getOptionId( 'anyDisallowedByProductCategory' ) ], $productCategoryDisallowedRateIds ) );
		$this->productCategoryEntityFactory = $this->createMock( ProductCategoryEntityFactory::class );
		$this->productCategoryEntityFactory
			->method( 'fromTermId' )
			->willReturn( $productCategory );

		$this->carrierOptionsFactory
			->method( 'createByOptionId' )
			->willReturnCallback( function ( $optionId ) use ( $carriersOptions ) {
				$carrierOptions = $carriersOptions[ $optionId ];

				return new Carrier\Options(
					$optionId,
					$carrierOptions
				);
			} );

		$this->carrierEntityRepository
			->method( 'getByCountryIncludingNonFeed' )
			->willReturn( $carriers );

		$this->carDeliveryConfig
			->method( 'isDisabled' )
			->willReturn( ! $isCarDeliveryEnabled );

		$rates = $this->checkout->getShippingRates( $allowedCarrierNames );

		self::assertCount( $expectedRateCount, $rates );
	}

	public function testAreBlocksUsedInCheckoutBlockDetection(): void {
		$this->createCheckoutMock();

		$this->provider->method( 'getCheckoutDetection' )->willReturn( OptionsProvider::BLOCK_CHECKOUT_DETECTION );
		$this->assertTrue( $this->checkout->areBlocksUsedInCheckout() );
	}

	public function testAreBlocksUsedInCheckoutClassicDetection(): void {
		$this->createCheckoutMock();

		$this->provider->method('getCheckoutDetection')->willReturn(OptionsProvider::CLASSIC_CHECKOUT_DETECTION);
		$this->assertFalse($this->checkout->areBlocksUsedInCheckout());
	}

	public function testAreBlocksUsedInCheckoutAutomaticDetectionWithBlock(): void {
		$this->createCheckoutMock();

		$this->provider->method('getCheckoutDetection')->willReturn(OptionsProvider::AUTOMATIC_CHECKOUT_DETECTION);

		$this->wpAdapter->method('hasBlock')->willReturn( true );
		$this->assertTrue($this->checkout->areBlocksUsedInCheckout());
	}

	public function testAreBlocksUsedInCheckoutAutomaticDetectionWithoutBlock(): void {
		$this->createCheckoutMock();

		$this->provider->method('getCheckoutDetection')->willReturn(OptionsProvider::AUTOMATIC_CHECKOUT_DETECTION);

		$this->wpAdapter->method('hasBlock')->willReturn( false );
		$this->assertFalse($this->checkout->areBlocksUsedInCheckout());
	}
}
