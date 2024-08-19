<?php
/**
 * Class RateCalculator.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

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
	 * Currency switcher facade.
	 *
	 * @var CurrencySwitcherFacade
	 */
	private $currencySwitcherFacade;

	/**
	 * Framework adapter.
	 *
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * RateCalculator constructor.
	 *
	 * @param WpAdapter              $wpAdapter       Framework adapter.
	 * @param CurrencySwitcherFacade $currencySwitcherFacade Currency switcher facade.
	 */
	public function __construct(
		WpAdapter $wpAdapter,
		CurrencySwitcherFacade $currencySwitcherFacade
	) {
		$this->wpAdapter              = $wpAdapter;
		$this->currencySwitcherFacade = $currencySwitcherFacade;
	}

	/**
	 * Computes custom rate cost for carrier using cart contents.
	 *
	 * @param Carrier\Options $options                     Carrier options.
	 * @param float           $cartPrice                   Price.
	 * @param float           $totalCartProductValue       Total cart product value.
	 * @param float|int       $cartWeight                  Weight.
	 * @param bool            $isFreeShippingCouponApplied Is free shipping coupon applied?.
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

		if ( null === $cost ) {
			return null;
		}

		$freeShippingLimit = null;
		if ( $carrierOptions['free_shipping_limit'] ) {
			$freeShippingLimit = $this->currencySwitcherFacade->getConvertedPrice( $carrierOptions['free_shipping_limit'] );
			if ( $cartPrice >= $freeShippingLimit ) {
				$cost = 0;
			}
		}

		if ( 0 !== $cost && $isFreeShippingCouponApplied && $options->hasCouponFreeShippingActive() ) {
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
	 * Tells if free shipping coupon is applied.
	 *
	 * @param WC_Cart|WC_Order $cartOrOrder Cart or order.
	 *
	 * @return bool
	 */
	public function isFreeShippingCouponApplied( $cartOrOrder ): bool {
		$coupons = $cartOrOrder->get_coupons();
		foreach ( $coupons as $coupon ) {
			if ( method_exists( $coupon, 'get_free_shipping' ) && $coupon->get_free_shipping() ) {
				return true;
			}
		}

		return false;
	}

}
