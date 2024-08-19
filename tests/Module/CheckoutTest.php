<?php

declare( strict_types=1 );

namespace Tests\Module;

use Packetery\Latte\Engine;
use Packetery\Module\Api;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Checkout;
use Packetery\Module\CurrencySwitcherFacade;
use Packetery\Module\Framework\FrameworkAdapter;
use Packetery\Module\Options\Provider;
use Packetery\Module\Order;
use Packetery\Module\Order\PickupPointValidator;
use Packetery\Module\Product;
use Packetery\Module\ProductCategory;
use Packetery\Module\RateCalculator;
use Packetery\Module\WidgetOptionsBuilder;
use Packetery\Nette\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;

class CheckoutTest extends TestCase {
	use WithMockFactory;

	public static function rateCreationDataProvider(): array {
		$carrierInputsFactory = static function ( array $inputConfigs, bool $nullifyAllowedCarrierNames = false ): array {
			$carriers                    = [];
			$carriersOptions             = [];
			$disallowedByProduct         = [];
			$disallowedByProductCategory = [];
			$allowedCarrierNames         = [];

			foreach ( $inputConfigs as $inputConfig ) {
				$carrier = $inputConfig['carrier'];

				$carrierOptionId                     = Carrier\OptionPrefixer::getOptionId( $carrier->getId() );
				$carriers[]                          = $carrier;
				$carriersOptions[ $carrierOptionId ] = array_merge( [
					'id'   => $carrier->getId(),
					'name' => $carrier->getName(),
				], $inputConfig['options'] );

				if ( $inputConfig['isDisallowedByProduct'] ?? false ) {
					$disallowedByProduct[] = $carrierOptionId;
				}

				if ( $inputConfig['isDisallowedByProductCategory'] ?? false ) {
					$disallowedByProductCategory[] = $carrierOptionId;
				}

				if ( $inputConfig['isActivatedByWcShippingConfig'] ?? true ) {
					$allowedCarrierNames[ $carrier->getId() ] = $carrier->getName();
				}
			}

			if ( $nullifyAllowedCarrierNames ) {
				$allowedCarrierNames = null;
			}

			return [
				$carriers,
				$carriersOptions,
				$disallowedByProduct,
				$disallowedByProductCategory,
				$allowedCarrierNames
			];
		};

		return [
			'configured carriers must be present in rates'            => [
				2,
				...$carrierInputsFactory(
					[
						[
							'carrier' => DummyFactory::createCarrierCzechPp(),
							'options' => [
								'active'              => true,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 20.0,
										'price'  => 234.34,
									],
								],
							],
						],
						[
							'carrier' => DummyFactory::createCarrierCzechHdRequiresSize(),
							'options' => [
								'active'              => true,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 5.0,
										'price'  => 444.34,
									],
								],
							],
						]
					]
				),
				true,
				false,
				5.0
			],
			'car delivery carrier must not be present in rates'       => [
				1,
				...$carrierInputsFactory(
					[
						[
							'carrier' => DummyFactory::createCarrierCzechPp(),
							'options' => [
								'active'              => true,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 20.0,
										'price'  => 234.34,
									],
								],
							],
						],
						[
							'carrier' => DummyFactory::createCarDeliveryCarrier(),
							'options' => [
								'active'              => true,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 5.0,
										'price'  => 444.34,
									],
								],
							],
						]
					],
					true
				),
				false,
				false,
				1.0
			],
			'only one carrier is active'                              => [
				1,
				...$carrierInputsFactory(
					[
						[
							'carrier' => DummyFactory::createCarrierCzechPp(),
							'options' => [
								'active'              => true,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 20.0,
										'price'  => 234.34,
									],
								],
							],
						],
						[
							'carrier' => DummyFactory::createCarrierCzechHdRequiresSize(),
							'options' => [
								'active'              => false,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 5.0,
										'price'  => 444.34,
									],
								],
							],
						],
					],
					true
				),
				true,
				false,
				1.0
			],
			'carrier not supporting over-weight cart must be omitted' => [
				0,
				...$carrierInputsFactory(
					[
						[
							'carrier' => DummyFactory::createCarrierCzechPp(),
							'options' => [
								'active'              => true,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 20.0,
										'price'  => 100.0,
									],
								],
							],
						]
					]
				),
				true,
				false,
				21.0
			],
			'inactive carrier must be omitted'                        => [
				0,
				...$carrierInputsFactory(
					[
						[
							'carrier'                       => DummyFactory::createCarrierCzechPp(),
							'isActivatedByWcShippingConfig' => false,
							'options'                       => [
								'active'              => false,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 20.0,
										'price'  => 100.0,
									],
								],
							],
						]
					],
				),
				true,
				false,
				1.0
			],
			'carrier disallowed by product must be omitted'           => [
				0,
				...$carrierInputsFactory(
					[
						[
							'carrier'               => DummyFactory::createCarrierCzechPp(),
							'isDisallowedByProduct' => true,
							'options'               => [
								'active' => true,
							],
						]
					]
				),
				true,
				false,
				1.0
			],
			'carrier disallowed by product category must be omitted'  => [
				0,
				...$carrierInputsFactory(
					[
						[
							'carrier'                       => DummyFactory::createCarrierCzechPp(),
							'isDisallowedByProductCategory' => true,
							'options'                       => [
								'active' => true,
							],
						]
					]
				),
				true,
				false,
				1.0
			],
			'car delivery carriers must be supported'                 => [
				1,
				...$carrierInputsFactory(
					[
						[
							'carrier' => DummyFactory::createCarDeliveryCarrier(),
							'options' => [
								'active'              => true,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 20.0,
										'price'  => 234.34,
									],
								],
							],
						]
					]
				),
				true,
				false,
				1.0
			],
			'carrier not supporting age verification must be omitted' => [
				0,
				...$carrierInputsFactory(
					[
						[
							'carrier' => DummyFactory::createCarDeliveryCarrier(),
							'options' => [
								'active'              => true,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 20.0,
										'price'  => 234.34,
									],
								],
							],
						]
					]
				),
				true,
				true,
				1.0
			],
			'allowed carrier names argument must support null'        => [
				1,
				...$carrierInputsFactory(
					[
						[
							'carrier' => DummyFactory::createCarDeliveryCarrier(),
							'options' => [
								'active'              => true,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 20.0,
										'price'  => 234.34,
									],
								],
							],
						]
					],
					true
				),
				true,
				false,
				1.0
			],
		];
	}

	/**
	 * @dataProvider rateCreationDataProvider
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testRateCreation(
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
		$wcProduct = $this->createMock( \WC_Product::class );
		$wcProduct
			->method( 'get_price' )
			->willReturn( '100.00' );
		$wcProduct
			->method( 'get_category_ids' )
			->willReturn( [ 1 ] );

		$cartItem = [
			'product_id' => 1,
			'quantity'   => 1,
			'data'       => $wcProduct,
		];

		$product = $this->createMock( Product\Entity::class );
		$product
			->method( 'isPhysical' )
			->willReturn( true );
		$product
			->method( 'isAgeVerification18PlusRequired' )
			->willReturn( $isAgeVerificationRequiredByProduct );
		$product
			->method( 'getDisallowedShippingRateIds' )
			->willReturn( array_merge( [ Carrier\OptionPrefixer::getOptionId( 'anyDisallowedOnProduct' ) ], $productDisallowedRateIds ) );

		$cart = $this->createMock( \WC_Cart::class );
		$cart
			->method( 'get_coupons' )
			->willReturn( [] );

		$bridge                       = $this->getPacketeryMockFactory()->createFrameworkAdapter();
		$productEntityFactory         = $this->createMock( Product\EntityFactory::class );
		$productCategoryEntityFactory = $this->createMock( ProductCategory\EntityFactory::class );
		$carrierOptionsFactory        = $this->createMock( Carrier\OptionsFactory::class );
		$carrierEntityRepository      = $this->createMock( Carrier\EntityRepository::class );
		$currencySwitcherFacade       = $this->getPacketeryMockFactory()->createCurrencySwitcherFacade();
		$carDeliveryConfig            = $this->createMock( CarDeliveryConfig::class );

		$checkout = $this->createCheckoutMock(
			$bridge,
			$productEntityFactory,
			$productCategoryEntityFactory,
			$carrierOptionsFactory,
			$currencySwitcherFacade,
			$carrierEntityRepository,
			$carDeliveryConfig,
		);

		$bridge
			->method( 'getCart' )
			->willReturn( $cart );
		$bridge
			->method( 'getCustomerShippingCountry' )
			->willReturn( 'cz' );
		$bridge
			->method( 'getCartContents' )
			->willReturn( [ $cartItem ] );
		$bridge
			->method( 'getCartContentsTotal' )
			->willReturn( 100.0 );
		$bridge
			->method( 'getCartContentsTax' )
			->willReturn( 21.0 );
		$bridge
			->method( 'getCartContentsWeight' )
			->willReturn( $cartWeight );
		$bridge
			->method( 'getWcGetWeight' )
			->willReturn( $cartWeight );
		$bridge
			->method( 'getCartContent' )
			->willReturn( [ $cartItem ] );

		$carrierEntityRepository
			->method( 'getByCountryIncludingNonFeed' )
			->willReturn( $carriers );
		$productEntityFactory
			->method( 'fromPostId' )
			->willReturn( $product );

		$carrierOptionsFactory
			->method( 'createByOptionId' )
			->willReturnCallback( function ( $optionId ) use ( $carriersOptions ) {
				$carrierOptions = $carriersOptions[ $optionId ];

				return new Carrier\Options(
					$optionId,
					$carrierOptions
				);
			} );

		$bridge
			->method( 'getProduct' )
			->willReturn( $wcProduct );

		$productCategory = $this->createMock( ProductCategory\Entity::class );
		$productCategory
			->method( 'getDisallowedShippingRateIds' )
			->willReturn( array_merge( [ Carrier\OptionPrefixer::getOptionId( 'anyDisallowedByProductCategory' ) ], $productCategoryDisallowedRateIds ) );

		$productCategoryEntityFactory
			->method( 'fromTermId' )
			->willReturn( $productCategory );

		$carDeliveryConfig
			->method( 'isEnabled' )
			->willReturn( $isCarDeliveryEnabled );

		$bridge
			->expects( self::atLeast( $expectedRateCount ) )
			->method( 'applyFilters' );

		$bridge
			->method( 'didAction' )
			->willReturn( 1 );

		$rates = $checkout->getShippingRates( $allowedCarrierNames );

		self::assertCount( $expectedRateCount, $rates );
	}

	/**
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	private function createCheckoutMock(
		MockObject|FrameworkAdapter $bridge,
		MockObject|Product\EntityFactory $productEntityFactory,
		MockObject|ProductCategory\EntityFactory $productCategoryEntityFactory,
		MockObject|Carrier\OptionsFactory $carrierOptionsFactory,
		MockObject|CurrencySwitcherFacade $currencySwitcherFacade,
		MockObject|Carrier\EntityRepository $carrierEntityRepository,
		MockObject|CarDeliveryConfig $carDeliveryConfig,
	): Checkout {
		return new Checkout(
			$bridge,
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
			new RateCalculator( $bridge, $currencySwitcherFacade ),
			$this->createMock( PacketaPickupPointsConfig::class ),
			$this->createMock( WidgetOptionsBuilder::class ),
			$carrierEntityRepository,
			$this->createMock( Api\Internal\CheckoutRouter::class ),
			$carDeliveryConfig
		);
	}

}
