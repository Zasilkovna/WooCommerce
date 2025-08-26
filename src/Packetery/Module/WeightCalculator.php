<?php
/**
 * Class WeightCalculator
 *
 * @package Packetery\Module\Weight
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Core\CoreHelper;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Options\OptionsProvider;
use WC_Order;
use WC_Order_Item_Product;

/**
 * Class WeightCalculator
 *
 * @package Packetery\Module\Order
 */
class WeightCalculator {

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	public function __construct(
		OptionsProvider $optionsProvider,
		WcAdapter $wcAdapter
	) {
		$this->optionsProvider = $optionsProvider;
		$this->wcAdapter       = $wcAdapter;
	}

	/**
	 * Calculates order weight ignoring user specified weight.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return float
	 */
	public function calculateOrderWeight( WC_Order $order ): float {
		$weight = 0.0;
		foreach ( $order->get_items() as $item ) {
			$quantity = $item->get_quantity();
			if ( $item instanceof WC_Order_Item_Product ) {
				$product = $item->get_product();

				if ( is_object( $product ) && method_exists( $product, 'get_weight' ) ) {
					$productWeight = (float) $product->get_weight();
					$weight       += ( $productWeight * $quantity );
				}
			}
		}

		$weightKg  = $this->wcAdapter->getWeight( $weight, 'kg' );
		$weightKg += $this->optionsProvider->getPackagingWeight();

		return CoreHelper::simplifyWeight( $weightKg );
	}
}
