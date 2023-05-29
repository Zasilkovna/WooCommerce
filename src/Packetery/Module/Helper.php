<?php
/**
 * Class Helper
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );


namespace Packetery\Module;

/**
 * Class Helper
 *
 * @package Packetery\Module
 */
class Helper {

	/**
	 * Tells if plugin is active.
	 *
	 * @param string $pluginRelativePath Relative path of plugin bootstrap file.
	 *
	 * @return bool
	 */
	public static function isPluginActive( string $pluginRelativePath ): bool {
		if ( is_multisite() ) {
			$plugins = get_site_option( 'active_sitewide_plugins' );
			if ( isset( $plugins[ $pluginRelativePath ] ) ) {
				return true;
			}
		}

		if ( ! function_exists( 'get_mu_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$muPlugins = get_mu_plugins();
		if ( isset( $muPlugins[ $pluginRelativePath ] ) ) {
			return true;
		}

		return in_array( $pluginRelativePath, (array) get_option( 'active_plugins', [] ), true );
	}

	/**
	 * Introduced as ManageWP Worker plugin hack fix.
	 *
	 * @return void
	 */
	public static function transformGlobalCookies(): void {
		if ( empty( $_COOKIE ) ) {
			return;
		}
		foreach ( $_COOKIE as $key => $value ) {
			// @codingStandardsIgnoreStart
			if ( is_int( $value ) ) {
				$_COOKIE[ $key ] = (string) $value;
			}
			// @codingStandardsIgnoreEnd
		}
	}

	/**
	 * Renders string.
	 *
	 * @param string $string String to render.
	 *
	 * @return void
	 */
	public static function renderString( string $string ): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $string;
	}

	/**
	 * Gets payment method title for backend.
	 *
	 * @param \WC_Payment_Gateway $paymentGateway Payment gateway.
	 * @return string
	 */
	public static function getPaymentMethodTitleForBE( \WC_Payment_Gateway $paymentGateway ): string {
		$methodTitle = (string) $paymentGateway->get_method_title();
		if ( '' !== $methodTitle ) {
			return $methodTitle;
		}

		return (string) $paymentGateway->get_title();
	}
}
