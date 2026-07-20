<?php
/**
 * Class ReturnEligibility
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Returns;

/**
 * Pure, framework-agnostic engine deciding whether an order may be returned.
 *
 * Evaluation is order-level: a single disqualifying fact (excluded category, virtual product,
 * over-limit value/weight, unsupported carrier/country, expired window, not delivered) makes the
 * whole order not returnable. Partial (per-item) returns are out of scope.
 *
 * @package Packetery
 */
class ReturnEligibility {

	/** WC order status (without the "wc-" prefix) that counts as "delivered". */
	public const DELIVERED_ORDER_STATUS = 'completed';

	public function isEligible(
		ReturnableOrderInfo $order,
		ReturnSettings $settings,
		\DateTimeImmutable $now
	): bool {
		return $this->isDelivered( $order )
			&& $this->isWithinWindow( $order, $settings, $now )
			&& $this->isCarrierAndCountryAllowed( $order, $settings )
			&& $this->isValueWithinLimit( $order, $settings )
			&& $this->isWeightWithinLimit( $order, $settings )
			&& ! $this->hasExcludedCategory( $order, $settings )
			&& ! ( $settings->isExcludeVirtual() && $order->hasVirtualItem() );
	}

	private function isDelivered( ReturnableOrderInfo $order ): bool {
		return $order->getOrderStatus() === self::DELIVERED_ORDER_STATUS;
	}

	private function isWithinWindow(
		ReturnableOrderInfo $order,
		ReturnSettings $settings,
		\DateTimeImmutable $now
	): bool {
		$completedAt = $order->getCompletedAt();
		if ( $completedAt === null ) {
			// A missing delivery date must not block the return (defensive).
			return true;
		}

		$deadline = $completedAt->modify( sprintf( '+%d days', $settings->getReturnWindowDays() ) );

		return $now <= $deadline;
	}

	/**
	 * Packeta orders are gated by the per-carrier "allow returns" flag (which implies a serviced
	 * country, as it is only configurable for serviced-country carriers). Orders delivered by another
	 * carrier are gated by the serviced-country whitelist configured for returns.
	 */
	private function isCarrierAndCountryAllowed( ReturnableOrderInfo $order, ReturnSettings $settings ): bool {
		if ( $order->isPacketaOrder() ) {
			return $order->carrierAllowsReturns();
		}

		$country = $order->getDeliveryCountry();
		if ( $country === null ) {
			return false;
		}

		$country = strtolower( $country );
		if ( ! in_array( $country, ReturnSettings::SERVICED_COUNTRIES, true ) ) {
			return false;
		}

		$allowed = $settings->getAllowedCountries();
		if ( $allowed === [] ) {
			return true;
		}

		return in_array( $country, array_map( 'strtolower', $allowed ), true );
	}

	private function isValueWithinLimit( ReturnableOrderInfo $order, ReturnSettings $settings ): bool {
		$max   = $settings->getMaxOrderValue();
		$value = $order->getTotalValue();
		if ( $max === null || $value === null ) {
			return true;
		}

		return $value <= $max;
	}

	private function isWeightWithinLimit( ReturnableOrderInfo $order, ReturnSettings $settings ): bool {
		$max    = $settings->getMaxWeightKg();
		$weight = $order->getTotalWeightKg();
		if ( $max === null || $weight === null ) {
			return true;
		}

		return $weight <= $max;
	}

	private function hasExcludedCategory( ReturnableOrderInfo $order, ReturnSettings $settings ): bool {
		$excluded = $settings->getExcludedCategories();
		if ( $excluded === [] ) {
			return false;
		}

		return array_intersect( $order->getCategoryIds(), $excluded ) !== [];
	}
}
