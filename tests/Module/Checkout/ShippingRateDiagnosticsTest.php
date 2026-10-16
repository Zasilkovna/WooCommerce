<?php

declare( strict_types=1 );

namespace Tests\Module\Checkout;

use Packetery\Core\Entity\Carrier;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\Options;
use Packetery\Module\Checkout\CartService;
use Packetery\Module\Checkout\CheckoutService;
use Packetery\Module\Checkout\CurrencySwitcherService;
use Packetery\Module\Checkout\RateCalculator;
use Packetery\Module\Checkout\RateUnavailability;
use Packetery\Module\Checkout\RateUnavailabilityReason;
use Packetery\Module\Checkout\ShippingRateDiagnostics;
use Packetery\Module\Checkout\ShippingRateEvaluation;
use Packetery\Module\DiagnosticsLogger\DiagnosticsLogger;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionNames;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Shipping\BaseShippingMethod;
use Packetery\Module\ShippingMethod;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Core\DummyFactory;
use WC_Shipping_Rate;
use WC_Shipping_Zone;

class ShippingRateDiagnosticsTest extends TestCase {

	private const SHIPPING_COUNTRIES = [
		'CZ' => 'Czechia',
		'SK' => 'Slovakia',
		'VE' => 'Venezuela',
	];

	private const ZONE_NOT_COVERED = 'Locations not covered by your other zones';

	private const GENERATED_AT_NOW     = '2026-09-02 12:00:00';
	private const GENERATED_AT_EARLIER = '2026-09-02 11:00:00';

	private WpAdapter&MockObject $wpAdapterMock;
	private WcAdapter&MockObject $wcAdapterMock;
	private CartService&MockObject $cartServiceMock;
	private CheckoutService&MockObject $checkoutServiceMock;
	private EntityRepository&MockObject $carrierEntityRepositoryMock;
	private CarrierOptionsFactory&MockObject $carrierOptionsFactoryMock;

	/**
	 * @var array<string, mixed>|null
	 */
	private ?array $storedSnapshot = null;

	/**
	 * Rates WooCommerce is left with - anything our method returned and this list omits was removed
	 * by a third party.
	 *
	 * @var array<string, WC_Shipping_Rate>
	 */
	private array $finalShippingRates = [];

	/**
	 * Method ids WooCommerce has a class for - a carrier switched off in Packeta settings is missing.
	 *
	 * @var string[]
	 */
	private array $registeredMethodIds = [
		ShippingMethod::PACKETERY_METHOD_ID,
		BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-cz',
		BaseShippingMethod::PACKETA_METHOD_PREFIX . 'other-carrier',
	];

	private function createDiagnostics(
		bool $loggingEnabled,
		bool $userCan,
		bool $isPerCarrierMode = true
	): ShippingRateDiagnostics {
		$this->wpAdapterMock       = $this->createMock( WpAdapter::class );
		$this->wcAdapterMock       = $this->createMock( WcAdapter::class );
		$this->cartServiceMock     = $this->createMock( CartService::class );
		$this->checkoutServiceMock = $this->createMock( CheckoutService::class );

		$this->carrierEntityRepositoryMock = $this->createMock( EntityRepository::class );
		$this->carrierOptionsFactoryMock   = $this->createMock( CarrierOptionsFactory::class );

		$this->wpAdapterMock->method( 'getOption' )
			->willReturnCallback(
				function ( string $option ) use ( $loggingEnabled ) {
					return $option === OptionNames::PACKETERY_DIAGNOSTICS_LOGGING_ENABLED ? $loggingEnabled : null;
				}
			);
		$this->wpAdapterMock->method( 'currentUserCan' )->willReturn( $userCan );
		$this->wpAdapterMock->method( 'getCurrentUserId' )->willReturn( 1 );
		$this->wpAdapterMock->method( 'date' )->willReturn( self::GENERATED_AT_NOW );
		$this->wcAdapterMock->method( 'getFinalShippingRates' )
			->willReturnCallback(
				function (): array {
					return $this->finalShippingRates;
				}
			);
		$this->wcAdapterMock->method( 'shippingGetRegisteredMethodIds' )
			->willReturnCallback(
				function (): array {
					return $this->registeredMethodIds;
				}
			);
		$this->wpAdapterMock->method( 'setTransient' )
			->willReturnCallback(
				function ( string $name, $value ): bool {
					$this->storedSnapshot = $value;

					return true;
				}
			);

		$optionsProviderMock = $this->createMock( OptionsProvider::class );
		$optionsProviderMock->method( 'isWcCarrierConfigEnabled' )->willReturn( $isPerCarrierMode );

		return new ShippingRateDiagnostics(
			$this->wpAdapterMock,
			$this->wcAdapterMock,
			$optionsProviderMock,
			$this->cartServiceMock,
			$this->checkoutServiceMock,
			$this->carrierEntityRepositoryMock,
			$this->carrierOptionsFactoryMock
		);
	}

	public static function activationProvider(): array {
		return [
			'logging off, user allowed'     => [ false, true, false ],
			'logging on, user not allowed'  => [ true, false, false ],
			'logging off, user not allowed' => [ false, false, false ],
			'logging on, user allowed'      => [ true, true, true ],
		];
	}

	/**
	 * @dataProvider activationProvider
	 */
	public function testIsActiveNeedsBothLoggingAndCapability( bool $loggingEnabled, bool $userCan, bool $expected ): void {
		self::assertSame( $expected, $this->createDiagnostics( $loggingEnabled, $userCan )->isActive() );
	}

	public function testNothingIsStoredWhenInactive(): void {
		$diagnostics = $this->createDiagnostics( false, false );
		$diagnostics->collectEvaluation( new ShippingRateEvaluation( 'zpoint-cz', 'Z-Point', null, [] ) );
		$diagnostics->saveSnapshot();

		self::assertNull( $this->storedSnapshot );
	}

	public function testRequestServedFromCacheKeepsTheStoredSnapshot(): void {
		$diagnostics = $this->createDiagnosticsWithZone( true );
		$this->wpAdapterMock->method( 'getTransient' )->willReturn(
			[
				'schemaVersion'   => 1,
				'methodRan'       => true,
				'customerCountry' => 'cz',
			]
		);

		$diagnostics->saveSnapshot();

		self::assertNull( $this->storedSnapshot );
	}

	public function testRequestServedFromCacheForAnotherCountryReplacesTheStoredSnapshot(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->expectStoredSnapshotOfCountry( 'cz' );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'sk' );
		$this->wcAdapterMock->method( 'countriesGetShippingCountries' )->willReturn( self::SHIPPING_COUNTRIES );
		$this->carrierEntityRepositoryMock->method( 'getByCountryIncludingNonFeed' )
			->willReturn( [ DummyFactory::createCarrierSlovakPp() ] );
		$this->expectZoneMethodStates( [ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-cz' => true ] );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame( 'sk', $this->storedSnapshot['customerCountry'] );
		self::assertFalse( $this->storedSnapshot['methodRan'] );
		self::assertSame( [], $this->storedSnapshot['packageUnavailabilities'] );
	}

	public function testZoneWithAnActiveCarrierBesideAnInactiveOneIsNotBlamed(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'sk' );
		$this->wcAdapterMock->method( 'countriesGetShippingCountries' )->willReturn( self::SHIPPING_COUNTRIES );
		$this->carrierEntityRepositoryMock->method( 'getByCountryIncludingNonFeed' )
			->willReturn( [ DummyFactory::createCarrierSlovakPp() ] );
		$this->expectCarrierOptions( false );
		$this->expectZoneMethodStates(
			[
				BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-cz'     => true,
				BaseShippingMethod::PACKETA_METHOD_PREFIX . 'other-carrier' => true,
			],
			self::ZONE_NOT_COVERED
		);

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame( [], $this->storedSnapshot['packageUnavailabilities'] );
	}

	/**
	 * A carrier switched off in Packeta settings does not register its method class,
	 * so WooCommerce never asks the method its zone row stands for.
	 */
	public function testZoneRowOfInactiveCarrierDoesNotHideTheMissingZone(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->expectStoredSnapshotOfCountry( 'cz' );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'sk' );
		$this->wcAdapterMock->method( 'countriesGetShippingCountries' )->willReturn( self::SHIPPING_COUNTRIES );
		$this->carrierEntityRepositoryMock->method( 'getByCountryIncludingNonFeed' )
			->willReturn( [ DummyFactory::createCarrierSlovakPp() ] );
		$this->expectCarrierOptions( false );
		$this->expectZoneMethodStates(
			[ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-cz' => true ],
			self::ZONE_NOT_COVERED
		);

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame( 'sk', $this->storedSnapshot['customerCountry'] );
		self::assertSame(
			[ RateUnavailabilityReason::NO_SHIPPING_ZONE ],
			array_column( $this->storedSnapshot['packageUnavailabilities'], 'reason' )
		);
		self::assertSame( self::ZONE_NOT_COVERED, $this->storedSnapshot['packageUnavailabilities'][0]['context']['zoneName'] );
	}

	public function testRecordOfAnOlderShapeIsDiscardedInsteadOfRead(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->wpAdapterMock->method( 'getTransient' )->willReturn( [ 'methodRan' => true ] );

		self::assertNull( $diagnostics->getSnapshot() );
	}

	/**
	 * @dataProvider countryWithoutAvailableCarrierProvider
	 *
	 * @param Carrier[] $carriersOfCountry
	 */
	public function testMethodThatRanWithoutACarrierForTheCountryIsNotReportedAsNeverAsked( array $carriersOfCountry ): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$diagnostics->collectMethodRun();
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 've' );
		$this->carrierEntityRepositoryMock->method( 'getByCountryIncludingNonFeed' )->willReturn( $carriersOfCountry );
		$this->expectCarrierOptions( false );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertTrue( $this->storedSnapshot['methodRan'] );
		self::assertSame(
			[ RateUnavailabilityReason::NO_CARRIER_FOR_COUNTRY ],
			array_column( $this->storedSnapshot['packageUnavailabilities'], 'reason' )
		);
	}

	/**
	 * A switched-on row of an unavailable carrier cannot make Packeta deliver to the country.
	 */
	public function testSwitchedOnRowOfAnUnavailableCarrierDoesNotHideTheMissingCarrier(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$diagnostics->collectMethodRun();
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 've' );
		$this->carrierEntityRepositoryMock->method( 'getByCountryIncludingNonFeed' )
			->willReturn( [ self::createCarrierOfCountry( 've', false ) ] );
		$this->expectCarrierOptions( true );
		$this->expectZoneMethodStates( [ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-ve' => true ] );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertTrue( $this->storedSnapshot['methodRan'] );
		self::assertSame(
			[ RateUnavailabilityReason::NO_CARRIER_FOR_COUNTRY ],
			array_column( $this->storedSnapshot['packageUnavailabilities'], 'reason' )
		);
	}

	/**
	 * A carrier switched off in Packeta keeps its row in the zone, so claiming that no method of ours
	 * serves the country would contradict the row the table shows for that very carrier.
	 */
	public function testCarrierWithARowInTheZoneSilencesThePackageLevelReason(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$diagnostics->collectMethodRun();
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->expectActiveCzechCarrier( false );
		$this->expectZoneMethodStates( [ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-cz' => true ] );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame( [], $this->storedSnapshot['packageUnavailabilities'] );
		self::assertSame(
			[ RateUnavailabilityReason::CARRIER_OPTION_INACTIVE ],
			array_column( $this->storedSnapshot['carriers'][0]['unavailabilities'], 'reason' )
		);
	}

	public static function carrierActivityProvider(): array {
		return [
			'carrier active in Packeta settings'   => [ true, false ],
			'carrier inactive in Packeta settings' => [ false, true ],
		];
	}

	/**
	 * @dataProvider carrierActivityProvider
	 */
	public function testCarriersOutsideEveryZoneAreReportedOnThePackageLevel( bool $isCarrierActive, bool $expectedNoActiveCarrier ): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$diagnostics->collectMethodRun();
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->expectActiveCzechCarrier( $isCarrierActive );
		$this->expectZoneMethodStates( [ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'other-carrier' => true ] );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame(
			[ RateUnavailabilityReason::NO_ZONE_METHOD_FOR_COUNTRY ],
			array_column( $this->storedSnapshot['packageUnavailabilities'], 'reason' )
		);
		self::assertSame(
			[
				'customerCountry' => 'cz',
				'noActiveCarrier' => $expectedNoActiveCarrier,
			],
			$this->storedSnapshot['packageUnavailabilities'][0]['context']
		);
	}

	/**
	 * A carrier unavailable in the feed gives no rate even when it is active, so neither carrier counts as active.
	 */
	public function testCarrierCountsAsActiveOnlyWhenItIsAlsoAvailable(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$diagnostics->collectMethodRun();
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->carrierEntityRepositoryMock->method( 'getByCountryIncludingNonFeed' )
			->willReturn( [ DummyFactory::createCarrierCzzpoint(), self::createCarrierOfCountry( 'cz', false ) ] );
		$inactiveOptionsMock = $this->createMock( Options::class );
		$inactiveOptionsMock->method( 'isActive' )->willReturn( false );
		$activeOptionsMock = $this->createMock( Options::class );
		$activeOptionsMock->method( 'isActive' )->willReturn( true );
		$this->carrierOptionsFactoryMock->method( 'createByCarrierId' )
			->willReturnMap( [ [ 'czzpoint', $inactiveOptionsMock ], [ 'zpoint-cz', $activeOptionsMock ] ] );
		$this->expectZoneMethodStates( [ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'other-carrier' => true ] );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame(
			[
				'customerCountry' => 'cz',
				'noActiveCarrier' => true,
			],
			$this->storedSnapshot['packageUnavailabilities'][0]['context']
		);
	}

	public function testReasonFoundWithoutTheMethodRunningOverwritesTheStoredSnapshot(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->wpAdapterMock->method( 'getTransient' )->willReturn(
			[
				'schemaVersion' => 1,
				'methodRan'     => true,
			]
		);
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'de' );
		$this->expectAvailableCarrierOfCountry();
		$this->wcAdapterMock->method( 'countriesGetShippingCountries' )->willReturn( [ 'CZ' => 'Czechia' ] );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertFalse( $this->storedSnapshot['methodRan'] );
		self::assertSame(
			[ RateUnavailabilityReason::SHIPPING_COUNTRY_NOT_ALLOWED ],
			array_column( $this->storedSnapshot['packageUnavailabilities'], 'reason' )
		);
	}

	public function testSnapshotKeepsEveryReasonOfEachCarrier(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$diagnostics->collectMethodRun();
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->cartServiceMock->method( 'getCartWeightKg' )->willReturn( 6.2 );

		$diagnostics->collectEvaluation(
			new ShippingRateEvaluation(
				'zpoint-cz',
				'Z-Point',
				null,
				[
					new RateUnavailability( RateUnavailabilityReason::DISALLOWED_BY_PRODUCT, [ 'productId' => 42 ] ),
					new RateUnavailability(
						RateUnavailabilityReason::WEIGHT_OVER_LIMIT,
						[
							'cartWeight'   => 6.2,
							'highestLimit' => 5.0,
						]
					),
				]
			)
		);
		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertTrue( $this->storedSnapshot['methodRan'] );
		self::assertCount( 1, $this->storedSnapshot['carriers'] );

		$carrier = $this->storedSnapshot['carriers'][0];
		self::assertSame( 'Z-Point', $carrier['carrierName'] );
		self::assertFalse( $carrier['isOffered'] );
		self::assertSame(
			[ RateUnavailabilityReason::DISALLOWED_BY_PRODUCT, RateUnavailabilityReason::WEIGHT_OVER_LIMIT ],
			array_column( $carrier['unavailabilities'], 'reason' )
		);
		self::assertSame( 5.0, $carrier['unavailabilities'][1]['context']['highestLimit'] );
	}

	public function testOfferedCarrierIsStoredWithCustomerFacingPrice(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$diagnostics->collectMethodRun();
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );

		$rate = [
			'label'    => 'Z-Point',
			'id'       => 'packetery_shipping_method:zpoint-cz',
			'cost'     => 82.64,
			'taxes'    => [ 1 => 17.36 ],
			'calc_tax' => 'per_order',
		];
		$this->expectFinalRate( 'packetery_shipping_method:zpoint-cz', 82.64, 17.36, true );

		$diagnostics->collectEvaluation( new ShippingRateEvaluation( 'zpoint-cz', 'Z-Point', $rate, [] ) );
		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertTrue( $this->storedSnapshot['carriers'][0]['isOffered'] );
		self::assertSame( 100.0, $this->storedSnapshot['carriers'][0]['costInCheckout'] );
	}

	public function testRateMissingBecauseOfFreeShippingNamesTheShopSetting(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$diagnostics->collectMethodRun();
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );

		$rate = [
			'label'    => 'Z-Point',
			'id'       => 'packetery_shipping_method:zpoint-cz',
			'cost'     => 82.64,
			'taxes'    => [ 1 => 17.36 ],
			'calc_tax' => 'per_order',
		];
		$this->expectFinalRate( 'free_shipping:5', 0.0, 0.0, true );
		$this->wcAdapterMock->method( 'areOtherRatesHiddenByFreeShipping' )->willReturn( true );

		$diagnostics->collectEvaluation( new ShippingRateEvaluation( 'zpoint-cz', 'Z-Point', $rate, [] ) );
		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame(
			[ RateUnavailabilityReason::RATE_HIDDEN_BY_FREE_SHIPPING ],
			array_column( $this->storedSnapshot['carriers'][0]['unavailabilities'], 'reason' )
		);
	}

	public function testRateRemovedAfterCalculationIsReportedAsHidden(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$diagnostics->collectMethodRun();
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );

		$rate = [
			'label'    => 'Z-Point',
			'id'       => 'packetery_shipping_method:zpoint-cz',
			'cost'     => 82.64,
			'taxes'    => [ 1 => 17.36 ],
			'calc_tax' => 'per_order',
		];
		$this->expectFinalRate( 'flat_rate:3', 55.0, 0.0, true );

		$diagnostics->collectEvaluation( new ShippingRateEvaluation( 'zpoint-cz', 'Z-Point', $rate, [] ) );
		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertFalse( $this->storedSnapshot['carriers'][0]['isOffered'] );
		self::assertSame(
			[ RateUnavailabilityReason::RATE_REMOVED_AFTER_CALCULATION ],
			array_column( $this->storedSnapshot['carriers'][0]['unavailabilities'], 'reason' )
		);
	}

	public static function taxDisplayProvider(): array {
		return [
			'shop shows shipping with tax'    => [ true, 100.0 ],
			'shop shows shipping without tax' => [ false, 82.64 ],
		];
	}

	/**
	 * The rate our method returns carries no tax whenever the client enters carrier prices without it
	 * - WooCommerce works it out afterwards - so the amount has to come from the final rate and follow
	 * the display setting the checkout itself follows.
	 *
	 * @dataProvider taxDisplayProvider
	 */
	public function testStoredPriceFollowsTheTaxDisplaySettingOfTheCart(
		bool $displayIncludingTax,
		float $expectedCost
	): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$diagnostics->collectMethodRun();
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );

		$rateCalculator = new RateCalculator(
			$this->createMock( WpAdapter::class ),
			$this->createMock( WcAdapter::class ),
			$this->createMock( CurrencySwitcherService::class ),
			$this->createMock( DiagnosticsLogger::class )
		);
		$rate           = $rateCalculator->createShippingRate( 'Z-Point', 'zpoint-cz', 82.64, null );

		$this->expectFinalRate( 'zpoint-cz', 82.64, 17.36, $displayIncludingTax );

		$diagnostics->collectEvaluation( new ShippingRateEvaluation( 'zpoint-cz', 'Z-Point', $rate, [] ) );
		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame( $expectedCost, $this->storedSnapshot['carriers'][0]['costInCheckout'] );
	}

	public function testUnknownCountryIsReportedWhenMethodWasNeverAsked(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( null );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertFalse( $this->storedSnapshot['methodRan'] );
		self::assertSame(
			[ RateUnavailabilityReason::CUSTOMER_COUNTRY_UNKNOWN ],
			array_column( $this->storedSnapshot['packageUnavailabilities'], 'reason' )
		);
	}

	public static function carriersOfNotAllowedCountryProvider(): array {
		return array_merge(
			[ 'an available carrier' => [ [ self::createCarrierOfCountry( 've', true ) ] ] ],
			self::countryWithoutAvailableCarrierProvider()
		);
	}

	/**
	 * @dataProvider carriersOfNotAllowedCountryProvider
	 *
	 * @param Carrier[] $carriersOfCountry
	 */
	public function testCountryOutsideShippingLocationsIsReported( array $carriersOfCountry ): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 've' );
		$this->carrierEntityRepositoryMock->method( 'getByCountryIncludingNonFeed' )->willReturn( $carriersOfCountry );
		$this->expectCarrierOptions( false );
		$this->wcAdapterMock->method( 'countriesGetShippingCountries' )->willReturn( [ 'CZ' => 'Czechia' ] );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame(
			[ RateUnavailabilityReason::SHIPPING_COUNTRY_NOT_ALLOWED ],
			array_column( $this->storedSnapshot['packageUnavailabilities'], 'reason' )
		);
	}

	private function createDiagnosticsWithZone( bool $zoneHasPacketaMethod ): ShippingRateDiagnostics {
		$diagnostics = $this->createDiagnostics( true, true, false );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->expectAvailableCarrierOfCountry();
		$this->wcAdapterMock->method( 'countriesGetShippingCountries' )->willReturn( [ 'CZ' => 'Czechia' ] );
		$this->wcAdapterMock->method( 'cartGetShippingPackages' )->willReturn( [ [ 'destination' => [ 'country' => 'CZ' ] ] ] );

		$zoneMock = $this->createMock( WC_Shipping_Zone::class );
		$zoneMock->method( 'get_zone_name' )->willReturn( 'Czechia' );
		$this->wcAdapterMock->method( 'shippingZonesGetZoneMatchingPackage' )->willReturn( $zoneMock );
		$this->wcAdapterMock->method( 'shippingZoneGetMethodStates' )->willReturn(
			$zoneHasPacketaMethod ? [ ShippingMethod::PACKETERY_METHOD_ID => true ] : [ 'flat_rate' => true ]
		);

		return $diagnostics;
	}

	private static function createCarrierOfCountry( string $country, bool $isAvailable ): Carrier {
		return new Carrier(
			"zpoint-{$country}",
			"zpoint-{$country}",
			true,
			false,
			false,
			false,
			true,
			true,
			false,
			true,
			$country,
			'CZK',
			5.0,
			$isAvailable,
			false,
			true
		);
	}

	private function expectActiveCzechCarrier( bool $isActive, bool $isAvailableInFeed = true ): void {
		$this->carrierEntityRepositoryMock->method( 'getByCountryIncludingNonFeed' )
			->willReturn( [ $isAvailableInFeed ? DummyFactory::createCarrierCzechPp() : self::createCarrierOfCountry( 'cz', false ) ] );

		$this->expectCarrierOptions( $isActive );
	}

	/**
	 * An inactive carrier also loses its method id from the registered ones, as in WooCommerce.
	 */
	private function expectCarrierOptions( bool $isActive ): void {
		$carrierOptionsMock = $this->createMock( Options::class );
		$carrierOptionsMock->method( 'isActive' )->willReturn( $isActive );
		$carrierOptionsMock->method( 'getName' )->willReturn( 'Z-Point' );
		$this->carrierOptionsFactoryMock->method( 'createByCarrierId' )->willReturn( $carrierOptionsMock );

		if ( $isActive === false ) {
			$this->registeredMethodIds = array_values(
				array_diff( $this->registeredMethodIds, [ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-cz' ] )
			);
		}
	}

	private function expectFinalRate( string $rateId, float $cost, float $tax, bool $displayIncludingTax ): void {
		$rateMock = $this->createMock( WC_Shipping_Rate::class );
		$rateMock->method( 'get_cost' )->willReturn( $cost );
		$rateMock->method( 'get_shipping_tax' )->willReturn( $tax );

		$this->finalShippingRates = [ $rateId => $rateMock ];
		$this->wcAdapterMock->method( 'cartDisplayPricesIncludingTax' )->willReturn( $displayIncludingTax );
	}

	private function expectAvailableCarrierOfCountry(): void {
		$this->carrierEntityRepositoryMock->method( 'getByCountryIncludingNonFeed' )
			->willReturn( [ DummyFactory::createCarrierCzechPp() ] );
	}

	private function expectStoredSnapshotOfCountry( string $customerCountry ): void {
		$this->wpAdapterMock->method( 'getTransient' )->willReturn(
			[
				'schemaVersion'   => 1,
				'methodRan'       => true,
				'customerCountry' => $customerCountry,
			]
		);
	}

	private function createZoneMock( string $zoneName ): WC_Shipping_Zone&MockObject {
		$zoneMock = $this->createMock( WC_Shipping_Zone::class );
		$zoneMock->method( 'get_zone_name' )->willReturn( $zoneName );

		return $zoneMock;
	}

	/**
	 * @param array<string, bool> $states
	 */
	private function expectZoneMethodStates( array $states, string $zoneName = 'Czechia' ): void {
		$this->wcAdapterMock->method( 'cartGetShippingPackages' )->willReturn( [ [ 'contents' => [] ] ] );
		$this->wcAdapterMock->method( 'shippingZonesGetZoneMatchingPackage' )
			->willReturn( $this->createZoneMock( $zoneName ) );
		$this->wcAdapterMock->method( 'shippingZoneGetMethodStates' )->willReturn( $states );
	}

	public function testCarrierActiveInPacketaButOutsideTheZoneGetsARow(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$diagnostics->collectMethodRun();
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->expectActiveCzechCarrier( true );

		$diagnostics->collectEvaluation( new ShippingRateEvaluation( 'zpoint-cz-hd', 'Home delivery', null, [] ) );
		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertCount( 2, $this->storedSnapshot['carriers'] );

		$missingCarrier = $this->storedSnapshot['carriers'][1];
		self::assertSame( 'zpoint-cz', $missingCarrier['carrierId'] );
		self::assertSame( 'Z-Point', $missingCarrier['carrierName'] );
		self::assertFalse( $missingCarrier['isOffered'] );
		self::assertNull( $missingCarrier['costInCheckout'] );
		self::assertSame(
			[ RateUnavailabilityReason::CARRIER_NOT_IN_SHIPPING_ZONE ],
			array_column( $missingCarrier['unavailabilities'], 'reason' )
		);
	}

	public function testCarrierUnavailableInTheFeedIsNotBlamedOnTheZone(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$diagnostics->collectMethodRun();
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->expectActiveCzechCarrier( true, false );

		$diagnostics->collectEvaluation( new ShippingRateEvaluation( 'zpoint-cz-hd', 'Home delivery', null, [] ) );
		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertCount( 2, $this->storedSnapshot['carriers'] );
		self::assertSame(
			[ RateUnavailabilityReason::CARRIER_UNAVAILABLE ],
			array_column( $this->storedSnapshot['carriers'][1]['unavailabilities'], 'reason' )
		);
	}

	public function testCarrierInactiveInPacketaIsNotBlamedOnTheZone(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$diagnostics->collectMethodRun();
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->expectActiveCzechCarrier( false );

		$diagnostics->collectEvaluation( new ShippingRateEvaluation( 'zpoint-cz-hd', 'Home delivery', null, [] ) );
		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertCount( 1, $this->storedSnapshot['carriers'] );
	}

	public function testCarrierOutsideEveryZoneIsListedEvenWithoutASingleEvaluation(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->expectActiveCzechCarrier( true );
		$this->wcAdapterMock->method( 'countriesGetShippingCountries' )->willReturn( [ 'CZ' => 'Czechia' ] );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertCount( 1, $this->storedSnapshot['carriers'] );
		self::assertSame(
			[ RateUnavailabilityReason::CARRIER_NOT_IN_SHIPPING_ZONE ],
			array_column( $this->storedSnapshot['carriers'][0]['unavailabilities'], 'reason' )
		);
	}

	public static function storedRecordProvider(): array {
		return [
			'no record stored yet'             => [ null ],
			'older record of the same country' => [
				[
					'schemaVersion'   => 1,
					'methodRan'       => true,
					'customerCountry' => 'cz',
				],
			],
		];
	}

	/**
	 * No Packeta method could be asked, so the rates did not come from the cache,
	 * and an older record of the same country is replaced too.
	 *
	 * @param array<string, mixed>|null $storedRecord
	 *
	 * @dataProvider storedRecordProvider
	 */
	public function testCarrierInTheZoneButSwitchedOffInPacketaIsNamed( ?array $storedRecord ): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->wpAdapterMock->method( 'getTransient' )->willReturn( $storedRecord );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->wcAdapterMock->method( 'countriesGetShippingCountries' )->willReturn( self::SHIPPING_COUNTRIES );
		$this->expectActiveCzechCarrier( false );
		$this->expectZoneMethodStates( [ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-cz' => true ] );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame( [], $this->storedSnapshot['packageUnavailabilities'] );
		self::assertCount( 1, $this->storedSnapshot['carriers'] );
		self::assertSame(
			[ RateUnavailabilityReason::CARRIER_OPTION_INACTIVE ],
			array_column( $this->storedSnapshot['carriers'][0]['unavailabilities'], 'reason' )
		);
	}

	public static function carrierModeProvider(): array {
		return [
			'per-carrier method' => [ true, BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-cz' ],
			'legacy method'      => [ false, ShippingMethod::PACKETERY_METHOD_ID ],
		];
	}

	/**
	 * The row switched off in the zone is named in the table,
	 * so the zone is not blamed on the package level.
	 *
	 * @dataProvider carrierModeProvider
	 */
	public function testCarrierWhoseMethodIsSwitchedOffInTheZoneIsNotBlamedOnPacketaSettings(
		bool $isPerCarrierMode,
		string $zoneMethodId
	): void {
		$diagnostics = $this->createDiagnostics( true, true, $isPerCarrierMode );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->wcAdapterMock->method( 'countriesGetShippingCountries' )->willReturn( self::SHIPPING_COUNTRIES );
		$this->expectActiveCzechCarrier( true );
		$this->expectZoneMethodStates( [ $zoneMethodId => false ] );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame( [], $this->storedSnapshot['packageUnavailabilities'] );
		self::assertSame(
			[ RateUnavailabilityReason::CARRIER_METHOD_DISABLED_IN_ZONE ],
			array_column( $this->storedSnapshot['carriers'][0]['unavailabilities'], 'reason' )
		);
	}

	/**
	 * The carrier has to be switched on in Packeta settings before its row in the zone can do anything,
	 * so the row names the Packeta settings first.
	 *
	 * @dataProvider carrierModeProvider
	 */
	public function testInactiveCarrierWithRowSwitchedOffInTheZoneIsSentToPacketaSettingsFirst(
		bool $isPerCarrierMode,
		string $zoneMethodId
	): void {
		$diagnostics = $this->createDiagnostics( true, true, $isPerCarrierMode );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->wcAdapterMock->method( 'countriesGetShippingCountries' )->willReturn( self::SHIPPING_COUNTRIES );
		$this->expectActiveCzechCarrier( false );
		$this->expectZoneMethodStates( [ $zoneMethodId => false ] );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame( [], $this->storedSnapshot['packageUnavailabilities'] );
		self::assertSame(
			[ RateUnavailabilityReason::CARRIER_OPTION_INACTIVE ],
			array_column( $this->storedSnapshot['carriers'][0]['unavailabilities'], 'reason' )
		);
	}

	/**
	 * Switching the shop from the legacy method to the per-carrier ones leaves the legacy row in the
	 * zone behind, and reading it would decide about every carrier at once.
	 */
	public function testLeftoverRowOfTheOtherModeIsIgnored(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->expectActiveCzechCarrier( true );
		$this->expectZoneMethodStates( [ ShippingMethod::PACKETERY_METHOD_ID => true ] );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame(
			[ RateUnavailabilityReason::CARRIER_NOT_IN_SHIPPING_ZONE ],
			array_column( $this->storedSnapshot['carriers'][0]['unavailabilities'], 'reason' )
		);
	}

	public function testLegacyMethodInTheZoneSpeaksForEveryCarrier(): void {
		$diagnostics = $this->createDiagnostics( true, true, false );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->expectActiveCzechCarrier( false );
		$this->expectZoneMethodStates( [ ShippingMethod::PACKETERY_METHOD_ID => true ] );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame(
			[ RateUnavailabilityReason::CARRIER_OPTION_INACTIVE ],
			array_column( $this->storedSnapshot['carriers'][0]['unavailabilities'], 'reason' )
		);
	}

	public function testCarrierReadyInBothPlacesSaysNothingWhenTheRatesCameFromTheCache(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->expectActiveCzechCarrier( true );
		$this->expectZoneMethodStates( [ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-cz' => true ] );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame( [], $this->storedSnapshot['carriers'] );
	}

	public function testPacketaMethodInAnyPackageZoneClearsTheZones(): void {
		$diagnostics = $this->createDiagnostics( true, true, false );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->expectAvailableCarrierOfCountry();
		$this->wcAdapterMock->method( 'countriesGetShippingCountries' )->willReturn( [ 'CZ' => 'Czechia' ] );
		$this->wcAdapterMock->method( 'cartGetShippingPackages' )->willReturn(
			[ [ 'contents' => [ 'first' ] ], [ 'contents' => [ 'second' ] ] ]
		);

		$zoneWithoutMethod = $this->createMock( WC_Shipping_Zone::class );
		$zoneWithoutMethod->method( 'get_zone_name' )->willReturn( 'Rest of the world' );

		$zoneWithMethod = $this->createMock( WC_Shipping_Zone::class );
		$zoneWithMethod->method( 'get_zone_name' )->willReturn( 'Czechia' );

		$this->wcAdapterMock->method( 'shippingZonesGetZoneMatchingPackage' )
			->willReturnOnConsecutiveCalls( $zoneWithoutMethod, $zoneWithMethod );
		$this->wcAdapterMock->method( 'shippingZoneGetMethodStates' )
			->willReturnOnConsecutiveCalls( [], [ ShippingMethod::PACKETERY_METHOD_ID => true ] );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame( [], $this->storedSnapshot['packageUnavailabilities'] );
	}

	public static function firstCalculationProvider(): array {
		return [
			'first calculation recorded'              => [ true, self::GENERATED_AT_NOW ],
			'first calculation served from the cache' => [ false, self::GENERATED_AT_EARLIER ],
		];
	}

	/**
	 * The block checkout can calculate the totals twice in one request:
	 * first for the address in the session, then for the new one.
	 *
	 * @dataProvider firstCalculationProvider
	 */
	public function testSecondCalculationInOneRequestDescribesOnlyTheNewCountry(
		bool $firstMethodRan,
		string $expectedFirstGeneratedAt
	): void {
		$diagnostics     = $this->createDiagnostics( true, true );
		$customerCountry = 'cz';
		$czechZone       = $this->createZoneMock( 'Czechia' );
		$slovakZone      = $this->createZoneMock( 'Slovakia' );

		if ( $firstMethodRan === false ) {
			$this->storedSnapshot = [
				'schemaVersion'   => 1,
				'generatedAt'     => self::GENERATED_AT_EARLIER,
				'methodRan'       => true,
				'customerCountry' => 'cz',
			];
		}
		$this->wpAdapterMock->method( 'getTransient' )
			->willReturnCallback(
				function (): ?array {
					return $this->storedSnapshot;
				}
			);
		$this->checkoutServiceMock->method( 'getCustomerCountry' )
			->willReturnCallback(
				function () use ( &$customerCountry ): string {
					return $customerCountry;
				}
			);
		$this->carrierEntityRepositoryMock->method( 'getByCountryIncludingNonFeed' )
			->willReturnCallback(
				function ( string $country ): array {
					return $country === 'cz' ? [ DummyFactory::createCarrierCzechPp() ] : [ DummyFactory::createCarrierSlovakPp() ];
				}
			);
		$this->expectCarrierOptions( true );
		$this->wcAdapterMock->method( 'countriesGetShippingCountries' )->willReturn( self::SHIPPING_COUNTRIES );
		$this->wcAdapterMock->method( 'cartGetShippingPackages' )->willReturn( [ [ 'contents' => [] ] ] );
		$this->wcAdapterMock->method( 'shippingZonesGetZoneMatchingPackage' )
			->willReturnCallback(
				function () use ( &$customerCountry, $czechZone, $slovakZone ): WC_Shipping_Zone {
					return $customerCountry === 'cz' ? $czechZone : $slovakZone;
				}
			);
		$this->wcAdapterMock->method( 'shippingZoneGetMethodStates' )
			->willReturnCallback(
				function ( WC_Shipping_Zone $zone ) use ( $czechZone ): array {
					return $zone === $czechZone
						? [ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-cz' => true ]
						: [ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-sk' => false ];
				}
			);

		if ( $firstMethodRan ) {
			$diagnostics->collectMethodRun();
			$diagnostics->collectEvaluation( new ShippingRateEvaluation( 'zpoint-cz', 'Z-Point', null, [] ) );
		}
		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame( $expectedFirstGeneratedAt, $this->storedSnapshot['generatedAt'] );

		$customerCountry = 'sk';
		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame( 'sk', $this->storedSnapshot['customerCountry'] );
		self::assertFalse( $this->storedSnapshot['methodRan'] );
		self::assertSame( [ 'zpoint-sk' ], array_column( $this->storedSnapshot['carriers'], 'carrierId' ) );
		self::assertSame(
			[ RateUnavailabilityReason::CARRIER_METHOD_DISABLED_IN_ZONE ],
			array_column( $this->storedSnapshot['carriers'][0]['unavailabilities'], 'reason' )
		);
		self::assertSame( [], $this->storedSnapshot['packageUnavailabilities'] );
	}

	public static function rowExplainingNothingProvider(): array {
		return [
			'active carrier whose method class is not registered' => [
				true,
				[ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-cz' => true ],
				[],
				false,
			],
			'switched-on row of a carrier of another country'     => [
				true,
				[ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-sk' => true ],
				[ [ RateUnavailabilityReason::CARRIER_NOT_IN_SHIPPING_ZONE ] ],
				false,
			],
			'inactive carrier without a row, no Packeta row' => [
				false,
				[],
				[],
				true,
			],
			'inactive carrier without a row, row of another country' => [
				false,
				[ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-sk' => true ],
				[],
				true,
			],
		];
	}

	/**
	 * A zone row that WooCommerce cannot ask, and that no row of a carrier of the country explains,
	 * leaves the zone without a Packeta method - and so does a zone with no Packeta row.
	 * When no carrier of the country is active, the zone reason says so too.
	 *
	 * @param bool                 $isCarrierActive
	 * @param array<string, bool>  $zoneMethodStates
	 * @param array<int, string[]> $expectedCarrierReasons
	 * @param bool                 $expectedNoActiveCarrier
	 *
	 * @dataProvider rowExplainingNothingProvider
	 */
	public function testZoneRowExplainingNothingDoesNotHideTheMissingZone(
		bool $isCarrierActive,
		array $zoneMethodStates,
		array $expectedCarrierReasons,
		bool $expectedNoActiveCarrier
	): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->wcAdapterMock->method( 'countriesGetShippingCountries' )->willReturn( self::SHIPPING_COUNTRIES );
		$this->expectActiveCzechCarrier( $isCarrierActive );
		$this->registeredMethodIds = array_values(
			array_diff( $this->registeredMethodIds, [ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-cz' ] )
		);
		$this->expectZoneMethodStates( $zoneMethodStates );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame(
			[ RateUnavailabilityReason::NO_SHIPPING_ZONE ],
			array_column( $this->storedSnapshot['packageUnavailabilities'], 'reason' )
		);
		self::assertSame(
			[
				'zoneName'        => 'Czechia',
				'customerCountry' => 'cz',
				'noActiveCarrier' => $expectedNoActiveCarrier,
			],
			$this->storedSnapshot['packageUnavailabilities'][0]['context']
		);
		self::assertSame(
			$expectedCarrierReasons,
			array_map(
				static function ( array $carrier ): array {
					return array_column( $carrier['unavailabilities'], 'reason' );
				},
				$this->storedSnapshot['carriers']
			)
		);
	}

	public function testZoneWithoutPacketaMethodIsReportedWhenMethodWasNeverAsked(): void {
		$this->createDiagnosticsWithZone( false )->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame(
			[ RateUnavailabilityReason::NO_SHIPPING_ZONE ],
			array_column( $this->storedSnapshot['packageUnavailabilities'], 'reason' )
		);
		self::assertSame( 'Czechia', $this->storedSnapshot['packageUnavailabilities'][0]['context']['zoneName'] );
	}

	/**
	 * In the legacy mode one method serves every carrier, so an inactive carrier does not keep it out of the zone.
	 */
	public function testLegacyModeDoesNotNameInactiveCarriersOnTheZone(): void {
		$diagnostics = $this->createDiagnosticsWithZone( false );
		$this->expectCarrierOptions( false );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame(
			[ RateUnavailabilityReason::NO_SHIPPING_ZONE ],
			array_column( $this->storedSnapshot['packageUnavailabilities'], 'reason' )
		);
		self::assertFalse( $this->storedSnapshot['packageUnavailabilities'][0]['context']['noActiveCarrier'] );
	}

	public static function countryWithoutAvailableCarrierProvider(): array {
		return [
			'no carrier for the country'  => [ [] ],
			'only an unavailable carrier' => [ [ self::createCarrierOfCountry( 've', false ) ] ],
		];
	}

	/**
	 * Without a Packeta carrier for the country, a Packeta method in the zone would change nothing.
	 *
	 * @dataProvider countryWithoutAvailableCarrierProvider
	 *
	 * @param Carrier[] $carriersOfCountry
	 */
	public function testCountryWithoutAvailableCarrierIsNotBlamedOnTheZone( array $carriersOfCountry ): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 've' );
		$this->wcAdapterMock->method( 'countriesGetShippingCountries' )->willReturn( self::SHIPPING_COUNTRIES );
		$this->carrierEntityRepositoryMock->method( 'getByCountryIncludingNonFeed' )->willReturn( $carriersOfCountry );
		$this->expectCarrierOptions( false );
		$this->expectZoneMethodStates( [ 'flat_rate' => true ], self::ZONE_NOT_COVERED );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame(
			[ RateUnavailabilityReason::NO_CARRIER_FOR_COUNTRY ],
			array_column( $this->storedSnapshot['packageUnavailabilities'], 'reason' )
		);
		self::assertSame( [ 'customerCountry' => 've' ], $this->storedSnapshot['packageUnavailabilities'][0]['context'] );
	}

	/**
	 * The method could be asked, so the rates came from the cache and the stored record still applies.
	 *
	 * @dataProvider countryWithoutAvailableCarrierProvider
	 *
	 * @param Carrier[] $carriersOfCountry
	 */
	public function testCountryWithoutAvailableCarrierServedFromCacheKeepsTheStoredSnapshot( array $carriersOfCountry ): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->expectStoredSnapshotOfCountry( 've' );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 've' );
		$this->wcAdapterMock->method( 'countriesGetShippingCountries' )->willReturn( self::SHIPPING_COUNTRIES );
		$this->carrierEntityRepositoryMock->method( 'getByCountryIncludingNonFeed' )->willReturn( $carriersOfCountry );
		$this->expectCarrierOptions( false );
		$this->expectZoneMethodStates(
			[ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'other-carrier' => true ],
			self::ZONE_NOT_COVERED
		);

		$diagnostics->saveSnapshot();

		self::assertNull( $this->storedSnapshot );
	}

	/**
	 * The switched-off row of the unavailable carrier gets its own reason in the table,
	 * but no setting of that row can make Packeta deliver to the country.
	 */
	public function testCountryWithoutAvailableCarrierIsNamedBeforeTheRowOfItsCarrier(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 've' );
		$this->wcAdapterMock->method( 'countriesGetShippingCountries' )->willReturn( self::SHIPPING_COUNTRIES );
		$this->carrierEntityRepositoryMock->method( 'getByCountryIncludingNonFeed' )
			->willReturn( [ self::createCarrierOfCountry( 've', false ) ] );
		$this->expectCarrierOptions( true );
		$this->expectZoneMethodStates(
			[ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-ve' => false ],
			self::ZONE_NOT_COVERED
		);

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame(
			[ RateUnavailabilityReason::NO_CARRIER_FOR_COUNTRY ],
			array_column( $this->storedSnapshot['packageUnavailabilities'], 'reason' )
		);
		self::assertSame(
			[ RateUnavailabilityReason::CARRIER_METHOD_DISABLED_IN_ZONE ],
			array_column( $this->storedSnapshot['carriers'][0]['unavailabilities'], 'reason' )
		);
	}

	public function testZoneCarryingPacketaMethodIsNotBlamedOnAnything(): void {
		$this->createDiagnosticsWithZone( true )->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame( [], $this->storedSnapshot['packageUnavailabilities'] );
	}
}
