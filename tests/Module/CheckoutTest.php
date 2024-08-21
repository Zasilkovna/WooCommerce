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
use Packetery\Module\CurrencySwitcherFacade;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\Provider;
use Packetery\Module\Order;
use Packetery\Module\Order\PickupPointValidator;
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
		float $cartWeight
	): void {
		$mockFactory = new MockFactory();
		$wpAdapter   = $mockFactory->createWpAdapter( $this );
		$wpAdapter
			->expects( self::atLeast( $expectedRateCount ) )
			->method( 'applyFilters' );
		$wpAdapter
			->method( 'didAction' )
			->willReturn( 1 );

		$cart = $this->createMock( \WC_Cart::class );
		$cart
			->method( 'get_coupons' )
			->willReturn( [] );
		$wcAdapter = $this->createMock( WcAdapter::class );
		$wcAdapter
			->method( 'cart' )
			->willReturn( $cart );

		$wcProduct = $this->createMock( \WC_Product::class );
		$wcProduct
			->method( 'get_price' )
			->willReturn( '100.00' );
		$wcProduct
			->method( 'get_category_ids' )
			->willReturn( [ 1 ] );
		$wcAdapter
			->method( 'productFactoryGetProduct' )
			->willReturn( $wcProduct );

		$cartItem = [
			'product_id' => 1,
			'quantity'   => 1,
			'data'       => $wcProduct,
		];
		$wcAdapter
			->method( 'cartGetCartContents' )
			->willReturn( [ $cartItem ] );
		$wcAdapter
			->method( 'cartGetCartContent' )
			->willReturn( [ $cartItem ] );

		$wcAdapter
			->method( 'customerGetShippingCountry' )
			->willReturn( 'cz' );
		$wcAdapter
			->method( 'cartGetCartContentsTotal' )
			->willReturn( 100.0 );
		$wcAdapter
			->method( 'cartGetCartContentsTax' )
			->willReturn( 21.0 );
		$wcAdapter
			->method( 'cartGetCartContentsWeight' )
			->willReturn( $cartWeight );
		$wcAdapter
			->method( 'getWeight' )
			->willReturn( $cartWeight );

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
		$productEntityFactory = $this->createMock( ProductEntityFactory::class );
		$productEntityFactory
			->method( 'fromPostId' )
			->willReturn( $productEntity );

		$productCategory = $this->createMock( ProductCategory\Entity::class );
		$productCategory
			->method( 'getDisallowedShippingRateIds' )
			->willReturn( array_merge( [ Carrier\OptionPrefixer::getOptionId( 'anyDisallowedByProductCategory' ) ], $productCategoryDisallowedRateIds ) );
		$productCategoryEntityFactory = $this->createMock( ProductCategoryEntityFactory::class );
		$productCategoryEntityFactory
			->method( 'fromTermId' )
			->willReturn( $productCategory );

		$carrierOptionsFactory = $this->createMock( CarrierOptionsFactory::class );
		$carrierOptionsFactory
			->method( 'createByOptionId' )
			->willReturnCallback( function ( $optionId ) use ( $carriersOptions ) {
				$carrierOptions = $carriersOptions[ $optionId ];

				return new Carrier\Options(
					$optionId,
					$carrierOptions
				);
			} );

		$currencySwitcherFacade = $mockFactory->createCurrencySwitcherFacade( $this );

		$carrierEntityRepository = $this->createMock( Carrier\EntityRepository::class );
		$carrierEntityRepository
			->method( 'getByCountryIncludingNonFeed' )
			->willReturn( $carriers );

		$carDeliveryConfig = $this->createMock( CarDeliveryConfig::class );
		$carDeliveryConfig
			->method( 'isEnabled' )
			->willReturn( $isCarDeliveryEnabled );

		$checkout = $this->createCheckoutMock(
			$wpAdapter,
			$wcAdapter,
			$productEntityFactory,
			$productCategoryEntityFactory,
			$carrierOptionsFactory,
			$currencySwitcherFacade,
			$carrierEntityRepository,
			$carDeliveryConfig,
		);

		$rates = $checkout->getShippingRates( $allowedCarrierNames );

		self::assertCount( $expectedRateCount, $rates );
	}

	/**
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	private function createCheckoutMock(
		MockObject|WpAdapter $wpAdapter,
		MockObject|WcAdapter $wcAdapter,
		MockObject|ProductEntityFactory $productEntityFactory,
		MockObject|ProductCategoryEntityFactory $productCategoryEntityFactory,
		MockObject|CarrierOptionsFactory $carrierOptionsFactory,
		MockObject|CurrencySwitcherFacade $currencySwitcherFacade,
		MockObject|Carrier\EntityRepository $carrierEntityRepository,
		MockObject|CarDeliveryConfig $carDeliveryConfig,
	): Checkout {
		return new Checkout(
			$wpAdapter,
			$wcAdapter,
			$productEntityFactory,
			$productCategoryEntityFactory,
			$carrierOptionsFactory,
			$this->createMock( Engine::class ),
			$this->createMock( Provider::class ),
			$this->createMock( Carrier\Repository::class ),
			$this->createMock( Request::class ),
			$this->createMock( Order\Repository::class ),
			$currencySwitcherFacade,
			$this->createMock( Order\PacketAutoSubmitter::class ),
			$this->createMock( PickupPointValidator::class ),
			$this->createMock( Order\AttributeMapper::class ),
			new RateCalculator( $wpAdapter, $currencySwitcherFacade ),
			$this->createMock( PacketaPickupPointsConfig::class ),
			$this->createMock( WidgetOptionsBuilder::class ),
			$carrierEntityRepository,
			$this->createMock( Api\Internal\CheckoutRouter::class ),
			$carDeliveryConfig,
		);
	}

}
