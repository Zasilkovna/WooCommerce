<?php
/**
 * Class RateCalculator.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Module\Carrier;
use Packetery\Module\DiagnosticsLogger\DiagnosticsLogger;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use WC_Cart;
use WC_Order;

/**
 * Class RateCalculator. Calculates cost for WooCommerce shipping rate.
 *
 * @package Packetery
 */
class RateCalculator {

	/**
	 * @var CurrencySwitcherService
	 */
	private $currencySwitcherService;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	/**
	 * @var DiagnosticsLogger
	 */
	private $diagnosticsLogger;

	public function __construct(
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter,
		CurrencySwitcherService $currencySwitcherService,
		DiagnosticsLogger $diagnosticsLogger
	) {
		$this->wpAdapter               = $wpAdapter;
		$this->wcAdapter               = $wcAdapter;
		$this->currencySwitcherService = $currencySwitcherService;
		$this->diagnosticsLogger       = $diagnosticsLogger;
	}

	/**
	 * Computes custom rate cost for carrier using cart contents.
	 *
	 * @param Carrier\Options $options    Carrier options.
	 * @param float           $cartPrice  Price.
	 * @param float           $totalCartProductValue Total cart product value.
	 * @param float|int       $cartWeight Weight.
	 *
	 * @return ?float
	 */
	public function getRateCost( Carrier\Options $options, float $cartPrice, float $totalCartProductValue, $cartWeight ): ?float {
		return $this->getShippingRateCost(
			$options,
			$cartPrice,
			$totalCartProductValue,
			$cartWeight,
			$this->isFreeShippingCouponApplied( $this->wcAdapter->cart() )
		);
	}

	/**
	 * Says why no pricing rule covers the cart, without any of the side effects of a full price
	 * computation - no free shipping limit, no coupon, no packeta_shipping_price filter. Safe to call
	 * for a carrier already ruled out on other grounds.
	 *
	 * Runs for every carrier on every checkout, so a limits option that is not an array - written by
	 * an import or an older version - must not reach array_column() and take the checkout down with a
	 * TypeError; the price computation next door only warns on the same value.
	 *
	 * @param Carrier\Options $options               Carrier options.
	 * @param float           $totalCartProductValue Total cart product value.
	 * @param float|int       $cartWeight            Weight.
	 */
	public function getLimitsUnavailability( Carrier\Options $options, float $totalCartProductValue, $cartWeight ): ?RateUnavailability {
		if ( $this->getCostFromLimits( $options, $totalCartProductValue, $cartWeight ) !== null ) {
			return null;
		}

		$carrierOptions = $options->toArray();
		$pricingType    = $options->getPricingType();

		if ( $pricingType === Carrier\Options::PRICING_TYPE_BY_WEIGHT ) {
			$weightLimits     = $carrierOptions[ Carrier\OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] ?? [];
			$configuredLimits = is_array( $weightLimits ) ? array_column( $weightLimits, 'weight' ) : [];
			if ( count( $configuredLimits ) === 0 ) {
				return new RateUnavailability(
					RateUnavailabilityReason::NO_PRICING_RULES,
					[ 'pricingType' => $pricingType ]
				);
			}

			return new RateUnavailability(
				RateUnavailabilityReason::WEIGHT_OVER_LIMIT,
				[
					'cartWeight'      => $cartWeight,
					'highestLimit'    => max( $configuredLimits ),
					'configuredRules' => count( $configuredLimits ),
				]
			);
		}

		if ( $pricingType === Carrier\Options::PRICING_TYPE_BY_PRODUCT_VALUE ) {
			$productValueLimits = $carrierOptions[ Carrier\OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ?? [];
			$configuredLimits   = is_array( $productValueLimits ) ? array_column( $productValueLimits, 'value' ) : [];
			if ( count( $configuredLimits ) === 0 ) {
				return new RateUnavailability(
					RateUnavailabilityReason::NO_PRICING_RULES,
					[ 'pricingType' => $pricingType ]
				);
			}

			return new RateUnavailability(
				RateUnavailabilityReason::PRODUCT_VALUE_OVER_LIMIT,
				[
					'totalCartProductValue' => $totalCartProductValue,
					'highestLimit'          => max( $configuredLimits ),
					'configuredRules'       => count( $configuredLimits ),
				]
			);
		}

		return new RateUnavailability(
			RateUnavailabilityReason::NO_PRICING_RULES,
			[ 'pricingType' => $pricingType ]
		);
	}

	/**
	 * @param Carrier\Options $options               Carrier options.
	 * @param float           $totalCartProductValue Total cart product value.
	 * @param float|int       $cartWeight            Weight.
	 */
	private function getCostFromLimits( Carrier\Options $options, float $totalCartProductValue, $cartWeight ): ?float {
		$carrierOptions = $options->toArray();

		if ( isset( $carrierOptions[ Carrier\OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] ) && $options->getPricingType() === Carrier\Options::PRICING_TYPE_BY_WEIGHT ) {
			foreach ( $carrierOptions[ Carrier\OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] as $weightLimit ) {
				if ( $cartWeight <= $weightLimit['weight'] ) {
					return (float) $weightLimit['price'];
				}
			}
		}

		if ( isset( $carrierOptions[ Carrier\OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ) && $options->getPricingType() === Carrier\Options::PRICING_TYPE_BY_PRODUCT_VALUE ) {
			foreach ( $carrierOptions[ Carrier\OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] as $productValueLimit ) {
				if ( $totalCartProductValue <= $productValueLimit['value'] ) {
					return (float) $productValueLimit['price'];
				}
			}
		}

		return null;
	}

	/**
	 * Computes custom rate cost for carrier using cart contents.
	 *
	 * @param Carrier\Options $options                     Carrier options.
	 * @param float           $cartPrice                   Price.
	 * @param float           $totalCartProductValue       Total cart product value.
	 * @param float|int       $cartWeight                  Weight.
	 * @param bool            $isFreeShippingCouponApplied Is free shipping coupon applied?
	 *
	 * @return ?float
	 */
	public function getShippingRateCost(
		Carrier\Options $options,
		float $cartPrice,
		float $totalCartProductValue,
		$cartWeight,
		bool $isFreeShippingCouponApplied
	): ?float {
		$carrierOptions = $options->toArray();
		$cost           = $this->getCostFromLimits( $options, $totalCartProductValue, $cartWeight );
		$this->diagnosticsLogger->log(
			'Rate calculator',
			[
				'cost'                        => $cost,
				'options'                     => $options,
				'cartPrice'                   => $cartPrice,
				'totalCartProductValue'       => $totalCartProductValue,
				'cartWeight'                  => $cartWeight,
				'isFreeShippingCouponApplied' => $isFreeShippingCouponApplied,
				'carrierOptions'              => $carrierOptions,
			]
		);
		if ( $cost === null ) {
			return null;
		}

		$freeShippingLimit = null;
		if ( ( $carrierOptions['free_shipping_limit'] ?? null ) ) {
			$freeShippingLimit = $this->currencySwitcherService->getConvertedPrice( $carrierOptions['free_shipping_limit'] );
			$this->diagnosticsLogger->log(
				'Rate calculator - free shipping limit',
				[
					'freeShippingLimit' => $freeShippingLimit,
				]
			);
			if ( $cartPrice >= $freeShippingLimit ) {
				$cost = 0;
			}
		}

		$hasCouponFreeShippingActive = $options->hasCouponFreeShippingActive();
		$this->diagnosticsLogger->log(
			'Rate calculator - coupon free shipping',
			[
				'hasCouponFreeShippingActive' => $hasCouponFreeShippingActive,
			]
		);
		if ( $cost !== 0 && $isFreeShippingCouponApplied === true && $hasCouponFreeShippingActive === true ) {
			$cost = 0;
		}

		$filterParameters = [
			'carrier_id'                                  => $carrierOptions['id'],
			'free_shipping_limit'                         => $freeShippingLimit,
			Carrier\OptionsPage::FORM_FIELD_PRICING_TYPE  => $options->getPricingType(),
			Carrier\OptionsPage::FORM_FIELD_WEIGHT_LIMITS => $carrierOptions[ Carrier\OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] ?? [],
			Carrier\OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS => $carrierOptions[ Carrier\OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ?? [],
		];

		$this->diagnosticsLogger->log(
			'Rate calculator - cost and filter parameters',
			[
				'filterParameters' => $filterParameters,
				'cost'             => $cost,
			]
		);

		/**
		 * Filter shipping rate cost in checkout
		 *
		 * @since 1.4.1
		 */
		return (float) $this->wpAdapter->applyFilters( 'packeta_shipping_price', (float) $cost, $filterParameters );
	}

	/**
	 * Gets applicable COD surcharge.
	 *
	 * @param array $carrierOptions Carrier options.
	 * @param float $cartPrice      Cart price.
	 *
	 * @return float
	 */
	public function getCODSurcharge( array $carrierOptions, float $cartPrice ): float {
		if ( isset( $carrierOptions['surcharge_limits'] ) ) {
			foreach ( $carrierOptions['surcharge_limits'] as $surchargeLimit ) {
				if ( $cartPrice <= $surchargeLimit['order_price'] ) {
					return (float) $surchargeLimit['surcharge'];
				}
			}
		}

		if ( isset( $carrierOptions['default_COD_surcharge'] ) && is_numeric( $carrierOptions['default_COD_surcharge'] ) ) {
			return (float) $carrierOptions['default_COD_surcharge'];
		}

		return 0.0;
	}

	/**
	 * @param string       $name
	 * @param string       $optionId
	 * @param float        $taxExclusiveCost
	 * @param float[]|null $taxes Taxes. It is going to be calculated if null.
	 *
	 * @return array{label: string, id: string, cost: float, taxes: array<int, float>|string, calc_tax: string}
	 */
	public function createShippingRate( string $name, string $optionId, float $taxExclusiveCost, ?array $taxes ): array {
		return [
			'label'    => $name,
			'id'       => $optionId,
			'cost'     => $taxExclusiveCost,
			'taxes'    => $taxes ?? '',
			'calc_tax' => 'per_order',
		];
	}

	/**
	 * Tells if free shipping coupon is applied.
	 *
	 * @param WC_Cart|WC_Order|null $cartOrOrder Cart or order.
	 *
	 * @return bool
	 */
	public function isFreeShippingCouponApplied( $cartOrOrder ): bool {
		if ( $cartOrOrder === null ) {
			return false;
		}

		$coupons = $cartOrOrder->get_coupons();
		foreach ( $coupons as $coupon ) {
			if ( method_exists( $coupon, 'get_free_shipping' ) && $coupon->get_free_shipping() ) {
				return true;
			}
		}

		return false;
	}
}
