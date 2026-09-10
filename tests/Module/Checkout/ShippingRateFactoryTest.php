<?php

declare( strict_types=1 );

namespace Tests\Module\Checkout;

use Packetery\Core\Entity;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\OptionPrefixer;
use Packetery\Module\Checkout\CartService;
use Packetery\Module\Checkout\CheckoutService;
use Packetery\Module\Checkout\CurrencySwitcherService;
use Packetery\Module\Checkout\RateCalculator;
use Packetery\Module\Checkout\RateUnavailability;
use Packetery\Module\Checkout\RateUnavailabilityReason;
use Packetery\Module\Checkout\ShippingRateDiagnostics;
use Packetery\Module\Checkout\ShippingRateEvaluation;
use Packetery\Module\Checkout\ShippingRateFactory;
use Packetery\Module\DiagnosticsLogger\DiagnosticsLogger;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Shipping\BaseShippingMethod;
use Packetery\Module\ShippingMethod;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Tests\Core\DummyFactory;
use Tests\Module\MockFactory;

class ShippingRateFactoryTest extends TestCase {
	private const DUMMY_RATE_ID = 'dummyRateId';

	private CheckoutService&MockObject $checkoutServiceMock;
	private Carrier\EntityRepository&MockObject $carrierEntityRepositoryMock;
	private CartService&MockObject $cartServiceMock;
	private CarrierOptionsFactory&MockObject $carrierOptionsFactoryMock;
	private OptionsProvider&MockObject $optionsProviderMock;
	private DiagnosticsLogger&MockObject $diagnosticsLogger;
	private CarDeliveryConfig&MockObject $carDeliveryConfigMock;
	private ShippingRateDiagnostics&MockObject $shippingRateDiagnosticsMock;

	public function createShippingRateFactory(): ShippingRateFactory {
		$wpAdapterMock = MockFactory::createWpAdapter( $this );
		$wpAdapterMock->method( '__' )
			->willReturnCallback(
				function ( string $text ): string {
					return $text;
				}
			);

		$wcAdapterMock                     = $this->createMock( WcAdapter::class );
		$this->checkoutServiceMock         = $this->createMock( CheckoutService::class );
		$this->carrierEntityRepositoryMock = $this->createMock( Carrier\EntityRepository::class );
		$this->cartServiceMock             = $this->createMock( CartService::class );
		$this->carrierOptionsFactoryMock   = $this->createMock( CarrierOptionsFactory::class );
		$this->optionsProviderMock         = $this->createMock( OptionsProvider::class );
		$this->diagnosticsLogger           = $this->createMock( DiagnosticsLogger::class );
		$this->carDeliveryConfigMock       = $this->createMock( CarDeliveryConfig::class );
		$this->shippingRateDiagnosticsMock = $this->createMock( ShippingRateDiagnostics::class );

		$rateCalculator = new RateCalculator(
			$wpAdapterMock,
			$wcAdapterMock,
			$this->createMock( CurrencySwitcherService::class ),
			$this->diagnosticsLogger
		);

		return new ShippingRateFactory(
			$wpAdapterMock,
			$wcAdapterMock,
			$this->checkoutServiceMock,
			$this->carrierEntityRepositoryMock,
			$this->cartServiceMock,
			$this->carrierOptionsFactoryMock,
			$this->carDeliveryConfigMock,
			$rateCalculator,
			$this->optionsProviderMock,
			$this->diagnosticsLogger,
			$this->shippingRateDiagnosticsMock
		);
	}

	/**
	 * The diagnostics is the whole point of the evaluation being collected at all, and a mock that is
	 * only asked isActive() lets both collect calls be deleted with the suite staying green.
	 */
	public function testEveryEvaluationIsHandedToTheDiagnostics(): void {
		$shippingRateFactory = $this->createShippingRateFactory();

		$carrier = DummyFactory::createCarrierCzechPp();
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->carrierEntityRepositoryMock->method( 'getByCountryIncludingNonFeed' )->willReturn( [ $carrier ] );
		$this->carrierOptionsFactoryMock->method( 'createByOptionId' )
			->willReturn( new Carrier\Options( 'packetery_carrier_' . $carrier->getId(), [ 'active' => true ] ) );

		$this->shippingRateDiagnosticsMock->expects( self::once() )->method( 'collectMethodRun' );
		$this->shippingRateDiagnosticsMock->expects( self::once() )
			->method( 'collectEvaluation' )
			->with(
				self::callback(
					function ( ShippingRateEvaluation $evaluation ) use ( $carrier ): bool {
						return $evaluation->getCarrierId() === $carrier->getId();
					}
				)
			);

		$shippingRateFactory->createShippingRates( null, ShippingMethod::PACKETERY_METHOD_ID, 1 );
	}

	public function testPackageLevelReasonIsHandedToTheDiagnostics(): void {
		$shippingRateFactory = $this->createShippingRateFactory();

		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( null );

		$this->shippingRateDiagnosticsMock->expects( self::once() )
			->method( 'collectPackageUnavailability' )
			->with(
				self::callback(
					function ( RateUnavailability $unavailability ): bool {
						return $unavailability->getReason() === RateUnavailabilityReason::CUSTOMER_COUNTRY_UNKNOWN;
					}
				)
			);

		$shippingRateFactory->createShippingRates( null, ShippingMethod::PACKETERY_METHOD_ID, 1 );
	}

	public static function createShippingRateAndApplyTaxesProvider(): array {
		return [
			'free shipping label appended when cost zero and visibility on' => [
				'cost'                  => 0.0,
				'isFreeShippingShown'   => true,
				'expectedLabel'         => 'Carrier Name: Free',
				'arePricesTaxInclusive' => false,
			],
			'free shipping label not appended when cost zero and visibility off' => [
				'cost'                  => 0.0,
				'isFreeShippingShown'   => false,
				'expectedLabel'         => 'Carrier Name',
				'arePricesTaxInclusive' => false,
			],
			'positive cost keeps label unchanged'       => [
				'cost'                  => 123.45,
				'isFreeShippingShown'   => true,
				'expectedLabel'         => 'Carrier Name',
				'arePricesTaxInclusive' => false,
			],
			'apply taxes when prices are tax inclusive' => [
				'cost'                  => 123.45,
				'isFreeShippingShown'   => true,
				'expectedLabel'         => 'Carrier Name',
				'arePricesTaxInclusive' => true,
			],
		];
	}

	/**
	 * @dataProvider createShippingRateAndApplyTaxesProvider
	 */
	public function testCreateShippingRateAndApplyTaxes(
		float $cost,
		bool $isFreeShippingShown,
		string $expectedLabel,
		bool $arePricesTaxInclusive,
	): void {
		$shippingRateFactory = $this->createShippingRateFactory();

		$this->optionsProviderMock->method( 'isFreeShippingShown' )
									->willReturn( $isFreeShippingShown );
		$this->optionsProviderMock->method( 'arePricesTaxInclusive' )
									->willReturn( $arePricesTaxInclusive );

		$reflection = new ReflectionMethod( ShippingRateFactory::class, 'createShippingRateAndApplyTaxes' );
		$reflection->setAccessible( true );
		$resultRate = $reflection->invoke( $shippingRateFactory, 'Carrier Name', $cost, self::DUMMY_RATE_ID );

		self::assertIsArray( $resultRate );
		self::assertSame( $expectedLabel, $resultRate['label'] );
		self::assertSame( self::DUMMY_RATE_ID, $resultRate['id'] );
		self::assertSame( $cost, $resultRate['cost'] );
		self::assertSame( 'per_order', $resultRate['calc_tax'] );
	}

	public static function createShippingRatesProvider(): array {
		$carrierCzPp = DummyFactory::createCarrierCzechPp();
		$carrierCzHd = DummyFactory::createCarrierCzechHdRequiresSize();
		$carrierDe   = DummyFactory::createCarrierGermanPp();

		$carrierCzUnavailable = new Entity\Carrier(
			'unavailable-cz',
			'unavailable-cz',
			true,
			false,
			false,
			false,
			true,
			true,
			false,
			true,
			'cz',
			'CZK',
			5.0,
			false,
			false,
			true
		);
		$carrierCzNoAgeCheck  = new Entity\Carrier(
			'no-age-cz',
			'no-age-cz',
			true,
			false,
			false,
			false,
			true,
			true,
			false,
			true,
			'cz',
			'CZK',
			5.0,
			true,
			false,
			false
		);
		$carrierCarDelivery   = new Entity\Carrier(
			'25061',
			'25061',
			false,
			true,
			false,
			false,
			true,
			true,
			false,
			true,
			'cz',
			'CZK',
			5.0,
			true,
			false,
			true
		);

		$carrierOptionsCzPp = new Carrier\Options(
			OptionPrefixer::getOptionId( $carrierCzPp->getId() ),
			[
				'id'                  => $carrierCzPp->getId(),
				'active'              => true,
				'name'                => 'Carrier CZ I',
				'weight_limits'       => [
					[
						'weight' => 5.0,
						'price'  => 30.0,
					],
				],
				'free_shipping_limit' => null,
			],
		);
		$carrierOptionsCzHd = new Carrier\Options(
			OptionPrefixer::getOptionId( $carrierCzHd->getId() ),
			[
				'id'                   => $carrierCzHd->getId(),
				'active'               => true,
				'name'                 => 'Carrier CZ II',
				'weight_limits'        => null,
				'product_value_limits' => [
					[
						'value' => 0.0,
						'price' => 30.0,
					],
					[
						'value' => 2000.0,
						'price' => 25.0,
					],
				],
				'free_shipping_limit'  => null,
				'pricing_type'         => 'byProductValue',
			],
		);
		$carrierOptionsDe   = new Carrier\Options(
			OptionPrefixer::getOptionId( $carrierDe->getId() ),
			[
				'id'                  => $carrierDe->getId(),
				'active'              => true,
				'name'                => 'Carrier DE',
				'weight_limits'       => [
					[
						'weight' => 5.0,
						'price'  => 30.0,
					],
				],
				'free_shipping_limit' => 90.0,
			],
		);

		$carrierOptionsCzPpInactive = new Carrier\Options(
			OptionPrefixer::getOptionId( $carrierCzPp->getId() ),
			[
				'id'            => $carrierCzPp->getId(),
				'active'        => false,
				'name'          => 'Carrier CZ I',
				'weight_limits' => [
					[
						'weight' => 5.0,
						'price'  => 30.0,
					],
				],
			],
		);
		$carrierOptionsCzPpNoRules  = new Carrier\Options(
			OptionPrefixer::getOptionId( $carrierCzPp->getId() ),
			[
				'id'            => $carrierCzPp->getId(),
				'active'        => true,
				'name'          => 'Carrier CZ I',
				'weight_limits' => [],
			],
		);
		$carrierOptionsUnavailable  = new Carrier\Options(
			OptionPrefixer::getOptionId( $carrierCzUnavailable->getId() ),
			[
				'id'            => $carrierCzUnavailable->getId(),
				'active'        => true,
				'name'          => 'Carrier CZ unavailable',
				'weight_limits' => [
					[
						'weight' => 5.0,
						'price'  => 30.0,
					],
				],
			],
		);
		$carrierOptionsCarDelivery  = new Carrier\Options(
			OptionPrefixer::getOptionId( $carrierCarDelivery->getId() ),
			[
				'id'            => $carrierCarDelivery->getId(),
				'active'        => true,
				'name'          => 'Car delivery',
				'weight_limits' => [
					[
						'weight' => 5.0,
						'price'  => 30.0,
					],
				],
			],
		);

		$carrierOptionsCzNoAgeCheck = new Carrier\Options(
			OptionPrefixer::getOptionId( $carrierCzNoAgeCheck->getId() ),
			[
				'id'            => $carrierCzNoAgeCheck->getId(),
				'active'        => true,
				'name'          => 'Carrier CZ without age check',
				'weight_limits' => [
					[
						'weight' => 5.0,
						'price'  => 30.0,
					],
				],
			],
		);

		$oversizedProduct    = [
			'productId'   => 42,
			'restriction' => CartService::SIZE_RESTRICTION_MAXIMUM_LENGTH,
			'measured'    => 120.0,
			'limit'       => 100.0,
		];
		$restrictingCategory = [
			'productId'  => 42,
			'categoryId' => 7,
		];

		return [
			'no customer country -> empty result'         => [
				'allowedCarrierNames'       => null,
				'methodId'                  => ShippingMethod::PACKETERY_METHOD_ID,
				'instanceId'                => 1,
				'customerCountry'           => null,
				'availableCarriers'         => [],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'isCarDeliveryDisabled'     => false,
				'disallowingProductId'      => null,
				'oversizedProduct'          => null,
				'restrictingCategory'       => null,
				'arePricesTaxInclusive'     => false,
				'collectAllReasons'         => true,
				'expectedRateCount'         => 0,
				'expectedReasons'           => [ RateUnavailabilityReason::CUSTOMER_COUNTRY_UNKNOWN ],
			],
			'packetery method with 2 carriers in same country' => [
				'allowedCarrierNames'       => null,
				'methodId'                  => ShippingMethod::PACKETERY_METHOD_ID,
				'instanceId'                => 1,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [ [ $carrierCzPp, $carrierOptionsCzPp ], [ $carrierCzHd, $carrierOptionsCzHd ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'isCarDeliveryDisabled'     => false,
				'disallowingProductId'      => null,
				'oversizedProduct'          => null,
				'restrictingCategory'       => null,
				'arePricesTaxInclusive'     => false,
				'collectAllReasons'         => true,
				'expectedRateCount'         => 2,
				'expectedReasons'           => [],
			],
			'specific carrier method with matching country' => [
				'allowedCarrierNames'       => null,
				'methodId'                  => BaseShippingMethod::PACKETA_METHOD_PREFIX . $carrierDe->getId(),
				'instanceId'                => 3,
				'customerCountry'           => 'de',
				'availableCarriers'         => [ [ $carrierDe, $carrierOptionsDe ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'isCarDeliveryDisabled'     => false,
				'disallowingProductId'      => null,
				'oversizedProduct'          => null,
				'restrictingCategory'       => null,
				'arePricesTaxInclusive'     => false,
				'collectAllReasons'         => true,
				'expectedRateCount'         => 1,
				'expectedReasons'           => [],
			],
			'age verification required but unsupported'   => [
				'allowedCarrierNames'       => null,
				'methodId'                  => BaseShippingMethod::PACKETA_METHOD_PREFIX . $carrierCzNoAgeCheck->getId(),
				'instanceId'                => 3,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [ [ $carrierCzNoAgeCheck, $carrierOptionsCzNoAgeCheck ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => true,
				'isCarDeliveryDisabled'     => false,
				'disallowingProductId'      => null,
				'oversizedProduct'          => null,
				'restrictingCategory'       => null,
				'arePricesTaxInclusive'     => false,
				'collectAllReasons'         => true,
				'expectedRateCount'         => 0,
				'expectedReasons'           => [ RateUnavailabilityReason::AGE_VERIFICATION_UNSUPPORTED ],
			],
			'specific carrier method with mismatched country' => [
				'allowedCarrierNames'       => null,
				'methodId'                  => BaseShippingMethod::PACKETA_METHOD_PREFIX . $carrierDe->getId(),
				'instanceId'                => 3,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [ [ $carrierDe, $carrierOptionsDe ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'isCarDeliveryDisabled'     => false,
				'disallowingProductId'      => null,
				'oversizedProduct'          => null,
				'restrictingCategory'       => null,
				'arePricesTaxInclusive'     => false,
				'collectAllReasons'         => true,
				'expectedRateCount'         => 0,
				'expectedReasons'           => [],
			],
			'legacy method with no carrier for the country' => [
				'allowedCarrierNames'       => null,
				'methodId'                  => ShippingMethod::PACKETERY_METHOD_ID,
				'instanceId'                => 1,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'isCarDeliveryDisabled'     => false,
				'disallowingProductId'      => null,
				'oversizedProduct'          => null,
				'restrictingCategory'       => null,
				'arePricesTaxInclusive'     => false,
				'collectAllReasons'         => true,
				'expectedRateCount'         => 0,
				'expectedReasons'           => [ RateUnavailabilityReason::NO_CARRIER_FOR_COUNTRY ],
			],
			'carrier unavailable in the feed'             => [
				'allowedCarrierNames'       => null,
				'methodId'                  => ShippingMethod::PACKETERY_METHOD_ID,
				'instanceId'                => 1,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [ [ $carrierCzUnavailable, $carrierOptionsUnavailable ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'isCarDeliveryDisabled'     => false,
				'disallowingProductId'      => null,
				'oversizedProduct'          => null,
				'restrictingCategory'       => null,
				'arePricesTaxInclusive'     => false,
				'collectAllReasons'         => true,
				'expectedRateCount'         => 0,
				'expectedReasons'           => [ RateUnavailabilityReason::CARRIER_UNAVAILABLE ],
			],
			'carrier not activated in Packeta settings'   => [
				'allowedCarrierNames'       => null,
				'methodId'                  => ShippingMethod::PACKETERY_METHOD_ID,
				'instanceId'                => 1,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [ [ $carrierCzPp, $carrierOptionsCzPpInactive ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'isCarDeliveryDisabled'     => false,
				'disallowingProductId'      => null,
				'oversizedProduct'          => null,
				'restrictingCategory'       => null,
				'arePricesTaxInclusive'     => false,
				'collectAllReasons'         => true,
				'expectedRateCount'         => 0,
				'expectedReasons'           => [ RateUnavailabilityReason::CARRIER_OPTION_INACTIVE ],
			],
			'car delivery globally disabled'              => [
				'allowedCarrierNames'       => null,
				'methodId'                  => ShippingMethod::PACKETERY_METHOD_ID,
				'instanceId'                => 1,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [ [ $carrierCarDelivery, $carrierOptionsCarDelivery ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'isCarDeliveryDisabled'     => true,
				'disallowingProductId'      => null,
				'oversizedProduct'          => null,
				'restrictingCategory'       => null,
				'arePricesTaxInclusive'     => false,
				'collectAllReasons'         => true,
				'expectedRateCount'         => 0,
				'expectedReasons'           => [ RateUnavailabilityReason::CAR_DELIVERY_DISABLED ],
			],
			'product in cart disallows the carrier'       => [
				'allowedCarrierNames'       => null,
				'methodId'                  => ShippingMethod::PACKETERY_METHOD_ID,
				'instanceId'                => 1,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [ [ $carrierCzPp, $carrierOptionsCzPp ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'isCarDeliveryDisabled'     => false,
				'disallowingProductId'      => 42,
				'oversizedProduct'          => null,
				'restrictingCategory'       => null,
				'arePricesTaxInclusive'     => false,
				'collectAllReasons'         => true,
				'expectedRateCount'         => 0,
				'expectedReasons'           => [ RateUnavailabilityReason::DISALLOWED_BY_PRODUCT ],
			],
			'product oversized for the carrier'           => [
				'allowedCarrierNames'       => null,
				'methodId'                  => ShippingMethod::PACKETERY_METHOD_ID,
				'instanceId'                => 1,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [ [ $carrierCzPp, $carrierOptionsCzPp ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'isCarDeliveryDisabled'     => false,
				'disallowingProductId'      => null,
				'oversizedProduct'          => $oversizedProduct,
				'restrictingCategory'       => null,
				'arePricesTaxInclusive'     => false,
				'collectAllReasons'         => true,
				'expectedRateCount'         => 0,
				'expectedReasons'           => [ RateUnavailabilityReason::PRODUCT_OVERSIZED ],
			],
			'product category disallows the carrier'      => [
				'allowedCarrierNames'       => null,
				'methodId'                  => ShippingMethod::PACKETERY_METHOD_ID,
				'instanceId'                => 1,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [ [ $carrierCzPp, $carrierOptionsCzPp ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'isCarDeliveryDisabled'     => false,
				'disallowingProductId'      => null,
				'oversizedProduct'          => null,
				'restrictingCategory'       => $restrictingCategory,
				'arePricesTaxInclusive'     => false,
				'collectAllReasons'         => true,
				'expectedRateCount'         => 0,
				'expectedReasons'           => [ RateUnavailabilityReason::DISALLOWED_BY_CATEGORY ],
			],
			'cart heavier than the highest weight rule'   => [
				'allowedCarrierNames'       => null,
				'methodId'                  => ShippingMethod::PACKETERY_METHOD_ID,
				'instanceId'                => 1,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [ [ $carrierCzPp, $carrierOptionsCzPp ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 6.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'isCarDeliveryDisabled'     => false,
				'disallowingProductId'      => null,
				'oversizedProduct'          => null,
				'restrictingCategory'       => null,
				'arePricesTaxInclusive'     => false,
				'collectAllReasons'         => true,
				'expectedRateCount'         => 0,
				'expectedReasons'           => [ RateUnavailabilityReason::WEIGHT_OVER_LIMIT ],
			],
			'cart worth more than the highest value rule' => [
				'allowedCarrierNames'       => null,
				'methodId'                  => ShippingMethod::PACKETERY_METHOD_ID,
				'instanceId'                => 1,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [ [ $carrierCzHd, $carrierOptionsCzHd ] ],
				'cartTotal'                 => 3000.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 3000.0,
				'isAgeVerificationRequired' => false,
				'isCarDeliveryDisabled'     => false,
				'disallowingProductId'      => null,
				'oversizedProduct'          => null,
				'restrictingCategory'       => null,
				'arePricesTaxInclusive'     => false,
				'collectAllReasons'         => true,
				'expectedRateCount'         => 0,
				'expectedReasons'           => [ RateUnavailabilityReason::PRODUCT_VALUE_OVER_LIMIT ],
			],
			'no pricing rule configured at all'           => [
				'allowedCarrierNames'       => null,
				'methodId'                  => ShippingMethod::PACKETERY_METHOD_ID,
				'instanceId'                => 1,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [ [ $carrierCzPp, $carrierOptionsCzPpNoRules ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'isCarDeliveryDisabled'     => false,
				'disallowingProductId'      => null,
				'oversizedProduct'          => null,
				'restrictingCategory'       => null,
				'arePricesTaxInclusive'     => false,
				'collectAllReasons'         => true,
				'expectedRateCount'         => 0,
				'expectedReasons'           => [ RateUnavailabilityReason::NO_PRICING_RULES ],
			],
			'several reasons reported at once'            => [
				'allowedCarrierNames'       => null,
				'methodId'                  => ShippingMethod::PACKETERY_METHOD_ID,
				'instanceId'                => 1,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [ [ $carrierCzPp, $carrierOptionsCzPp ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 6.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'isCarDeliveryDisabled'     => false,
				'disallowingProductId'      => 42,
				'oversizedProduct'          => $oversizedProduct,
				'restrictingCategory'       => $restrictingCategory,
				'arePricesTaxInclusive'     => false,
				'collectAllReasons'         => true,
				'expectedRateCount'         => 0,
				'expectedReasons'           => [
					RateUnavailabilityReason::DISALLOWED_BY_PRODUCT,
					RateUnavailabilityReason::PRODUCT_OVERSIZED,
					RateUnavailabilityReason::DISALLOWED_BY_CATEGORY,
					RateUnavailabilityReason::WEIGHT_OVER_LIMIT,
				],
			],
			'diagnostics off stops at the first reason and skips the cart scan' => [
				'allowedCarrierNames'       => null,
				'methodId'                  => ShippingMethod::PACKETERY_METHOD_ID,
				'instanceId'                => 1,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [ [ $carrierCzUnavailable, $carrierOptionsUnavailable ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 6.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'isCarDeliveryDisabled'     => false,
				'disallowingProductId'      => 42,
				'oversizedProduct'          => null,
				'restrictingCategory'       => null,
				'arePricesTaxInclusive'     => false,
				'collectAllReasons'         => false,
				'expectedRateCount'         => 0,
				'expectedReasons'           => [ RateUnavailabilityReason::CARRIER_UNAVAILABLE ],
			],
			'diagnostics on reports the cart reasons alongside the carrier one' => [
				'allowedCarrierNames'       => null,
				'methodId'                  => ShippingMethod::PACKETERY_METHOD_ID,
				'instanceId'                => 1,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [ [ $carrierCzUnavailable, $carrierOptionsUnavailable ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 6.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'isCarDeliveryDisabled'     => false,
				'disallowingProductId'      => 42,
				'oversizedProduct'          => null,
				'restrictingCategory'       => null,
				'arePricesTaxInclusive'     => false,
				'collectAllReasons'         => true,
				'expectedRateCount'         => 0,
				'expectedReasons'           => [
					RateUnavailabilityReason::CARRIER_UNAVAILABLE,
					RateUnavailabilityReason::DISALLOWED_BY_PRODUCT,
					RateUnavailabilityReason::WEIGHT_OVER_LIMIT,
				],
			],
		];
	}

	/**
	 * @dataProvider createShippingRatesProvider
	 */
	public function testCreateShippingRates(
		?array $allowedCarrierNames,
		string $methodId,
		int $instanceId,
		?string $customerCountry,
		array $availableCarriers,
		float $cartTotal,
		float $cartWeight,
		float $totalValue,
		bool $isAgeVerificationRequired,
		bool $isCarDeliveryDisabled,
		?int $disallowingProductId,
		?array $oversizedProduct,
		?array $restrictingCategory,
		bool $arePricesTaxInclusive,
		bool $collectAllReasons,
		int $expectedRateCount,
		array $expectedReasons,
	): void {
		$shippingRateFactory = $this->createShippingRateFactory();

		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( $customerCountry );
		$this->carDeliveryConfigMock->method( 'isDisabled' )->willReturn( $isCarDeliveryDisabled );
		$this->shippingRateDiagnosticsMock->method( 'isActive' )->willReturn( $collectAllReasons );

		$carrierEntities = [];
		$optionsMap      = [];
		foreach ( $availableCarriers as $pair ) {
			if ( is_array( $pair ) && count( $pair ) === 2 ) {
				/** @var array<Entity\Carrier, Carrier\Options> $pair */
				[ $carrier, $options ] = $pair;

				$carrierEntities[]                     = $carrier;
				$optionsMap[ $options->getOptionId() ] = $options;
			}
		}

		if ( $methodId === ShippingMethod::PACKETERY_METHOD_ID ) {
			$this->carrierEntityRepositoryMock
				->method( 'getByCountryIncludingNonFeed' )
				->willReturn( $carrierEntities );
		} else {
			$targetCarrier = $carrierEntities[0] ?? null;
			$this->carrierEntityRepositoryMock
				->method( 'getAnyById' )
				->willReturn( $targetCarrier );
		}

		$this->carrierOptionsFactoryMock
			->method( 'createByOptionId' )
			->willReturnCallback(
				function ( string $optionId ) use ( $optionsMap ) {
					return $optionsMap[ $optionId ] ?? new Carrier\Options( $optionId, [ 'active' => true ] );
				}
			);

		$this->cartServiceMock->method( 'getCartContentsTotalIncludingTax' )->willReturn( $cartTotal );
		$this->cartServiceMock->method( 'getCartWeightKg' )->willReturn( $cartWeight );
		$this->cartServiceMock->method( 'getTotalCartProductValue' )->willReturn( $totalValue );
		$this->cartServiceMock->method( 'isAgeVerificationRequired' )->willReturn( $isAgeVerificationRequired );
		$this->cartServiceMock->method( 'findProductDisallowingRate' )->willReturn( $disallowingProductId );
		$this->cartServiceMock->method( 'findOversizedProductForCarrier' )->willReturn( $oversizedProduct );
		$this->cartServiceMock->method( 'findCategoryRestrictingRate' )->willReturn( $restrictingCategory );

		$this->optionsProviderMock->method( 'arePricesTaxInclusive' )->willReturn( $arePricesTaxInclusive );

		$loggedReasons = [];
		$this->diagnosticsLogger
			->method( 'log' )
			->willReturnCallback(
				function ( string $logMessage, array $arguments ) use ( &$loggedReasons ): void {
					if ( $logMessage === 'Shipping rate evaluated' ) {
						foreach ( $arguments['unavailabilities'] as $unavailability ) {
							$loggedReasons[] = $unavailability['reason'];
						}
					}
					if ( $logMessage === 'No shipping rate can be offered for the package' ) {
						$loggedReasons[] = $arguments['reason'];
					}
				}
			);

		$shippingRates = $shippingRateFactory->createShippingRates( $allowedCarrierNames, $methodId, $instanceId );

		self::assertCount( $expectedRateCount, $shippingRates );
		self::assertSame( $expectedReasons, $loggedReasons );
		if ( $expectedRateCount > 0 ) {
			$firstRate = array_key_first( $shippingRates );
			if ( $methodId === ShippingMethod::PACKETERY_METHOD_ID ) {
				self::assertStringStartsWith( ShippingMethod::PACKETERY_METHOD_ID . ':', $firstRate );
			} else {
				self::assertStringStartsWith( $methodId . ':', $firstRate );
			}
		}
	}
}
