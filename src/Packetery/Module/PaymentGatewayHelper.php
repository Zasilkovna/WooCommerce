<?php
/**
 * Class PaymentHelper
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

/**
 * Class PaymentHelper
 */
class PaymentGatewayHelper {

	/**
	 * Gets available payment gateway choices.
	 *
	 * @return array<string, string>
	 */
	public static function getAvailablePaymentGatewayChoices(): array {
		$items = [];

		foreach ( self::getAvailablePaymentGateways() as $paymentGateway ) {
			$items[ $paymentGateway->id ] = $paymentGateway->get_method_title();
		}

		return $items;
	}

	/**
	 * Get available gateways.
	 *
	 * @return \WC_Payment_Gateway[]
	 */
	public static function getAvailablePaymentGateways(): array {
		$availableGateways = [];

		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			if ( 'yes' === $gateway->enabled ) {
				$availableGateways[ $gateway->id ] = $gateway;
			}
		}

		return array_filter(
			$availableGateways,
			static function ( $gateway ): bool {
				return $gateway instanceof \WC_Payment_Gateway;
			}
		);
	}
}
