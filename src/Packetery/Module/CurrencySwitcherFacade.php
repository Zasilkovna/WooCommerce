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

	/**
	 * List of supported plugins for options export.
	 *
	 * @var string[]
	 */
	public static $supportedCurrencySwitchers = [
		'WOOCS - WooCommerce Currency Switcher',
		'CURCY - Multi Currency for WooCommerce',
		'WooCommerce Price Based on Country (WCPBC)',
	];

	/**
	 * Applies currency conversion if needed.
	 *
	 * @param float $price Price to convert.
	 *
	 * @return float
	 */
	public function getConvertedPrice( float $price ): float {
		if ( $this->isCurcyPluginEnabled() ) {
			return (float) wmc_get_price( $price );
		}

		$wcpbcPrice = $this->applyWcpbcExchangeRate( $price );
		if ( null !== $wcpbcPrice ) {
			return $wcpbcPrice;
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
	 * Gets price converted by WCPBC plugin.
	 *
	 * @param float $price Input price.
	 *
	 * @return float|null
	 */
	private function applyWcpbcExchangeRate( float $price ) {
		if ( function_exists( 'wcpbc_the_zone' ) ) {
			$wcpbcZone = wcpbc_the_zone();
			if ( false !== $wcpbcZone ) {
				return (float) $wcpbcZone->get_exchange_rate_price( $price );
			}
		}

		return null;
	}

}
