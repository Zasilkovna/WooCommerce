<?php
/**
 * Class RateCalculator.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Module\Carrier;
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
	 * RateCalculator constructor.
	 *
	 * @param CurrencySwitcherFacade $currencySwitcherFacade Currency switcher facade.
	 */
	public function __construct(
		CurrencySwitcherFacade $currencySwitcherFacade
	) {
		$this->currencySwitcherFacade = $currencySwitcherFacade;
	}

	/**
	 * Computes custom rate cost for carrier using cart contents.
	 *
	 * @param Carrier\Options $options                     Carrier options.
	 * @param float           $cartPrice                   Price.
	 * @param float|int       $cartWeight                  Weight.
	 * @param bool            $isFreeShippingCouponApplied Is free shipping coupon applied?.
	 *
	 * @return ?float
	 */
	public function getShippingRateCost(
		Carrier\Options $options,
		float $cartPrice,
		$cartWeight,
		bool $isFreeShippingCouponApplied
	): ?float {
		$cost           = null;
		$carrierOptions = $options->toArray();

		if ( isset( $carrierOptions['weight_limits'] ) ) {
			foreach ( $carrierOptions['weight_limits'] as $weightLimit ) {
				if ( $cartWeight <= $weightLimit['weight'] ) {
					$cost = $weightLimit['price'];
					break;
				}
			}
		}

		if ( null === $cost ) {
			return null;
		}

		if ( $carrierOptions['free_shipping_limit'] ) {
			$freeShippingLimit = $this->currencySwitcherFacade->getConvertedPrice( $carrierOptions['free_shipping_limit'] );
			if ( $cartPrice >= $freeShippingLimit ) {
				$cost = 0;
			}
		}

		if ( 0 !== $cost && $isFreeShippingCouponApplied && $options->hasCouponFreeShippingActive() ) {
			$cost = 0;
		}

		/**
		 * Filter shipping rate cost in checkout
		 *
		 * @since 1.4.1
		 */
		return (float) apply_filters( 'packeta_shipping_price', (float) $cost );
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
			if ( $coupon->get_free_shipping() ) {
				return true;
			}
		}

		return false;
	}

}
