<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Core\Entity;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\DiagnosticsLogger\DiagnosticsLogger;
use Packetery\Module\Exception\ProductNotFoundException;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Shipping\BaseShippingMethod;
use Packetery\Module\ShippingMethod;

class ShippingRateFactory {

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	/**
	 * @var CheckoutService
	 */
	private $checkoutService;

	/**
	 * @var Carrier\EntityRepository
	 */
	private $carrierEntityRepository;

	/**
	 * @var CartService
	 */
	private $cartService;

	/**
	 * @var CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/**
	 * @var CarDeliveryConfig
	 */
	private $carDeliveryConfig;

	/**
	 * @var RateCalculator
	 */
	private $rateCalculator;

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * @var DiagnosticsLogger
	 */
	private $diagnosticsLogger;

	/**
	 * @var ShippingRateDiagnostics
	 */
	private $shippingRateDiagnostics;

	public function __construct(
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter,
		CheckoutService $checkoutService,
		Carrier\EntityRepository $carrierEntityRepository,
		CartService $cartService,
		CarrierOptionsFactory $carrierOptionsFactory,
		CarDeliveryConfig $carDeliveryConfig,
		RateCalculator $rateCalculator,
		OptionsProvider $optionsProvider,
		DiagnosticsLogger $diagnosticsLogger,
		ShippingRateDiagnostics $shippingRateDiagnostics
	) {
		$this->wpAdapter               = $wpAdapter;
		$this->wcAdapter               = $wcAdapter;
		$this->checkoutService         = $checkoutService;
		$this->carrierEntityRepository = $carrierEntityRepository;
		$this->cartService             = $cartService;
		$this->carrierOptionsFactory   = $carrierOptionsFactory;
		$this->carDeliveryConfig       = $carDeliveryConfig;
		$this->rateCalculator          = $rateCalculator;
		$this->optionsProvider         = $optionsProvider;
		$this->diagnosticsLogger       = $diagnosticsLogger;
		$this->shippingRateDiagnostics = $shippingRateDiagnostics;
	}

	/**
	 * Prepare shipping rates based on cart properties.
	 *
	 * In the per-carrier mode the carrier is derived from $methodId, which is also the key WooCommerce
	 * looks $allowedCarrierNames up by - the two can only disagree if a third party swaps the shipping
	 * method classes through woocommerce_load_shipping_methods.
	 *
	 * @param array|null $allowedCarrierNames List of allowed carrier names.
	 * @param string     $methodId            Shipping method class id.
	 * @param int        $instanceId          Shipping method instance id.
	 *
	 * @return array<string, array<string, string|float|array>>
	 * @throws ProductNotFoundException Product not found.
	 */
	public function createShippingRates( ?array $allowedCarrierNames, string $methodId, int $instanceId ): array {
		$this->shippingRateDiagnostics->collectMethodRun();

		$customerCountry = $this->checkoutService->getCustomerCountry();
		$this->diagnosticsLogger->log(
			'createShippingRates parameters',
			[
				'customerCountry'     => $customerCountry,
				'allowedCarrierNames' => $allowedCarrierNames,
				'methodId'            => $methodId,
				'instanceId'          => $instanceId,
			]
		);
		if ( $customerCountry === null ) {
			$this->logPackageUnavailability(
				new RateUnavailability(
					RateUnavailabilityReason::CUSTOMER_COUNTRY_UNKNOWN,
					[ 'methodId' => $methodId ]
				)
			);

			return [];
		}

		$isLegacySingleMethod = $methodId === ShippingMethod::PACKETERY_METHOD_ID;
		if ( $isLegacySingleMethod ) {
			$availableCarriers = $this->carrierEntityRepository->getByCountryIncludingNonFeed( $customerCountry, false );
		} else {
			$availableCarriers = [];
			$carrierEntity     = $this->carrierEntityRepository->getAnyById(
				str_replace( BaseShippingMethod::PACKETA_METHOD_PREFIX, '', $methodId )
			);
			if ( $carrierEntity !== null && $carrierEntity->getCountry() === $customerCountry ) {
				$availableCarriers[] = $carrierEntity;
			}
		}

		if ( count( $availableCarriers ) === 0 ) {
			// Only the legacy method looked at every carrier of the country, so only it can claim
			// there is none. A per-carrier method speaks for one carrier, and a shop serving CZ and SK
			// from a single zone runs every foreign method on every address.
			if ( $isLegacySingleMethod ) {
				$this->logPackageUnavailability(
					new RateUnavailability(
						RateUnavailabilityReason::NO_CARRIER_FOR_COUNTRY,
						[
							'customerCountry' => $customerCountry,
							'methodId'        => $methodId,
						]
					)
				);
			}

			return [];
		}

		$customRates = [];
		foreach ( $availableCarriers as $carrier ) {
			$optionId   = Carrier\OptionPrefixer::getOptionId( $carrier->getId() );
			$rateId     = $methodId === ShippingMethod::PACKETERY_METHOD_ID
				? $methodId . ':' . $optionId
				: $methodId . ':' . $instanceId;
			$evaluation = $this->evaluateCarrier(
				$carrier,
				$allowedCarrierNames,
				$optionId,
				$rateId
			);

			$this->shippingRateDiagnostics->collectEvaluation( $evaluation );
			$this->diagnosticsLogger->log(
				'Shipping rate evaluated',
				[
					'carrierId'        => $evaluation->getCarrierId(),
					'carrierName'      => $evaluation->getCarrierName(),
					'rateId'           => $rateId,
					'optionId'         => $optionId,
					'isOffered'        => $evaluation->getRate() !== null,
					'unavailabilities' => $evaluation->getUnavailabilitiesForLog(),
				]
			);

			$rate = $evaluation->getRate();
			if ( $rate !== null ) {
				$customRates[ $rateId ] = $rate;
			}
		}

		return $customRates;
	}

	private function logPackageUnavailability( RateUnavailability $unavailability ): void {
		$this->shippingRateDiagnostics->collectPackageUnavailability( $unavailability );
		$this->diagnosticsLogger->log(
			'No shipping rate can be offered for the package',
			[
				'reason'  => $unavailability->getReason(),
				'context' => $unavailability->getContext(),
			]
		);
	}

	/**
	 * @param Entity\Carrier                  $carrier             Carrier.
	 * @param array<string, string|null>|null $allowedCarrierNames Null in the legacy single-method mode.
	 * @param string                          $optionId            Carrier option id.
	 * @param string                          $rateId              WooCommerce rate id.
	 *
	 * @throws ProductNotFoundException
	 */
	private function evaluateCarrier(
		Entity\Carrier $carrier,
		?array $allowedCarrierNames,
		string $optionId,
		string $rateId
	): ShippingRateEvaluation {
		$options     = $this->carrierOptionsFactory->createByOptionId( $optionId );
		$carrierName = $allowedCarrierNames[ $carrier->getId() ] ?? $options->getName() ?? $carrier->getName();

		// Only somebody reading the diagnostics needs the full list; a customer needs the yes or no,
		// so the reasons that have to walk the cart products are skipped once one already ruled the
		// carrier out. The offered-or-not result is the same either way.
		$collectAllReasons = $this->shippingRateDiagnostics->isActive();

		$unavailabilities = $this->getCarrierUnavailabilities( $carrier, $allowedCarrierNames, $optionId, $options );
		if ( $collectAllReasons === false && count( $unavailabilities ) > 0 ) {
			return new ShippingRateEvaluation( $carrier->getId(), (string) $carrierName, null, $unavailabilities );
		}

		$cartPrice             = $this->cartService->getCartContentsTotalIncludingTax();
		$cartWeight            = $this->cartService->getCartWeightKg();
		$totalCartProductValue = $this->cartService->getTotalCartProductValue();

		$unavailabilities = array_merge(
			$unavailabilities,
			$this->getCartUnavailabilities( $optionId, $options, $totalCartProductValue, $cartWeight )
		);

		$rate = null;
		if ( count( $unavailabilities ) === 0 ) {
			$cost = $this->rateCalculator->getRateCost( $options, $cartPrice, $totalCartProductValue, $cartWeight );
			// Null would mean getLimitsUnavailability() and the price calculation disagreed. Offering
			// no rate is wrong but harmless; a cast would hand the customer free shipping.
			if ( $cost !== null ) {
				$rate = $this->createShippingRateAndApplyTaxes( $carrierName, $cost, $rateId );
			}
		}

		return new ShippingRateEvaluation( $carrier->getId(), (string) $carrierName, $rate, $unavailabilities );
	}

	/**
	 * Reasons answerable from the carrier and its options alone.
	 *
	 * @param Entity\Carrier                  $carrier             Carrier.
	 * @param array<string, string|null>|null $allowedCarrierNames Null in the legacy single-method mode.
	 * @param string                          $optionId            Carrier option id.
	 * @param Carrier\Options                 $carrierOptions      Carrier options.
	 *
	 * @return RateUnavailability[]
	 * @throws ProductNotFoundException
	 */
	private function getCarrierUnavailabilities(
		Entity\Carrier $carrier,
		?array $allowedCarrierNames,
		string $optionId,
		Carrier\Options $carrierOptions
	): array {
		$unavailabilities = [];

		if ( $carrier->isAvailable() === false ) {
			$unavailabilities[] = new RateUnavailability(
				RateUnavailabilityReason::CARRIER_UNAVAILABLE,
				[ 'carrierId' => $carrier->getId() ]
			);
		}

		if ( $this->cartService->isAgeVerificationRequired() && $carrier->supportsAgeVerification() === false ) {
			$unavailabilities[] = new RateUnavailability( RateUnavailabilityReason::AGE_VERIFICATION_UNSUPPORTED );
		}

		if ( $allowedCarrierNames === null && $carrierOptions->isActive() === false ) {
			$unavailabilities[] = new RateUnavailability(
				RateUnavailabilityReason::CARRIER_OPTION_INACTIVE,
				[ 'optionId' => $optionId ]
			);
		}

		if ( $carrier->isCarDelivery() && $this->carDeliveryConfig->isDisabled() ) {
			$unavailabilities[] = new RateUnavailability( RateUnavailabilityReason::CAR_DELIVERY_DISABLED );
		}

		return $unavailabilities;
	}

	/**
	 * Reasons that have to walk the cart products, so they are the expensive half.
	 *
	 * @param string          $optionId              Carrier option id.
	 * @param Carrier\Options $carrierOptions        Carrier options.
	 * @param float           $totalCartProductValue Total cart product value.
	 * @param float|int       $cartWeight            Cart weight in kg.
	 *
	 * @return RateUnavailability[]
	 * @throws ProductNotFoundException
	 */
	private function getCartUnavailabilities(
		string $optionId,
		Carrier\Options $carrierOptions,
		float $totalCartProductValue,
		$cartWeight
	): array {
		$unavailabilities = [];

		$disallowingProductId = $this->cartService->findProductDisallowingRate( $optionId );
		if ( $disallowingProductId !== null ) {
			$unavailabilities[] = new RateUnavailability(
				RateUnavailabilityReason::DISALLOWED_BY_PRODUCT,
				[ 'productId' => $disallowingProductId ]
			);
		}

		$oversizedProduct = $this->cartService->findOversizedProductForCarrier( $carrierOptions );
		if ( $oversizedProduct !== null ) {
			$unavailabilities[] = new RateUnavailability( RateUnavailabilityReason::PRODUCT_OVERSIZED, $oversizedProduct );
		}

		$restrictingCategory = $this->cartService->findCategoryRestrictingRate(
			$optionId,
			$this->wcAdapter->cartGetCartContents()
		);
		if ( $restrictingCategory !== null ) {
			$unavailabilities[] = new RateUnavailability( RateUnavailabilityReason::DISALLOWED_BY_CATEGORY, $restrictingCategory );
		}

		$limitsUnavailability = $this->rateCalculator->getLimitsUnavailability( $carrierOptions, $totalCartProductValue, $cartWeight );
		if ( $limitsUnavailability !== null ) {
			$unavailabilities[] = $limitsUnavailability;
		}

		return $unavailabilities;
	}

	/**
	 * @param string $carrierName
	 * @param float  $cost
	 * @param string $rateId
	 *
	 * @return array{
	 *      label: string,
	 *      id: string,
	 *      cost: float,
	 *      taxes: array<int, float>|string,
	 *      calc_tax: string
	 *  }
	 */
	private function createShippingRateAndApplyTaxes( string $carrierName, float $cost, string $rateId ): array {
		if ( $this->isFreeShippingApplicable( $cost ) ) {
			$carrierName = $this->formatCarrierNameWithFreeShipping( $carrierName );
		}
		$taxes = null;
		if ( $cost > 0 && $this->optionsProvider->arePricesTaxInclusive() ) {
			$rates            = $this->wcAdapter->taxGetShippingTaxRates();
			$taxes            = $this->wcAdapter->taxCalcInclusiveTax( $cost, $rates );
			$taxExclusiveCost = $cost - array_sum( $taxes );

			/**
			 * Filters shipping taxes.
			 *
			 * @param array $taxes Taxes.
			 * @param float $taxExclusiveCost Tax exclusive cost.
			 * @param array $rates Rates.
			 *
			 * @since 1.6.5
			 */
			$taxes = $this->wpAdapter->applyFilters( 'woocommerce_calc_shipping_tax', $taxes, $taxExclusiveCost, $rates );
			if ( ! is_array( $taxes ) ) {
				$taxes = [];
			}

			$cost -= array_sum( $taxes );
		}

		return $this->rateCalculator->createShippingRate( $carrierName, $rateId, $cost, $taxes );
	}

	private function formatCarrierNameWithFreeShipping( string $carrierName ): string {
		return sprintf( '%s: %s', $carrierName, $this->wpAdapter->__( 'Free', 'packeta' ) );
	}

	private function isFreeShippingApplicable( float $cost ): bool {
		return $cost === 0.0 && $this->optionsProvider->isFreeShippingShown();
	}
}
