<?php

declare( strict_types=1 );

namespace Tests\Integration\Module\Checkout;

use Packetery\Core\Entity;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\OptionPrefixer;
use Packetery\Module\Checkout\CartService;
use Packetery\Module\Checkout\CheckoutService;
use Packetery\Module\Checkout\CurrencySwitcherService;
use Packetery\Module\Checkout\RateCalculator;
use Packetery\Module\Checkout\ShippingRateFactory;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Shipping\BaseShippingMethod;
use Packetery\Module\ShippingMethod;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;
use Tests\Integration\AbstractIntegrationTestCase;
use Tests\Module\MockFactory;

class ShippingRateFactoryTest extends AbstractIntegrationTestCase {
	private const DUMMY_RATE_ID = 'dummyRateId';

	private CheckoutService&MockObject $checkoutServiceMock;
	private Carrier\EntityRepository&MockObject $carrierEntityRepositoryMock;
	private CartService&MockObject $cartServiceMock;
	private CarrierOptionsFactory&MockObject $carrierOptionsFactoryMock;
	private OptionsProvider&MockObject $optionsProviderMock;

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

		$rateCalculator = new RateCalculator(
			$wpAdapterMock,
			$wcAdapterMock,
			$this->createMock( CurrencySwitcherService::class )
		);

		return new ShippingRateFactory(
			$wpAdapterMock,
			$wcAdapterMock,
			$this->checkoutServiceMock,
			$this->carrierEntityRepositoryMock,
			$this->cartServiceMock,
			$this->carrierOptionsFactoryMock,
			$this->createMock( CarDeliveryConfig::class ),
			$rateCalculator,
			$this->optionsProviderMock
		);
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
		$carrierCzFirst  = new Entity\Carrier(
			'100',
			'Carrier CZ',
			true,
			false,
			false,
			false,
			false,
			false,
			false,
			true,
			'cz',
			'CZK',
			30.0,
			true,
			false,
			true,
		);
		$carrierCzSecond = new Entity\Carrier(
			'101',
			'Carrier CZ 2',
			true,
			false,
			false,
			false,
			false,
			false,
			false,
			true,
			'cz',
			'CZK',
			30.0,
			true,
			false,
			true,
		);
		$carrierSk       = new Entity\Carrier(
			'200',
			'Carrier SK',
			true,
			false,
			false,
			false,
			false,
			false,
			false,
			true,
			'sk',
			'EUR',
			30.0,
			true,
			false,
			false,
		);

		$carrierOptionsCzFirst  = new Carrier\Options(
			OptionPrefixer::getOptionId( $carrierCzFirst->getId() ),
			[
				'id'                  => $carrierCzFirst->getId(),
				'active'              => true,
				'name'                => 'Carrier CZ',
				'weight_limits'       => [
					[
						'weight' => 5.0,
						'price'  => 30.0,
					],
				],
				'free_shipping_limit' => null,
			],
		);
		$carrierOptionsCzSecond = new Carrier\Options(
			OptionPrefixer::getOptionId( $carrierCzSecond->getId() ),
			[
				'id'                   => $carrierCzSecond->getId(),
				'active'               => true,
				'name'                 => 'Carrier CZ 2',
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
		$carrierOptionsSk       = new Carrier\Options(
			OptionPrefixer::getOptionId( $carrierSk->getId() ),
			[
				'id'                  => $carrierSk->getId(),
				'active'              => true,
				'name'                => 'Carrier SK',
				'weight_limits'       => [
					[
						'weight' => 5.0,
						'price'  => 30.0,
					],
				],
				'free_shipping_limit' => 90.0,
			],
		);

		return [
			'no customer country -> empty result'        => [
				'allowedCarrierNames'       => null,
				'methodId'                  => ShippingMethod::PACKETERY_METHOD_ID,
				'instanceId'                => 1,
				'customerCountry'           => null,
				'availableCarriers'         => [],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'disallowedRateIds'         => [],
				'oversized'                 => false,
				'restrictedByCategory'      => false,
				'arePricesTaxInclusive'     => false,
				'rateCost'                  => 10.0,
				'expectedRateCount'         => 0,
			],
			'packetery method with 2 carriers in same country' => [
				'allowedCarrierNames'       => null,
				'methodId'                  => ShippingMethod::PACKETERY_METHOD_ID,
				'instanceId'                => 1,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [ [ $carrierCzFirst, $carrierOptionsCzFirst ], [ $carrierCzSecond, $carrierOptionsCzSecond ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'disallowedRateIds'         => [],
				'oversized'                 => false,
				'restrictedByCategory'      => false,
				'arePricesTaxInclusive'     => false,
				'rateCost'                  => 10.0,
				'expectedRateCount'         => 2,
			],
			'specific carrier method with matching country' => [
				'allowedCarrierNames'       => null,
				'methodId'                  => BaseShippingMethod::PACKETA_METHOD_PREFIX . $carrierSk->getId(),
				'instanceId'                => 7,
				'customerCountry'           => 'sk',
				'availableCarriers'         => [ [ $carrierSk, $carrierOptionsSk ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'disallowedRateIds'         => [],
				'oversized'                 => false,
				'restrictedByCategory'      => false,
				'arePricesTaxInclusive'     => false,
				'rateCost'                  => 10.0,
				'expectedRateCount'         => 1,
			],
			'specific carrier method with age verification not available' => [
				'allowedCarrierNames'       => null,
				'methodId'                  => BaseShippingMethod::PACKETA_METHOD_PREFIX . $carrierSk->getId(),
				'instanceId'                => 7,
				'customerCountry'           => 'sk',
				'availableCarriers'         => [ [ $carrierSk, $carrierOptionsSk ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => true,
				'disallowedRateIds'         => [],
				'oversized'                 => false,
				'restrictedByCategory'      => false,
				'arePricesTaxInclusive'     => false,
				'rateCost'                  => 10.0,
				'expectedRateCount'         => 0,
			],
			'specific carrier method with mismatched country' => [
				'allowedCarrierNames'       => null,
				'methodId'                  => BaseShippingMethod::PACKETA_METHOD_PREFIX . $carrierSk->getId(),
				'instanceId'                => 7,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [ [ $carrierSk, $carrierOptionsSk ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'disallowedRateIds'         => [],
				'oversized'                 => false,
				'restrictedByCategory'      => false,
				'arePricesTaxInclusive'     => false,
				'rateCost'                  => 10.0,
				'expectedRateCount'         => 0,
			],
			'allowedCarrierNames filter allows only one' => [
				'allowedCarrierNames'       => [ $carrierCzFirst->getId() => 'Custom CZ' ],
				'methodId'                  => ShippingMethod::PACKETERY_METHOD_ID,
				'instanceId'                => 1,
				'customerCountry'           => 'cz',
				'availableCarriers'         => [ [ $carrierCzFirst, $carrierOptionsCzFirst ], [ $carrierSk, $carrierOptionsSk ] ],
				'cartTotal'                 => 100.0,
				'cartWeight'                => 1.0,
				'totalValue'                => 100.0,
				'isAgeVerificationRequired' => false,
				'disallowedRateIds'         => [],
				'oversized'                 => false,
				'restrictedByCategory'      => false,
				'arePricesTaxInclusive'     => false,
				'rateCost'                  => 10.0,
				'expectedRateCount'         => 1,
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
		array $disallowedRateIds,
		bool $oversized,
		bool $restrictedByCategory,
		bool $arePricesTaxInclusive,
		float $rateCost,
		int $expectedRateCount,
	): void {
		$shippingRateFactory = $this->createShippingRateFactory();

		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( $customerCountry );

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
		$this->cartServiceMock->method( 'getDisallowedShippingRateIds' )->willReturn( $disallowedRateIds );
		$this->cartServiceMock->method( 'cartContainsProductOversizedForCarrier' )->willReturn( $oversized );
		$this->cartServiceMock->method( 'isShippingRateRestrictedByProductsCategory' )->willReturn( $restrictedByCategory );

		$this->optionsProviderMock->method( 'arePricesTaxInclusive' )->willReturn( $arePricesTaxInclusive );

		$shippingRates = $shippingRateFactory->createShippingRates( $allowedCarrierNames, $methodId, $instanceId );

		self::assertCount( $expectedRateCount, $shippingRates );
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
