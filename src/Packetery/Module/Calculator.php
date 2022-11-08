<?php
/**
 * Class Calculator
 *
 * @package Packetery\Module\Weight
 */

declare( strict_types=1 );


namespace Packetery\Module;

use Packetery\Module\Options;

/**
 * Class Calculator
 *
 * @package Packetery\Module\Order
 */
class Calculator {

	/**
	 * Options provider.
	 *
	 * @var Options\Provider
	 */
	private $optionsProvider;

	/**
	 * Constructor.
	 *
	 * @param Options\Provider $optionsProvider Options provider.
	 */
	public function __construct( Options\Provider $optionsProvider ) {
		$this->optionsProvider = $optionsProvider;
	}

	/**
	 * Calculates order weight ignoring user specified weight.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return float
	 */
	public function calculateOrderWeight( \WC_Order $order ): float {
		$weight = 0;
		foreach ( $order->get_items() as $item ) {
			$quantity = $item->get_quantity();
			$product  = $item->get_product();

			if ( is_object( $product ) && method_exists( $product, 'get_weight' ) ) {
				$productWeight = (float) $product->get_weight();
				$weight       += ( $productWeight * $quantity );
			}
		}

		$weightKg = \wc_get_weight( $weight, 'kg' );

		if ( $weightKg ) {
			$weightKg += $this->optionsProvider->getPackagingWeight();
		} elseif ( $this->optionsProvider->isDefaultWeightEnabled() ) {
			$weightKg = $this->optionsProvider->getDefaultWeight() + $this->optionsProvider->getPackagingWeight();
		}

		return $weightKg;
	}
}
