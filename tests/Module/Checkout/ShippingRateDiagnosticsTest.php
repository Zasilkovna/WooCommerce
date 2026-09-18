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
		$this->wpAdapterMock->method( 'date' )->willReturn( '2026-09-02 12:00:00' );
		$this->wcAdapterMock->method( 'getFinalShippingRates' )
			->willReturnCallback(
				function (): array {
					return $this->finalShippingRates;
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
				'schemaVersion' => 1,
				'methodRan'     => true,
			]
		);

		$diagnostics->saveSnapshot();

		self::assertNull( $this->storedSnapshot );
	}

	public function testRecordOfAnOlderShapeIsDiscardedInsteadOfRead(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->wpAdapterMock->method( 'getTransient' )->willReturn( [ 'methodRan' => true ] );

		self::assertNull( $diagnostics->getSnapshot() );
	}

	public function testMethodThatRanWithoutACarrierForTheCountryIsNotReportedAsNeverAsked(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$diagnostics->collectMethodRun();
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );

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

	public function testCarriersOutsideEveryZoneAreReportedOnThePackageLevel(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$diagnostics->collectMethodRun();
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->expectActiveCzechCarrier( true );
		$this->expectZoneMethodStates( [ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'other-carrier' => true ] );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame(
			[ RateUnavailabilityReason::NO_ZONE_METHOD_FOR_COUNTRY ],
			array_column( $this->storedSnapshot['packageUnavailabilities'], 'reason' )
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

	public function testCountryOutsideShippingLocationsIsReported(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'de' );
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

	private static function createUnavailableCzechCarrier(): Carrier {
		return new Carrier(
			'zpoint-cz',
			'zpoint-cz',
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
	}

	private function expectActiveCzechCarrier( bool $isActive, bool $isAvailableInFeed = true ): void {
		$this->carrierEntityRepositoryMock->method( 'getByCountryIncludingNonFeed' )
			->willReturn( [ $isAvailableInFeed ? DummyFactory::createCarrierCzechPp() : self::createUnavailableCzechCarrier() ] );

		$carrierOptionsMock = $this->createMock( Options::class );
		$carrierOptionsMock->method( 'isActive' )->willReturn( $isActive );
		$carrierOptionsMock->method( 'getName' )->willReturn( 'Z-Point' );
		$this->carrierOptionsFactoryMock->method( 'createByCarrierId' )->willReturn( $carrierOptionsMock );
	}

	private function expectFinalRate( string $rateId, float $cost, float $tax, bool $displayIncludingTax ): void {
		$rateMock = $this->createMock( WC_Shipping_Rate::class );
		$rateMock->method( 'get_cost' )->willReturn( $cost );
		$rateMock->method( 'get_shipping_tax' )->willReturn( $tax );

		$this->finalShippingRates = [ $rateId => $rateMock ];
		$this->wcAdapterMock->method( 'cartDisplayPricesIncludingTax' )->willReturn( $displayIncludingTax );
	}

	/**
	 * @param array<string, bool> $states
	 */
	private function expectZoneMethodStates( array $states ): void {
		$this->wcAdapterMock->method( 'cartGetShippingPackages' )->willReturn( [ [ 'contents' => [] ] ] );
		$this->wcAdapterMock->method( 'shippingZonesGetZoneMatchingPackage' )
			->willReturn( $this->createMock( WC_Shipping_Zone::class ) );
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

	public function testCarrierInTheZoneButSwitchedOffInPacketaIsNamed(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->expectActiveCzechCarrier( false );
		$this->expectZoneMethodStates( [ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-cz' => true ] );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertCount( 1, $this->storedSnapshot['carriers'] );
		self::assertSame(
			[ RateUnavailabilityReason::CARRIER_OPTION_INACTIVE ],
			array_column( $this->storedSnapshot['carriers'][0]['unavailabilities'], 'reason' )
		);
	}

	public function testCarrierWhoseMethodIsSwitchedOffInTheZoneIsNotBlamedOnPacketaSettings(): void {
		$diagnostics = $this->createDiagnostics( true, true );
		$this->checkoutServiceMock->method( 'getCustomerCountry' )->willReturn( 'cz' );
		$this->expectActiveCzechCarrier( true );
		$this->expectZoneMethodStates( [ BaseShippingMethod::PACKETA_METHOD_PREFIX . 'zpoint-cz' => false ] );

		$diagnostics->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame(
			[ RateUnavailabilityReason::CARRIER_METHOD_DISABLED_IN_ZONE ],
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

	public function testZoneWithoutPacketaMethodIsReportedWhenMethodWasNeverAsked(): void {
		$this->createDiagnosticsWithZone( false )->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame(
			[ RateUnavailabilityReason::NO_SHIPPING_ZONE ],
			array_column( $this->storedSnapshot['packageUnavailabilities'], 'reason' )
		);
		self::assertSame( 'Czechia', $this->storedSnapshot['packageUnavailabilities'][0]['context']['zoneName'] );
	}

	public function testZoneCarryingPacketaMethodIsNotBlamedOnAnything(): void {
		$this->createDiagnosticsWithZone( true )->saveSnapshot();

		self::assertIsArray( $this->storedSnapshot );
		self::assertSame( [], $this->storedSnapshot['packageUnavailabilities'] );
	}
}
