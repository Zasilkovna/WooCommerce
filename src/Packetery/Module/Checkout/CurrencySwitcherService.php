<?php
/**
 * Class CurrencySwitcherService
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\ModuleHelper;

/**
 * Class CurrencySwitcherService.
 */
class CurrencySwitcherService {

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var ModuleHelper
	 */
	private $moduleHelper;

	public function __construct(
		WpAdapter $wpAdapter,
		ModuleHelper $moduleHelper
	) {
		$this->wpAdapter    = $wpAdapter;
		$this->moduleHelper = $moduleHelper;
	}

	/**
	 * List of supported plugins for options export.
	 *
	 * @var string[]
	 */
	public static $supportedCurrencySwitchers = [
		'WOOCS - WooCommerce Currency Switcher',
		'WooPayments: Integrated WooCommerce Payments',
	];

	/**
	 * Applies currency conversion if needed.
	 *
	 * @param float $price Price to convert.
	 *
	 * @return float
	 */
	public function getConvertedPrice( float $price ): float {
		if ( $this->moduleHelper->isPluginActive( 'woocommerce-currency-switcher/index.php' ) ) {
			return $this->applyFilterWoocsExchangeValue( $price );
		}
		elseif ( defined( 'WCPAY_PLUGIN_FILE' ) && class_exists( '\WC_Payments_Features' ) && \WC_Payments_Features::is_customer_multi_currency_enabled() ) {
			return $this->applyFilterWcpayExchangeValue( $price );
		}

		/**
		 * Applies packetery_price filters.
		 *
		 * @since 1.4
		 */
		return (float) $this->wpAdapter->applyFilters( 'packetery_price', $price );
	}

	/**
	 * WooCommerce currency-switcher.com compatibility. Does no harm in case WOOCS is not active.
	 *
	 * @param float $value Value of the surcharge or transport price.
	 *
	 * @return float
	 */
	private function applyFilterWoocsExchangeValue( float $value ): float {
		if ( $value > 0 ) {
			/**
			 * Applies woocs_exchange_value filters.
			 *
			 * @since 1.2.7
			 */
			$value = (float) $this->wpAdapter->applyFilters( 'woocs_exchange_value', $value );
		}

		return $value;
	}

	/**
	 * WooCommerce Payments compatibility. Does no harm in case WCPay is not active.
	 *
	 * @param float $value Value of the surcharge or transport price.
	 *
	 * @return float
	 */
	private function applyFilterWcpayExchangeValue( float $value ): float {
		if ( $value > 0 && function_exists( 'WC_Payments_Multi_Currency' ) ) {
			$multiCurrency = \WC_Payments_Multi_Currency();
			$value         = (float) $multiCurrency->get_price( $value, 'product' );
		}

		return $value;
	}
}
