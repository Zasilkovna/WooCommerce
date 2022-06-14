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
}
