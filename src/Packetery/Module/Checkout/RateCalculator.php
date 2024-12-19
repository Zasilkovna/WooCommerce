<?php
/**
 * Class RateCalculator.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Module\Carrier;
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

	public function __construct(
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter,
		CurrencySwitcherService $currencySwitcherService
	) {
		$this->wpAdapter               = $wpAdapter;
		$this->wcAdapter               = $wcAdapter;
		$this->currencySwitcherService = $currencySwitcherService;
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
		$cost           = null;
		$carrierOptions = $options->toArray();

		if ( isset( $carrierOptions[ Carrier\OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] ) && $options->getPricingType() === Carrier\Options::PRICING_TYPE_BY_WEIGHT ) {
			foreach ( $carrierOptions[ Carrier\OptionsPage::FORM_FIELD_WEIGHT_LIMITS ] as $weightLimit ) {
				if ( $cartWeight <= $weightLimit['weight'] ) {
					$cost = $weightLimit['price'];

					break;
				}
			}
		}

		if ( isset( $carrierOptions[ Carrier\OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ) && $options->getPricingType() === Carrier\Options::PRICING_TYPE_BY_PRODUCT_VALUE ) {
			foreach ( $carrierOptions[ Carrier\OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] as $productValueLimit ) {
				if ( $totalCartProductValue <= $productValueLimit['value'] ) {
					$cost = $productValueLimit['price'];

					break;
				}
			}
		}

		if ( $cost === null ) {
			return null;
		}

		$freeShippingLimit = null;
		if ( $carrierOptions['free_shipping_limit'] ) {
			$freeShippingLimit = $this->currencySwitcherService->getConvertedPrice( $carrierOptions['free_shipping_limit'] );
			if ( $cartPrice >= $freeShippingLimit ) {
				$cost = 0;
			}
		}

		if ( $cost !== 0 && $isFreeShippingCouponApplied && $options->hasCouponFreeShippingActive() ) {
			$cost = 0;
		}

		$filterParameters = [
			'carrier_id'                                  => $carrierOptions['id'],
			'free_shipping_limit'                         => $freeShippingLimit,
			Carrier\OptionsPage::FORM_FIELD_PRICING_TYPE  => $options->getPricingType(),
			Carrier\OptionsPage::FORM_FIELD_WEIGHT_LIMITS => $carrierOptions[ Carrier\OptionsPage::FORM_FIELD_WEIGHT_LIMITS ],
			Carrier\OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS => $carrierOptions[ Carrier\OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ] ?? [],
		];

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
			foreach ( $carrierOptions['surcharge_limits'] as $weightLimit ) {
				if ( $cartPrice <= $weightLimit['order_price'] ) {
					return (float) $weightLimit['surcharge'];
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
}
