<?php
/**
 * Class CurrencySwitcherFacade
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\ModuleHelper;

/**
 * Class CurrencySwitcherFacade.
 */
class CurrencySwitcherFacade {

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	public function __construct(
		WpAdapter $wpAdapter
	) {
		$this->wpAdapter = $wpAdapter;
	}

	/**
	 * List of supported plugins for options export.
	 *
	 * @var string[]
	 */
	public static $supportedCurrencySwitchers = [
		'WOOCS - WooCommerce Currency Switcher',
	];

	/**
	 * Applies currency conversion if needed.
	 *
	 * @param float $price Price to convert.
	 *
	 * @return float
	 */
	public function getConvertedPrice( float $price ): float {
		if ( ModuleHelper::isPluginActive( 'woocommerce-currency-switcher/index.php' ) ) {
			return $this->applyFilterWoocsExchangeValue( $price );
		}

		/**
		 * Applies packetery_price filters.
		 *
		 * @since 1.4
		 */
		$price = (float) $this->wpAdapter->applyFilters( 'packetery_price', $price );

		return $price;
	}

	/**
	 * WooCommerce currency-switcher.com compatibility. Does no harm in case WOOCS is not active.
	 *
	 * @param float $value Value of the surcharge or transport price.
	 *
	 * @return float
	 */
	private function applyFilterWoocsExchangeValue( float $value ): float {
		if ( 0 < $value ) {
			/**
			 * Applies woocs_exchange_value filters.
			 *
			 * @since 1.2.7
			 */
			$value = (float) $this->wpAdapter->applyFilters( 'woocs_exchange_value', $value );
		}

		return $value;
	}
}
