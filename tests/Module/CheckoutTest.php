<?php

declare( strict_types=1 );

namespace Tests\Module;

use Packetery\Latte\Engine;
use Packetery\Module\Api;
use Packetery\Module\Bridge;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Checkout;
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

class CheckoutTest extends TestCase {
	use WithMockFactory;

	private MockObject|Bridge $bridge;

	private MockObject|Product\EntityFactory $productEntityFactory;

	private MockObject|ProductCategory\EntityFactory $productCategoryEntityFactory;

	private MockObject|Carrier\OptionsFactory $carrierOptionsFactory;

	private MockObject|Carrier\EntityRepository $carrierEntityRepository;

	private MockObject|CarDeliveryConfig $carDeliveryConfig;

	private Checkout $checkout;

	public function setUp(): void {
		parent::setUp();

		$this->bridge                       = $this->getPacketeryMockFactory()->createBridge();
		$this->productEntityFactory         = $this->createMock( Product\EntityFactory::class );
		$this->productCategoryEntityFactory = $this->createMock( ProductCategory\EntityFactory::class );
		$this->carrierOptionsFactory        = $this->createMock( Carrier\OptionsFactory::class );
		$this->carrierEntityRepository      = $this->createMock( Carrier\EntityRepository::class );
		$currencySwitcherFacade             = $this->getPacketeryMockFactory()->createCurrencySwitcherFacade();
		$this->carDeliveryConfig            = $this->createMock( CarDeliveryConfig::class );

		$this->checkout = new Checkout(
			$this->bridge,
			$this->productEntityFactory,
			$this->productCategoryEntityFactory,
			$this->carrierOptionsFactory,
			$this->createMock( Engine::class ),
			$this->createMock( Provider::class ),
			$this->createMock( Carrier\Repository::class ),
			$this->createMock( Request::class ),
			$this->createMock( Order\Repository::class ),
			$currencySwitcherFacade,
			$this->createMock( Order\PacketAutoSubmitter::class ),
			$this->createMock( PickupPointValidator::class ),
			$this->createMock( Order\AttributeMapper::class ),
			new RateCalculator( $this->bridge, $currencySwitcherFacade ),
			$this->createMock( PacketaPickupPointsConfig::class ),
			$this->createMock( WidgetOptionsBuilder::class ),
			$this->carrierEntityRepository,
			$this->createMock( Api\Internal\CheckoutRouter::class ),
			$this->carDeliveryConfig
		);
	}

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
							'carrier' => \Tests\Core\DummyFactory::createCarrierCzechPp(),
							'options' => [
								'active'              => true,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 20,
										'price'  => 234.34,
									],
								],
							],
						],
						[
							'carrier' => \Tests\Core\DummyFactory::createCarrierCzechHdRequiresSize(),
							'options' => [
								'active'              => true,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 5,
										'price'  => 444.34,
									],
								],
							],
						]
					]
				),
				true,
				false,
				5
			],
			'car delivery carrier must not be present in rates'       => [
				1,
				...$carrierInputsFactory(
					[
						[
							'carrier' => \Tests\Core\DummyFactory::createCarrierCzechPp(),
							'options' => [
								'active'              => true,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 20,
										'price'  => 234.34,
									],
								],
							],
						],
						[
							'carrier' => \Tests\Core\DummyFactory::createCarDeliveryCarrier(),
							'options' => [
								'active'              => true,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 5,
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
				1
			],
			'only one carrier is active'                              => [
				1,
				...$carrierInputsFactory(
					[
						[
							'carrier' => \Tests\Core\DummyFactory::createCarrierCzechPp(),
							'options' => [
								'active'              => true,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 20,
										'price'  => 234.34,
									],
								],
							],
						],
						[
							'carrier' => \Tests\Core\DummyFactory::createCarrierCzechHdRequiresSize(),
							'options' => [
								'active'              => false,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 5,
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
				1
			],
			'carrier not supporting over-weight cart must be omitted' => [
				0,
				...$carrierInputsFactory(
					[
						[
							'carrier' => \Tests\Core\DummyFactory::createCarrierCzechPp(),
							'options' => [
								'active'              => true,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 20,
										'price'  => 100,
									],
								],
							],
						]
					]
				),
				true,
				false,
				21
			],
			'inactive carrier must be omitted'                        => [
				0,
				...$carrierInputsFactory(
					[
						[
							'carrier'                       => \Tests\Core\DummyFactory::createCarrierCzechPp(),
							'isActivatedByWcShippingConfig' => false,
							'options'                       => [
								'active'              => false,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 20,
										'price'  => 100,
									],
								],
							],
						]
					],
				),
				true,
				false,
				1
			],
			'carrier disallowed by product must be omitted'           => [
				0,
				...$carrierInputsFactory(
					[
						[
							'carrier'               => \Tests\Core\DummyFactory::createCarrierCzechPp(),
							'isDisallowedByProduct' => true,
							'options'               => [
								'active' => true,
							],
						]
					]
				),
				true,
				false,
				1
			],
			'carrier disallowed by product category must be omitted'  => [
				0,
				...$carrierInputsFactory(
					[
						[
							'carrier'                       => \Tests\Core\DummyFactory::createCarrierCzechPp(),
							'isDisallowedByProductCategory' => true,
							'options'                       => [
								'active' => true,
							],
						]
					]
				),
				true,
				false,
				1
			],
			'car delivery carriers must be supported'                 => [
				1,
				...$carrierInputsFactory(
					[
						[
							'carrier' => \Tests\Core\DummyFactory::createCarDeliveryCarrier(),
							'options' => [
								'active'              => true,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 20,
										'price'  => 234.34,
									],
								],
							],
						]
					]
				),
				true,
				false,
				1
			],
			'carrier not supporting age verification must be omitted' => [
				0,
				...$carrierInputsFactory(
					[
						[
							'carrier' => \Tests\Core\DummyFactory::createCarDeliveryCarrier(),
							'options' => [
								'active'              => true,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 20,
										'price'  => 234.34,
									],
								],
							],
						]
					]
				),
				true,
				true,
				1
			],
			'allowed carrier names argument must support null'        => [
				1,
				...$carrierInputsFactory(
					[
						[
							'carrier' => \Tests\Core\DummyFactory::createCarDeliveryCarrier(),
							'options' => [
								'active'              => true,
								'free_shipping_limit' => null,
								'weight_limits'       => [
									[
										'weight' => 20,
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
				1
			],
		];
	}

	/**
	 * @dataProvider rateCreationDataProvider
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
			->willReturn( 100 );
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
		$this->bridge
			->method( 'getCart' )
			->willReturn( $cart );
		$this->bridge
			->method( 'getCustomerShippingCountry' )
			->willReturn( 'cz' );
		$this->bridge
			->method( 'getCartContents' )
			->willReturn( [ $cartItem ] );
		$this->bridge
			->method( 'getCartContentsTotal' )
			->willReturn( 100 );
		$this->bridge
			->method( 'getCartContentsTax' )
			->willReturn( 21 );
		$this->bridge
			->method( 'getCartContentsWeight' )
			->willReturn( $cartWeight );
		$this->bridge
			->method( 'getWcGetWeight' )
			->willReturn( $cartWeight );
		$this->bridge
			->method( 'getCartContent' )
			->willReturn( [ $cartItem ] );

		$this->carrierEntityRepository
			->method( 'getByCountryIncludingNonFeed' )
			->willReturn( $carriers );
		$this->productEntityFactory
			->method( 'fromPostId' )
			->willReturn( $product );

		$this->carrierOptionsFactory
			->method( 'createByOptionId' )
			->willReturnCallback( function ( $optionId ) use ( $carriersOptions ) {
				$carrierOptions = $carriersOptions[ $optionId ];

				return new Carrier\Options(
					$optionId,
					$carrierOptions
				);
			} );

		$this->bridge
			->method( 'getProduct' )
			->willReturn( $wcProduct );

		$productCategory = $this->createMock( ProductCategory\Entity::class );
		$productCategory
			->method( 'getDisallowedShippingRateIds' )
			->willReturn( array_merge( [ Carrier\OptionPrefixer::getOptionId( 'anyDisallowedByProductCategory' ) ], $productCategoryDisallowedRateIds ) );

		$this->productCategoryEntityFactory
			->method( 'fromTermId' )
			->willReturn( $productCategory );

		$this->carDeliveryConfig
			->method( 'isEnabled' )
			->willReturn( $isCarDeliveryEnabled );

		$this->bridge
			->expects( self::atLeast( $expectedRateCount ) )
			->method( 'applyFilters' );

		$this->bridge
			->method( 'didAction' )
			->willReturn( true );

		$rates = $this->checkout->getShippingRates( $allowedCarrierNames );

		self::assertCount( $expectedRateCount, $rates );
	}
}