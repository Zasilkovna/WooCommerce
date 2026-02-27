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
use WC_Product;

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
	 * @param Carrier\Options $carrierOptions
	 * @param float           $cartPrice
	 * @param float           $totalCartProductValue
	 * @param float|int       $cartWeight
	 * @param bool            $isFreeShippingCouponApplied
	 */
	public function getShippingRateCost(
		Carrier\Options $carrierOptions,
		float $cartPrice,
		float $totalCartProductValue,
		$cartWeight,
		bool $isFreeShippingCouponApplied
	): ?float {
		$cost                = null;
		$carrierOptionsArray = $carrierOptions->toArray();

		if ( $carrierOptions->hasPerClassOptions() ) {
			$cost = $this->getFinalShippingClassesCost( $carrierOptionsArray, $carrierOptions, $isFreeShippingCouponApplied );
			if ( $cost === null ) {
				return null;
			}

			return (float) $this->wpAdapter->applyFilters( 'packeta_shipping_price', (float) $cost, [ 'carrier_id' => $carrierOptionsArray['id'] ] );
		}

		if ( isset( $carrierOptionsArray[ Carrier\OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] ) &&
			$carrierOptions->getPricingType() === Carrier\Options::PRICING_TYPE_BY_WEIGHT ) {
			foreach ( $carrierOptionsArray[ Carrier\OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] as $weightLimit ) {
				if ( $cartWeight <= $weightLimit['weight'] ) {
					$cost = $weightLimit['price'];

					break;
				}
			}
		}

		if ( isset( $carrierOptionsArray[ Carrier\OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ) &&
			$carrierOptions->getPricingType() === Carrier\Options::PRICING_TYPE_BY_PRODUCT_VALUE ) {
			foreach ( $carrierOptionsArray[ Carrier\OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] as $productValueLimit ) {
				if ( $totalCartProductValue <= $productValueLimit['value'] ) {
					$cost = $productValueLimit['price'];

					break;
				}
			}
		}
		$this->diagnosticsLogger->log(
			'Rate calculator',
			[
				'cost'                        => $cost,
				'options'                     => $carrierOptions,
				'cartPrice'                   => $cartPrice,
				'totalCartProductValue'       => $totalCartProductValue,
				'cartWeight'                  => $cartWeight,
				'isFreeShippingCouponApplied' => $isFreeShippingCouponApplied,
				'carrierOptions'              => $carrierOptionsArray,
			]
		);
		if ( $cost === null ) {
			return null;
		}

		$freeShippingLimit = null;
		if ( $carrierOptionsArray['free_shipping_limit'] ) {
			$freeShippingLimit = $this->currencySwitcherService->getConvertedPrice( $carrierOptionsArray['free_shipping_limit'] );
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

		$hasCouponFreeShippingActive = $carrierOptions->hasCouponFreeShippingActive();
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
			'carrier_id'                                  => $carrierOptionsArray['id'],
			'free_shipping_limit'                         => $freeShippingLimit,
			Carrier\OptionsPage::FORM_FIELD_PRICING_TYPE  => $carrierOptions->getPricingType(),
			Carrier\OptionsPage::FORM_FIELD_WEIGHT_LIMITS => $carrierOptionsArray[ Carrier\OptionsPage::FORM_FIELD_WEIGHT_LIMITS ],
			Carrier\OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS => $carrierOptionsArray[ Carrier\OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ?? [],
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
	 * @return array<string, string|float|array>
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

	/**
	 * @param WC_Product $product
	 *
	 * @return string
	 */
	public static function getProductShippingClassSlug( WC_Product $product ): string {
		$shippingClassSlug = $product->get_shipping_class();
		if ( $shippingClassSlug === '' ) {
			$shippingClassSlug = 'no_class';
		}

		return $shippingClassSlug;
	}

	/**
	 * Groups cart items by shipping class slug and aggregates weight and value.
	 *
	 * @return array<string, array{weightKg: float, value: float}>
	 */
	public function getCartItemsGroupedByShippingClass(): array {
		$cartItems   = $this->wcAdapter->cartGetCartContent();
		$classTotals = [];
		foreach ( $cartItems as $item ) {
			$product = $item['data'] ?? null;
			if ( ! $product ) {
				continue;
			}
			$shippingClassSlug = self::getProductShippingClassSlug( $product );
			$quantity          = isset( $item['quantity'] ) ? (float) $item['quantity'] : 1.0;
			$weight            = (float) $product->get_weight();
			$lineWeightKg      = $this->wcAdapter->getWeight( $weight * $quantity, 'kg' );
			$lineSubtotal      = isset( $item['line_subtotal'] ) ? (float) $item['line_subtotal'] : 0.0;

			if ( ! isset( $classTotals[ $shippingClassSlug ] ) ) {
				$classTotals[ $shippingClassSlug ] = [
					'weightKg' => 0.0,
					'value'    => 0.0,
				];
			}
			$classTotals[ $shippingClassSlug ]['weightKg'] += $lineWeightKg;
			$classTotals[ $shippingClassSlug ]['value']    += $lineSubtotal;
		}

		return $classTotals;
	}

	/**
	 * @param array<string, mixed> $carrierOptionsArray
	 * @param Carrier\Options      $carrierOptions
	 * @param bool                 $isFreeShippingCouponApplied
	 *
	 * @return float|int|null
	 */
	public function getFinalShippingClassesCost( array $carrierOptionsArray, Carrier\Options $carrierOptions, bool $isFreeShippingCouponApplied ) {
		$calculationType = $carrierOptionsArray['class_calculation_type'] ?? 'per_class';

		$classTotals = $this->getCartItemsGroupedByShippingClass();

		$totalCartValue = array_sum( array_column( $classTotals, 'value' ) );

		$perClassCosts = [];
		foreach ( $classTotals as $slug => $totals ) {
			$classConfig = $carrierOptionsArray['per_class'][ $slug ] ?? null;
			$effective   = $carrierOptionsArray;
			if ( is_array( $classConfig ) ) {
				$effective = array_merge( $effective, $classConfig );
			}

			/** @var string $pricingType */
			$pricingType = $effective[ Carrier\OptionsPage::FORM_FIELD_PRICING_TYPE ] ?? $carrierOptions->getPricingType();
			$classCost   = null;
			if ( isset( $effective[ Carrier\OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] ) &&
				$pricingType === Carrier\Options::PRICING_TYPE_BY_WEIGHT ) {
				foreach ( $effective[ Carrier\OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] as $weightLimit ) {
					if ( $totals['weightKg'] <= $weightLimit['weight'] ) {
						$classCost = $weightLimit['price'];

						break;
					}
				}
			}
			if ( isset( $effective[ Carrier\OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ) &&
				$pricingType === Carrier\Options::PRICING_TYPE_BY_PRODUCT_VALUE ) {
				foreach ( $effective[ Carrier\OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] as $productValueLimit ) {
					if ( $totals['value'] <= $productValueLimit['value'] ) {
						$classCost = $productValueLimit['price'];

						break;
					}
				}
			}

			if ( $classCost === null ) {
				$classCost = $this->computeCostFromGlobalLimits( $carrierOptionsArray, $totals, $pricingType );
			}

			$limit = null;
			if ( is_array( $classConfig ) && isset( $classConfig['free_shipping_limit'] ) && is_numeric( $classConfig['free_shipping_limit'] ) ) {
				$limit = $this->currencySwitcherService->getConvertedPrice( (float) $classConfig['free_shipping_limit'] );
			} elseif ( ! is_array( $classConfig ) && isset( $carrierOptionsArray['free_shipping_limit'] ) && is_numeric( $carrierOptionsArray['free_shipping_limit'] ) ) {
				$limit = $this->currencySwitcherService->getConvertedPrice( (float) $carrierOptionsArray['free_shipping_limit'] );
			}
			if ( $limit !== null && $totalCartValue >= $limit ) {
				$classCost = 0.0;
			}

			$perClassCosts[ $slug ] = (float) $classCost;
		}

		if ( $calculationType === 'per_order_most_expensive' ) {
			if ( $perClassCosts === [] ) {
				$cost = 0;
			} else {
				$cost = max( $perClassCosts );
			}
		} else {
			$cost = array_sum( $perClassCosts );
		}

		$hasCouponFreeShippingActive = $carrierOptions->hasCouponFreeShippingActive();
		if ( $cost !== 0 && $isFreeShippingCouponApplied === true && $hasCouponFreeShippingActive === true ) {
			$cost = 0.0;
		}

		$this->diagnosticsLogger->log(
			'Rate calculator (per-class)',
			[
				'perClassCosts' => $perClassCosts,
				'classTotals'   => $classTotals,
				'calculation'   => $calculationType,
			]
		);

		return $cost;
	}

	/**
	 * Compute cost from GLOBAL (root) limits when per-class limits do not match.
	 *
	 * @param array<string, mixed>                 $carrierOptionsArray
	 * @param array{weightKg: float, value: float} $classTotals
	 * @param string                               $pricingType One of Options::PRICING_TYPE_BY_WEIGHT or Options::PRICING_TYPE_BY_PRODUCT_VALUE
	 *
	 * @return float|null Returns price if any global limit matched; otherwise null
	 */
	private function computeCostFromGlobalLimits( array $carrierOptionsArray, array $classTotals, string $pricingType ): ?float {
		// Prefer the same pricing type that was effective for the class.
		if (
			isset( $carrierOptionsArray[ Carrier\OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] ) &&
			is_array( $carrierOptionsArray[ Carrier\OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] ) &&
			$pricingType === Carrier\Options::PRICING_TYPE_BY_WEIGHT
		) {
			foreach ( $carrierOptionsArray[ Carrier\OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] as $weightLimit ) {
				if ( ! is_array( $weightLimit ) ) {
					continue;
				}
				if ( $classTotals['weightKg'] <= $weightLimit['weight'] ) {
					return (float) $weightLimit['price'];
				}
			}
		}

		if (
			isset( $carrierOptionsArray[ Carrier\OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ) &&
			is_array( $carrierOptionsArray[ Carrier\OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ) &&
			$pricingType === Carrier\Options::PRICING_TYPE_BY_PRODUCT_VALUE
		) {
			foreach ( $carrierOptionsArray[ Carrier\OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] as $productValueLimit ) {
				if ( ! is_array( $productValueLimit ) ) {
					continue;
				}
				if ( $classTotals['value'] <= $productValueLimit['value'] ) {
					return (float) $productValueLimit['price'];
				}
			}
		}

		return null;
	}
}
