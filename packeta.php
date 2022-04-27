<?php
/**
 * Plugin implementing Packeta shipping methods.
 *
 * @package   Packeta
 *
 * @wordpress-plugin
 *
 * Plugin Name: Packeta
 * Description: This is the official plugin, that allows you to choose pickup points of Packeta and its external carriers in all of Europe, or utilize address delivery to 25 countries in the European Union, straight from the cart in your e-shop. Furthermore, you can also submit all your orders to Packeta with just one click.
 * Version: 1.3.3
 * Author: Zásilkovna s.r.o.
 * Author URI: https://www.zasilkovna.cz/
 * Text Domain: packeta
 * Domain Path: /languages
 * Requires at least: 5.3
 * Requires PHP: 7.2
 *
 * Tested up to: 5.9.2
 * WC requires at least: 4.5
 * WC tested up to: 6.3.1
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

use Packetery\Module\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( PHP_SAPI === 'cli' ) {
	return;
}

define( 'PACKETERY_MIN_PHP_VERSION', '7.2' );

/**
 * Renders PHP version notice.
 *
 * @return void
 */
function packetery_render_insufficient_php_version_notice() {
	// translators: %s: Min PHP version.
	$message = sprintf( __( 'Insufficient PHP version. At least version %s is required by Packeta plugin.', 'packeta' ), PACKETERY_MIN_PHP_VERSION );
	echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
}

/**
 * Deactivates plugin.
 *
 * @return void
 */
function packetery_deactivate_plugin() {
	if ( ! function_exists( 'deactivate_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	deactivate_plugins( array( __FILE__ ), true );
}

if ( version_compare( PHP_VERSION, PACKETERY_MIN_PHP_VERSION, '<' ) ) {
	add_action( 'admin_notices', 'packetery_render_insufficient_php_version_notice' );
	add_action( 'init', 'packetery_deactivate_plugin' );

	return;
}

$container = require __DIR__ . '/bootstrap.php';
$container->getByType( Plugin::class )->run();
