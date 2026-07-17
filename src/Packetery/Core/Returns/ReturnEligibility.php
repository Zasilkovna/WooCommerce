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
 * over-limit value/weight, unsupported country/carrier, expired window, not delivered) makes the
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
			&& $this->isCountryAllowed( $order, $settings )
			&& $this->isCarrierAllowed( $order, $settings )
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

	private function isCountryAllowed( ReturnableOrderInfo $order, ReturnSettings $settings ): bool {
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

	private function isCarrierAllowed( ReturnableOrderInfo $order, ReturnSettings $settings ): bool {
		$allowed = $settings->getAllowedCarriers();
		if ( $allowed === [] ) {
			return true;
		}

		$carrierId = $order->getCarrierId();
		if ( $carrierId === null ) {
			return false;
		}

		return in_array( $carrierId, $allowed, true );
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
