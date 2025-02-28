<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Core\Entity;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarDeliveryConfig;
use Packetery\Module\Carrier\CarrierOptionsFactory;
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

	public function __construct(
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter,
		CheckoutService $checkoutService,
		Carrier\EntityRepository $carrierEntityRepository,
		CartService $cartService,
		CarrierOptionsFactory $carrierOptionsFactory,
		CarDeliveryConfig $carDeliveryConfig,
		RateCalculator $rateCalculator,
		OptionsProvider $optionsProvider
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
	}

	/**
	 * Prepare shipping rates based on cart properties.
	 *
	 * @param array|null $allowedCarrierNames List of allowed carrier names.
	 * @param string     $methodId            Shipping method class id.
	 * @param int        $instanceId          Shipping method instance id.
	 *
	 * @return array<string, array<string, string|float|array>>
	 * @throws ProductNotFoundException Product not found.
	 */
	public function createShippingRates( ?array $allowedCarrierNames, string $methodId, int $instanceId ): array {
		$customerCountry = $this->checkoutService->getCustomerCountry();
		if ( $customerCountry === null ) {
			return [];
		}

		$rateId = null;
		if ( $methodId === ShippingMethod::PACKETERY_METHOD_ID ) {
			$availableCarriers = $this->carrierEntityRepository->getByCountryIncludingNonFeed( $customerCountry );
		} else {
			$availableCarriers = [];
			$carrierEntity     = $this->carrierEntityRepository->getAnyById(
				str_replace( BaseShippingMethod::PACKETA_METHOD_PREFIX, '', $methodId )
			);
			if ( $carrierEntity !== null && $carrierEntity->getCountry() === $customerCountry ) {
				$availableCarriers[] = $carrierEntity;
			}
			$rateId = $methodId . ':' . $instanceId;
		}

		$customRates = [];
		foreach ( $availableCarriers as $carrier ) {
			$optionId = Carrier\OptionPrefixer::getOptionId( $carrier->getId() );
			if ( $methodId === ShippingMethod::PACKETERY_METHOD_ID ) {
				$rateId = $methodId . ':' . $optionId;
			}
			$shippingRate = $this->createShippingRateOfCarrier(
				$carrier,
				$allowedCarrierNames,
				$optionId,
				$rateId
			);

			if ( $shippingRate !== null ) {
				$customRates[ $rateId ] = $shippingRate;
			}
		}

		return $customRates;
	}

	/**
	 * @throws ProductNotFoundException
	 */
	private function createShippingRateOfCarrier(
		Entity\Carrier $carrier,
		?array $allowedCarrierNames,
		string $optionId,
		string $rateId
	): ?array {
		if ( ! $this->canCreateShippingRate(
			$carrier,
			$allowedCarrierNames,
			$optionId
		) ) {
			return null;
		}

		$options               = $this->carrierOptionsFactory->createByOptionId( $optionId );
		$carrierName           = $allowedCarrierNames[ $carrier->getId() ] ?? $options->getName();
		$cartPrice             = $this->cartService->getCartContentsTotalIncludingTax();
		$cartWeight            = $this->cartService->getCartWeightKg();
		$totalCartProductValue = $this->cartService->getTotalCartProductValue();
		$cost                  = $this->rateCalculator->getRateCost( $options, $cartPrice, $totalCartProductValue, $cartWeight );

		return $cost !== null ? $this->createShippingRateAndApplyTaxes( $carrierName, $cost, $rateId ) : null;
	}

	/**
	 * @throws ProductNotFoundException
	 */
	private function canCreateShippingRate(
		Entity\Carrier $carrier,
		?array $allowedCarrierNames,
		string $optionId
	): bool {
		$isAgeVerificationRequired = $this->cartService->isAgeVerificationRequired();
		if ( $isAgeVerificationRequired && ! $carrier->supportsAgeVerification() ) {
			return false;
		}

		if ( $allowedCarrierNames !== null && ! array_key_exists( $carrier->getId(), $allowedCarrierNames ) ) {
			return false;
		}

		$carrierOptions            = $this->carrierOptionsFactory->createByOptionId( $optionId );
		$disallowedShippingRateIds = $this->cartService->getDisallowedShippingRateIds();
		$cartProducts              = $this->wcAdapter->cartGetCartContents();

		$isCarrierOptionInactive  = $allowedCarrierNames === null && ! $carrierOptions->isActive();
		$isCarDeliveryDisabled    = $carrier->isCarDelivery() && $this->carDeliveryConfig->isDisabled();
		$isOptionDisallowed       = in_array( $optionId, $disallowedShippingRateIds, true );
		$containsOversizedProduct = $this->cartService->cartContainsProductOversizedForCarrier( $carrierOptions );
		$isRestrictedByCategory   = $this->cartService->isShippingRateRestrictedByProductsCategory( $optionId, $cartProducts );

		return ! ( $isCarrierOptionInactive || $isCarDeliveryDisabled || $isOptionDisallowed || $containsOversizedProduct || $isRestrictedByCategory );
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
	 *      taxes: array<int, float>,
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
