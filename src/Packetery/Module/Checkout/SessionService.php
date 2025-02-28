<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Module\Framework\WcAdapter;

class SessionService {

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	public function __construct(
		WcAdapter $wcAdapter
	) {
		$this->wcAdapter = $wcAdapter;
	}

	/**
	 * Gets shipping method from session without calculation.
	 *
	 * @return string
	 */
	public function getChosenMethodFromSession(): string {
		$chosenShippingRate = null;
		if ( $this->wcAdapter->session() !== null ) {
			$chosenShippingRates = $this->wcAdapter->sessionGetArray( 'chosen_shipping_methods' );
			if ( isset( $chosenShippingRates[0] ) && is_string( $chosenShippingRates[0] ) ) {
				$chosenShippingRate = $chosenShippingRates[0];
			}
		}

		return $chosenShippingRate ?? '';
	}

	public function getChosenPaymentMethod(): ?string {
		return $this->wcAdapter->sessionGetString( 'chosen_payment_method' );
	}

	/**
	 * Updates shipping rates cost based on cart properties.
	 * To test, change the shipping price during the transition from the first to the second step of the cart.
	 */
	public function actionUpdateShippingRates(): void {
		$packages = $this->wcAdapter->shippingGetPackages();
		foreach ( $packages as $index => $package ) {
			$this->wcAdapter->sessionSet( 'shipping_for_package_' . $index, false );
		}
	}

	/**
	 * Updates shipping packages to make WooCommerce caching system work correctly.
	 * Package values are used in WooCommerce method \WC_Shipping::calculate_shipping_for_package().
	 * In order to generate package cache hash correctly by WooCommerce
	 * the package must contain all relevant information related to pricing.
	 *
	 * @param array $packages Packages.
	 *
	 * @return array
	 */
	public function filterUpdateShippingPackages( array $packages ): array {
		foreach ( $packages as $key => $package ) {
			$package['packetery_payment_method'] = $this->getChosenPaymentMethod();
			$packages[ $key ]                    = $package;
		}

		return $packages;
	}
}
