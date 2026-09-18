<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Core\Entity\Carrier;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\OptionPrefixer;
use Packetery\Module\Carrier\Options;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionNames;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Shipping\BaseShippingMethod;
use Packetery\Module\ShippingMethod;
use Packetery\Module\Transients;
use WC_Shipping_Rate;

/**
 * Records why each Packeta rate was or was not offered, so the client can read it in the Support tab
 * instead of digging the log file out over FTP.
 */
class ShippingRateDiagnostics {

	/**
	 * Bumped whenever the stored record changes shape **or the set of reason codes changes** - a record
	 * lives an hour, so it outlives a plugin update, and the Support tab would either die on a missing
	 * key or print the bare identifier of a reason that no longer has a sentence.
	 */
	private const SCHEMA_VERSION = 1;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * @var CartService
	 */
	private $cartService;

	/**
	 * @var CheckoutService
	 */
	private $checkoutService;

	/**
	 * @var EntityRepository
	 */
	private $carrierEntityRepository;

	/**
	 * @var CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/**
	 * Collected within a single request - calculate_shipping() runs once per shipping method, so the
	 * pieces have to be gathered and written out only once.
	 *
	 * @var ShippingRateEvaluation[]
	 */
	private $evaluations = [];

	/**
	 * @var RateUnavailability[]
	 */
	private $packageUnavailabilities = [];

	/**
	 * Our calculate_shipping() ran in this request. False means WooCommerce never asked us, which
	 * is what a rate cache hit looks like from here.
	 *
	 * @var bool
	 */
	private $methodRan = false;

	/**
	 * @var bool|null
	 */
	private $isActive;

	/**
	 * Packeta shipping method id => enabled in at least one zone matching the cart packages. Read once
	 * per request, because both the package level reason and the per carrier rows need it.
	 *
	 * @var array<string, bool>|null
	 */
	private $packetaMethodStatesInZones;

	/**
	 * @var string|null
	 */
	private $firstZoneWithoutPacketaMethod;

	public function __construct(
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter,
		OptionsProvider $optionsProvider,
		CartService $cartService,
		CheckoutService $checkoutService,
		EntityRepository $carrierEntityRepository,
		CarrierOptionsFactory $carrierOptionsFactory
	) {
		$this->wpAdapter               = $wpAdapter;
		$this->wcAdapter               = $wcAdapter;
		$this->optionsProvider         = $optionsProvider;
		$this->cartService             = $cartService;
		$this->checkoutService         = $checkoutService;
		$this->carrierEntityRepository = $carrierEntityRepository;
		$this->carrierOptionsFactory   = $carrierOptionsFactory;
	}

	/**
	 * Asked twice for every carrier on the checkout path, and current_user_can() dispatches the
	 * user_has_cap filter that other plugins hook - so it is answered once per request.
	 */
	public function isActive(): bool {
		if ( $this->isActive === null ) {
			$this->isActive = (bool) $this->wpAdapter->getOption( OptionNames::PACKETERY_DIAGNOSTICS_LOGGING_ENABLED ) === true
				&& $this->wpAdapter->currentUserCan( 'manage_woocommerce' );
		}

		return $this->isActive;
	}

	public function collectMethodRun(): void {
		$this->methodRan = true;
	}

	public function collectEvaluation( ShippingRateEvaluation $evaluation ): void {
		if ( $this->isActive() === false ) {
			return;
		}

		$this->evaluations[ $evaluation->getCarrierId() ] = $evaluation;
	}

	public function collectPackageUnavailability( RateUnavailability $unavailability ): void {
		if ( $this->isActive() === false ) {
			return;
		}

		$this->packageUnavailabilities[] = $unavailability;
	}

	/**
	 * Runs on woocommerce_after_calculate_totals, which fires even when the rates came from the
	 * session cache and our method never ran - that is exactly the case the snapshot has to describe.
	 */
	public function saveSnapshot(): void {
		if ( $this->isActive() === false ) {
			return;
		}

		$customerCountry   = $this->checkoutService->getCustomerCountry();
		$carriersOfCountry = $customerCountry === null
			? []
			: $this->carrierEntityRepository->getByCountryIncludingNonFeed( $customerCountry, true );

		$packageUnavailabilities = $this->packageUnavailabilities;
		if ( $this->methodRan === false ) {
			$notAskedReason = $this->getReasonMethodWasNotAsked();
			if ( $notAskedReason !== null ) {
				$packageUnavailabilities[] = $notAskedReason;
			}
		}

		if (
			$this->methodRan === true
			&& count( $this->evaluations ) === 0
			&& count( $packageUnavailabilities ) === 0
		) {
			$noCarrierReason = $this->getReasonNoCarrierWasEvaluated( $carriersOfCountry, $customerCountry );
			if ( $noCarrierReason !== null ) {
				$packageUnavailabilities[] = $noCarrierReason;
			}
		}

		// The block checkout fires a series of Store API requests and all but the first are served from
		// the rate cache; overwriting with a record that says nothing would leave only the last one behind.
		if (
			$this->methodRan === false
			&& count( $packageUnavailabilities ) === 0
			&& $this->getSnapshot() !== null
		) {
			return;
		}

		$finalRates = count( $this->evaluations ) > 0 ? $this->wcAdapter->getFinalShippingRates() : [];

		$carriers = [];
		foreach ( $this->evaluations as $evaluation ) {
			$rate             = $evaluation->getRate();
			$unavailabilities = $evaluation->getUnavailabilitiesForLog();

			// Our method returned the rate and WooCommerce ended up without it, so something removed it
			// through woocommerce_package_rates - the checkout hides a carrier we did offer.
			$finalRate  = $rate !== null ? ( $finalRates[ $rate['id'] ] ?? null ) : null;
			$wasRemoved = $rate !== null && $finalRate === null;
			if ( $wasRemoved ) {
				$unavailabilities[] = [
					'reason'  => $this->wcAdapter->areOtherRatesHiddenByFreeShipping()
						? RateUnavailabilityReason::RATE_HIDDEN_BY_FREE_SHIPPING
						: RateUnavailabilityReason::RATE_REMOVED_AFTER_CALCULATION,
					'context' => [],
				];
			}

			$carriers[] = [
				'carrierId'        => $evaluation->getCarrierId(),
				'carrierName'      => $evaluation->getCarrierName(),
				'isOffered'        => $rate !== null && $wasRemoved === false,
				'costInCheckout'   => $this->getCostInCheckout( $finalRate ),
				'unavailabilities' => $unavailabilities,
			];
		}

		$carriers = array_merge( $carriers, $this->getCarriersMissingFromEvaluation( $carriersOfCountry ) );

		$packageUnavailabilitiesForSnapshot = [];
		foreach ( $packageUnavailabilities as $unavailability ) {
			$packageUnavailabilitiesForSnapshot[] = [
				'reason'  => $unavailability->getReason(),
				'context' => $unavailability->getContext(),
			];
		}

		$this->wpAdapter->setTransient(
			$this->getTransientName(),
			[
				'schemaVersion'           => self::SCHEMA_VERSION,
				'generatedAt'             => $this->wpAdapter->date( 'Y-m-d H:i:s' ),
				'methodRan'               => $this->methodRan,
				'customerCountry'         => $customerCountry,
				'cartWeightKg'            => $this->cartService->getCartWeightKg(),
				'cartTotal'               => $this->cartService->getCartContentsTotalIncludingTax(),
				'totalProductValue'       => $this->cartService->getTotalCartProductValue(),
				'itemCount'               => count( $this->wcAdapter->cartGetCartContents() ),
				'checkoutDetection'       => $this->optionsProvider->getCheckoutDetection(),
				'packageUnavailabilities' => $packageUnavailabilitiesForSnapshot,
				'carriers'                => $carriers,
			],
			HOUR_IN_SECONDS
		);
	}

	/**
	 * @return array{
	 *      schemaVersion: int,
	 *      generatedAt: string,
	 *      methodRan: bool,
	 *      customerCountry: string|null,
	 *      cartWeightKg: float,
	 *      cartTotal: float,
	 *      totalProductValue: float,
	 *      itemCount: int,
	 *      checkoutDetection: string,
	 *      packageUnavailabilities: array<int, array{reason: string, context: array<string, scalar|null>}>,
	 *      carriers: array<int, array{
	 *          carrierId: string,
	 *          carrierName: string,
	 *          isOffered: bool,
	 *          costInCheckout: float|null,
	 *          unavailabilities: array<int, array{reason: string, context: array<string, scalar|null>}>
	 *      }>
	 *  }|null
	 */
	public function getSnapshot(): ?array {
		$snapshot = $this->wpAdapter->getTransient( $this->getTransientName() );
		if ( is_array( $snapshot ) === false || ( $snapshot['schemaVersion'] ?? null ) !== self::SCHEMA_VERSION ) {
			return null;
		}

		/** @var array{
	 *      schemaVersion: int,
	 *      generatedAt: string,
	 *      methodRan: bool,
	 *      customerCountry: string|null,
	 *      cartWeightKg: float,
	 *      cartTotal: float,
	 *      totalProductValue: float,
	 *      itemCount: int,
	 *      checkoutDetection: string,
	 *      packageUnavailabilities: array<int, array{reason: string, context: array<string, scalar|null>}>,
	 *      carriers: array<int, array{
	 *          carrierId: string,
	 *          carrierName: string,
	 *          isOffered: bool,
	 *          costInCheckout: float|null,
	 *          unavailabilities: array<int, array{reason: string, context: array<string, scalar|null>}>
	 *      }>
	 *  } $snapshot */
		return $snapshot;
	}

	/**
	 * Saving carrier settings drops WooCommerce's rate cache, so the stored record describes rules
	 * that no longer apply. Dropping it too sends the reader back to the checkout, which is the only
	 * thing that can produce a record of the new rules.
	 */
	public function discardSnapshot(): void {
		$this->wpAdapter->deleteTransient( $this->getTransientName() );
	}

	/**
	 * Nothing was evaluated, and the reason has to be read from the zones rather than from that
	 * emptiness: a carrier switched off in Packeta settings does not register its method class, yet
	 * keeps its row in the zone, so "no method of ours serves this country" would contradict the row
	 * the table shows for that very carrier.
	 *
	 * @param Carrier[] $carriersOfCountry Carriers Packeta has for the destination country.
	 */
	private function getReasonNoCarrierWasEvaluated(
		array $carriersOfCountry,
		?string $customerCountry
	): ?RateUnavailability {
		if ( count( $carriersOfCountry ) === 0 ) {
			return new RateUnavailability(
				RateUnavailabilityReason::NO_CARRIER_FOR_COUNTRY,
				[ 'customerCountry' => $customerCountry ]
			);
		}

		$methodStates = $this->getPacketaMethodStatesInMatchingZones();
		foreach ( $carriersOfCountry as $carrier ) {
			// A row of its own, switched on or off - the carrier's row in the table says it precisely.
			if ( isset( $methodStates[ $this->getZoneMethodIdOfCarrier( $carrier->getId() ) ] ) ) {
				return null;
			}
		}

		return new RateUnavailability(
			RateUnavailabilityReason::NO_ZONE_METHOD_FOR_COUNTRY,
			[ 'customerCountry' => $customerCountry ]
		);
	}

	/**
	 * A carrier the client wants in the checkout can be missing from the evaluation entirely, and then
	 * it would be missing from the table too - the most common shape of the complaint. What the client
	 * wants is read from the shipping zones matching the address: a carrier whose method sits in none
	 * of them and is switched off in Packeta settings is one the shop does not use, and saying anything
	 * about it would only bury the carriers the client asks about.
	 *
	 * @param Carrier[] $carriersOfCountry Carriers Packeta has for the destination country.
	 *
	 * @return array<int, array{carrierId: string, carrierName: string, isOffered: bool, costInCheckout: float|null, unavailabilities: array<int, array{reason: string, context: array<string, scalar|null>}>}>
	 */
	private function getCarriersMissingFromEvaluation( array $carriersOfCountry ): array {
		$methodStates = $this->getPacketaMethodStatesInMatchingZones();

		$carriers = [];
		foreach ( $carriersOfCountry as $carrier ) {
			if ( isset( $this->evaluations[ $carrier->getId() ] ) ) {
				continue;
			}

			$carrierOptions = $this->carrierOptionsFactory->createByCarrierId( $carrier->getId() );
			$unavailability = $this->getReasonCarrierIsMissing(
				$carrier,
				$carrierOptions,
				$methodStates[ $this->getZoneMethodIdOfCarrier( $carrier->getId() ) ] ?? null
			);
			if ( $unavailability === null ) {
				continue;
			}

			$carriers[] = [
				'carrierId'        => $carrier->getId(),
				'carrierName'      => $carrierOptions->getName() ?? $carrier->getName(),
				'isOffered'        => false,
				'costInCheckout'   => null,
				'unavailabilities' => [
					[
						'reason'  => $unavailability->getReason(),
						'context' => $unavailability->getContext(),
					],
				],
			];
		}

		return $carriers;
	}

	private function getReasonCarrierIsMissing(
		Carrier $carrier,
		Options $carrierOptions,
		?bool $isMethodEnabledInZone
	): ?RateUnavailability {
		// Null stands for no zone matching the address holding the method at all.
		if ( $isMethodEnabledInZone === false ) {
			return new RateUnavailability(
				RateUnavailabilityReason::CARRIER_METHOD_DISABLED_IN_ZONE,
				[ 'carrierId' => $carrier->getId() ]
			);
		}

		if ( $isMethodEnabledInZone === true ) {
			if ( $carrierOptions->isActive() === false ) {
				return new RateUnavailability(
					RateUnavailabilityReason::CARRIER_OPTION_INACTIVE,
					[ 'optionId' => OptionPrefixer::getOptionId( $carrier->getId() ) ]
				);
			}

			// The method is in the zone and switched on, so it did run - unless WooCommerce served the
			// rates from its cache, and then there is nothing to be said about this carrier.
			return null;
		}

		if ( $carrierOptions->isActive() === false ) {
			return null;
		}

		return $carrier->isAvailable()
			? new RateUnavailability(
				RateUnavailabilityReason::CARRIER_NOT_IN_SHIPPING_ZONE,
				[ 'carrierId' => $carrier->getId() ]
			)
			: new RateUnavailability(
				RateUnavailabilityReason::CARRIER_UNAVAILABLE,
				[ 'carrierId' => $carrier->getId() ]
			);
	}

	/**
	 * Zone methods are read straight from the database, because a carrier switched off in Packeta
	 * settings never registers its class and WC_Shipping_Zone::get_shipping_methods() drops it - and
	 * that row is what tells a carrier the client does not use from one that stopped working.
	 *
	 * A cart can be split into several packages, each matching its own zone, so every package has to
	 * be looked at before blaming the zones.
	 *
	 * @return array<string, bool>
	 */
	private function getPacketaMethodStatesInMatchingZones(): array {
		if ( $this->packetaMethodStatesInZones !== null ) {
			return $this->packetaMethodStatesInZones;
		}

		$states = [];
		foreach ( $this->wcAdapter->cartGetShippingPackages() as $package ) {
			$zone                   = $this->wcAdapter->shippingZonesGetZoneMatchingPackage( $package );
			$zoneCarriesPacketaRate = false;

			foreach ( $this->wcAdapter->shippingZoneGetMethodStates( $zone ) as $methodId => $isEnabled ) {
				if ( $this->belongsToCurrentCarrierMode( $methodId ) === false ) {
					continue;
				}

				$states[ $methodId ]    = ( $states[ $methodId ] ?? false ) || $isEnabled;
				$zoneCarriesPacketaRate = $zoneCarriesPacketaRate || $isEnabled;
			}

			if ( $zoneCarriesPacketaRate === false && $this->firstZoneWithoutPacketaMethod === null ) {
				$this->firstZoneWithoutPacketaMethod = $zone->get_zone_name();
			}
		}

		$this->packetaMethodStatesInZones = $states;

		return $states;
	}

	/**
	 * Switching between the legacy method and the per-carrier ones leaves the rows of the mode no
	 * longer in use behind - WooCommerce only filters them out by the registered classes and the
	 * plugin never deletes them. Reading them would describe a shop that no longer exists.
	 */
	private function belongsToCurrentCarrierMode( string $methodId ): bool {
		if ( $this->optionsProvider->isWcCarrierConfigEnabled() ) {
			return strpos( $methodId, BaseShippingMethod::PACKETA_METHOD_PREFIX ) === 0;
		}

		return $methodId === ShippingMethod::PACKETERY_METHOD_ID;
	}

	/**
	 * In the legacy mode a single method stands for every carrier, so they all share its row.
	 */
	private function getZoneMethodIdOfCarrier( string $carrierId ): string {
		return $this->optionsProvider->isWcCarrierConfigEnabled()
			? BaseShippingMethod::PACKETA_METHOD_PREFIX . $carrierId
			: ShippingMethod::PACKETERY_METHOD_ID;
	}

	/**
	 * The amount the customer is shown, read from the rate WooCommerce ended up with rather than from
	 * the one we returned: a third party can reprice it, and the tax is worked out by WooCommerce
	 * whenever the client enters carrier prices without tax. The shop can be set up to show shipping
	 * either way, and the diagnostics has to match what the checkout says.
	 */
	private function getCostInCheckout( ?WC_Shipping_Rate $finalRate ): ?float {
		if ( $finalRate === null ) {
			return null;
		}

		$cost = (float) $finalRate->get_cost();
		if ( $this->wcAdapter->cartDisplayPricesIncludingTax() === false ) {
			return $cost;
		}

		return $cost + (float) $finalRate->get_shipping_tax();
	}

	/**
	 * WooCommerce skips a shipping method entirely - our calculate_shipping() is never called - when
	 * the destination country is not shippable or when no zone matching the address carries a Packeta
	 * method. A zone that does carry one yields no reason; the rates then came from the rate cache.
	 */
	private function getReasonMethodWasNotAsked(): ?RateUnavailability {
		$customerCountry = $this->checkoutService->getCustomerCountry();
		if ( $customerCountry === null ) {
			return new RateUnavailability( RateUnavailabilityReason::CUSTOMER_COUNTRY_UNKNOWN );
		}

		$allowedCountries = array_map( 'strtolower', array_keys( $this->wcAdapter->countriesGetShippingCountries() ) );
		if ( in_array( $customerCountry, $allowedCountries, true ) === false ) {
			return new RateUnavailability(
				RateUnavailabilityReason::SHIPPING_COUNTRY_NOT_ALLOWED,
				[ 'customerCountry' => $customerCountry ]
			);
		}

		// One zone carrying a switched on Packeta method is enough for the method to have been asked.
		if ( in_array( true, $this->getPacketaMethodStatesInMatchingZones(), true ) ) {
			return null;
		}

		if ( $this->firstZoneWithoutPacketaMethod !== null ) {
			return new RateUnavailability(
				RateUnavailabilityReason::NO_SHIPPING_ZONE,
				[ 'zoneName' => $this->firstZoneWithoutPacketaMethod ]
			);
		}

		return null;
	}

	private function getTransientName(): string {
		return Transients::RATE_DIAGNOSTICS_PREFIX . $this->wpAdapter->getCurrentUserId();
	}
}
