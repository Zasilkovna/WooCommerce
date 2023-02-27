<?php
/**
 * Class ShippingFacade.
 *
 * @package Packetery
 */

namespace Packetery\Module;

use Packetery\Core\Entity;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\Repository;
use WC_Cart;
use WC_Order;

/**
 * Class ShippingFacade.
 *
 * @package Packetery
 */
class ShippingFacade {

	public const CARRIER_PREFIX = 'packetery_carrier_';

	/**
	 * Currency switcher facade.
	 *
	 * @var CurrencySwitcherFacade
	 */
	private $currencySwitcherFacade;

	/**
	 * Carrier repository.
	 *
	 * @var Repository
	 */
	private $carrierRepository;

	/**
	 * ShippingFacade constructor.
	 *
	 * @param CurrencySwitcherFacade $currencySwitcherFacade Currency switcher facade.
	 * @param Repository             $carrierRepository      Carrier repository.
	 */
	public function __construct(
		CurrencySwitcherFacade $currencySwitcherFacade,
		Repository $carrierRepository
	) {
		$this->currencySwitcherFacade = $currencySwitcherFacade;
		$this->carrierRepository      = $carrierRepository;
	}

	/**
	 * Computes custom rate cost for carrier using cart contents.
	 *
	 * @param Carrier\Options $options Carrier options.
	 * @param float           $cartPrice Price.
	 * @param float|int       $cartWeight Weight.
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

		// WooCommerce currency-switcher.com compatibility.
		return (float) $cost;
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

	/**
	 * Gets carrier id from chosen shipping method.
	 *
	 * @param string $chosenMethod Chosen shipping method.
	 *
	 * @return string|null
	 */
	public function getCarrierId( string $chosenMethod ): ?string {
		$branchServiceId = $this->getExtendedBranchServiceId( $chosenMethod );
		if ( null === $branchServiceId ) {
			return null;
		}

		if ( $this->isZpointCarrierId( $branchServiceId ) ) {
			return Carrier\Repository::INTERNAL_PICKUP_POINTS_ID;
		}

		return $branchServiceId;
	}

	/**
	 * Checks if id is zpoint carrier id.
	 *
	 * @param string $carrierId Carrier id.
	 *
	 * @return bool
	 */
	public function isZpointCarrierId( string $carrierId ): bool {
		return ( strpos( $carrierId, Carrier\Repository::ZPOINT_CARRIER_PREFIX ) === 0 );
	}

	/**
	 * Gets feed ID or artificially created ID for internal purposes.
	 *
	 * @param string $chosenMethod Chosen method.
	 *
	 * @return string|null
	 */
	public function getExtendedBranchServiceId( string $chosenMethod ): ?string {
		if ( ! $this->isPacketeryOrder( $chosenMethod ) ) {
			return null;
		}

		return str_replace( self::CARRIER_PREFIX, '', $chosenMethod );
	}

	/**
	 * Checks if chosen shipping method is one of packetery.
	 *
	 * @param string $chosenMethod Chosen shipping method.
	 *
	 * @return bool
	 */
	public function isPacketeryOrder( string $chosenMethod ): bool {
		$chosenMethod = $this->getShortenedRateId( $chosenMethod );
		return ( strpos( $chosenMethod, self::CARRIER_PREFIX ) === 0 );
	}

	/**
	 * Gets ShippingRate's ID of extended id.
	 *
	 * @param string $chosenMethod Chosen shipping method.
	 *
	 * @return string
	 */
	public function getShortenedRateId( string $chosenMethod ): string {
		return str_replace( ShippingMethod::PACKETERY_METHOD_ID . ':', '', $chosenMethod );
	}

	/**
	 * Gets widget vendors param.
	 *
	 * @param string     $carrierId    Carrier id.
	 * @param string     $country      Country.
	 * @param array|null $vendorGroups Vendor groups.
	 *
	 * @return array|null
	 */
	public function getWidgetVendorsParam( string $carrierId, string $country, ?array $vendorGroups ): ?array {
		if ( is_numeric( $carrierId ) ) {
			return [
				[
					'carrierId' => $carrierId,
					'selected'  => true,
				],
			];
		}

		$vendorCarriers = $this->carrierRepository->getVendorCarriers();
		if ( ! empty( $vendorCarriers[ $carrierId ] ) ) {
			$vendorGroups = [ $vendorCarriers[ $carrierId ]['group'] ];
		}

		if ( empty( $vendorGroups ) ) {
			return null;
		}

		$vendorsParam = [];
		foreach ( $vendorGroups as $code ) {
			$groupSettings = [
				'selected' => true,
				'country'  => $country,
			];
			if ( Entity\Carrier::GROUP_ZPOINT !== $code ) {
				$groupSettings['group'] = $code;
			}
			$vendorsParam[] = $groupSettings;
		}

		return $vendorsParam;
	}

}
