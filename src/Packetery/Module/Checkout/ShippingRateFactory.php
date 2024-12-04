<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Core\Entity;
use Packetery\Module\Carrier;
use Packetery\Module\Exception\ProductNotFoundException;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\ShippingMethod;

class ShippingRateFactory {
	private $checkoutService;
	private $carrierEntityRepository;
	private $wcAdapter;
	private $cartService;
	private $carrierOptionsFactory;
	private $carDeliveryConfig;
	private $rateCalculator;
	private $optionsProvider;
	private $wpAdapter;

	public function __construct(
		CheckoutService $checkoutService,
		Carrier\EntityRepository $carrierEntityRepository,
		WcAdapter $wcAdapter,
		CartService $cartService,
		Carrier\CarrierOptionsFactory $carrierOptionsFactory,
		Carrier\CarDeliveryConfig $carDeliveryConfig,
		RateCalculator $rateCalculator,
		OptionsProvider $optionsProvider,
		WpAdapter $wpAdapter
	) {
		$this->checkoutService         = $checkoutService;
		$this->carrierEntityRepository = $carrierEntityRepository;
		$this->wcAdapter               = $wcAdapter;
		$this->cartService             = $cartService;
		$this->carrierOptionsFactory   = $carrierOptionsFactory;
		$this->carDeliveryConfig       = $carDeliveryConfig;
		$this->rateCalculator          = $rateCalculator;
		$this->optionsProvider         = $optionsProvider;
		$this->wpAdapter               = $wpAdapter;
	}

	/**
	 * Prepare shipping rates based on cart properties.
	 *
	 * @param array|null $allowedCarrierNames List of allowed carrier names.
	 *
	 * @return array
	 * @throws ProductNotFoundException Product not found.
	 */
	public function createShippingRates( ?array $allowedCarrierNames ): array {
		$customerCountry           = $this->checkoutService->getCustomerCountryOrEmpty();
		$availableCarriers         = $this->carrierEntityRepository->getByCountryIncludingNonFeed( $customerCountry );
		$cartProducts              = $this->wcAdapter->cartGetCartContents();
		$cartPrice                 = $this->cartService->getCartContentsTotalIncludingTax();
		$cartWeight                = $this->cartService->getCartWeightKg();
		$totalCartProductValue     = $this->cartService->getTotalCartProductValue();
		$disallowedShippingRateIds = $this->cartService->getDisallowedShippingRateIds();
		$isAgeVerificationRequired = $this->cartService->isAgeVerification18PlusRequired();

		$customRates = [];
		foreach ( $availableCarriers as $carrier ) {
			$optionId     = Carrier\OptionPrefixer::getOptionId( $carrier->getId() );
			$rateId       = ShippingMethod::PACKETERY_METHOD_ID . ':' . $optionId;
			$shippingRate = $this->createShippingRateOfCarrier(
				$isAgeVerificationRequired,
				$carrier,
				$allowedCarrierNames,
				$optionId,
				$disallowedShippingRateIds,
				$cartProducts,
				$cartPrice,
				$totalCartProductValue,
				$cartWeight,
				$rateId
			);

			if ( null !== $shippingRate ) {
				$customRates[ $rateId ] = $shippingRate;
			}
		}

		return $customRates;
	}

	private function createShippingRateOfCarrier(
		bool $isAgeVerificationRequired,
		Entity\Carrier $carrier,
		?array $allowedCarrierNames,
		string $optionId,
		array $disallowedShippingRateIds,
		array $cartProducts,
		float $cartPrice,
		float $totalCartProductValue,
		float $cartWeight,
		string $rateId
	): ?array {
		if ( ! $this->canCreateShippingRate(
			$isAgeVerificationRequired,
			$carrier,
			$allowedCarrierNames,
			$optionId,
			$disallowedShippingRateIds,
			$cartProducts
		) ) {
			return null;
		}

		$options     = $this->carrierOptionsFactory->createByOptionId( $optionId );
		$carrierName = $allowedCarrierNames[ $carrier->getId() ] ?? $options->getName();

		$cost = $this->rateCalculator->getRateCost( $options, $cartPrice, $totalCartProductValue, $cartWeight );

		return null !== $cost ? $this->createShippingRateAndApplyTaxes( $carrierName, $cost, $rateId ) : null;
	}

	private function canCreateShippingRate(
		bool $isAgeVerificationRequired,
		Entity\Carrier $carrier,
		?array $allowedCarrierNames,
		string $optionId,
		array $disallowedShippingRateIds,
		array $cartProducts
	): bool {
		if ( $isAgeVerificationRequired && ! $carrier->supportsAgeVerification() ) {
			return false;
		}

		if ( null !== $allowedCarrierNames && ! array_key_exists( $carrier->getId(), $allowedCarrierNames ) ) {
			return false;
		}

		$carrierOptions = $this->carrierOptionsFactory->createByOptionId( $optionId );

		return ! (
			( null === $allowedCarrierNames && ! $carrierOptions->isActive() ) ||
			( $carrier->isCarDelivery() && $this->carDeliveryConfig->isDisabled() ) ||
			in_array( $optionId, $disallowedShippingRateIds, true ) ||
			$this->cartService->isShippingRateRestrictedByProductsCategory( $optionId, $cartProducts )
		);
	}

	/**
	 * @param string $carrierName
	 * @param float  $cost
	 * @param string $rateId
	 *
	 * @return array
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
		return 0.0 === $cost && $this->optionsProvider->isFreeShippingShown();
	}
}
