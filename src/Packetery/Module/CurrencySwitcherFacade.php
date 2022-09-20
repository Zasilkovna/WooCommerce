<?php
/**
 * Class CurrencySwitcherFacade
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );

namespace Packetery\Module;

/**
 * Class CurrencySwitcherFacade.
 */
class CurrencySwitcherFacade {

	public const CONTEXT_FEE = 'fee';
	/**
	 * List of supported plugins for options export.
	 * Use the name from the settings export, optionally extended with the publisher name
	 *
	 * @var string[]
	 */
	public static $supportedCurrencySwitchers = [
		'WOOCS - WooCommerce Currency Switcher',
		'CURCY - Multi Currency for WooCommerce',
		'Currency Switcher for WooCommerce (by WP Wham)',
	];

	/**
	 * Applies currency conversion if needed.
	 *
	 * @param float  $price Price to convert.
	 * @param string $context Context where is method called.
	 *
	 * @return float
	 */
	public function getConvertedPrice( float $price, string $context = '' ): float {

		if ( self::CONTEXT_FEE !== $context && $this->isWpWhamSwitcherEnabled() ) {
			$convertedPrice = alg_convert_price(
				[
					'price'        => $price,
					'format_price' => false,
				]
			);

			return is_numeric( $convertedPrice ) ? (float) $convertedPrice : $price;
		}

		if ( $this->isCurcyPluginEnabled() ) {
			return (float) wmc_get_price( $price );
		}

		return $this->applyFilterWoocsExchangeValue( $price );
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
			$value = (float) apply_filters( 'woocs_exchange_value', $value );
		}

		return $value;
	}

	/**
	 * Tells if CURCY (Multi Currency for Woocommerce) plugin is active. We intentionally do not check if it's enabled.
	 *
	 * @return bool
	 */
	private function isCurcyPluginEnabled(): bool {
		return is_plugin_active( 'woo-multi-currency/woo-multi-currency.php' );
	}

	/**
	 * Tells if Currency Switcher for WooCommerce (by WP Wham) is active.
	 *
	 * @return bool
	 */
	private function isWpWhamSwitcherEnabled(): bool {
		return is_plugin_active( 'currency-switcher-woocommerce/currency-switcher-woocommerce.php' );
	}

}
