<?php
/**
 * Plugin implementing Packeta shipping methods.
 *
 * @package   Packeta
 *
 * @wordpress-plugin
 *
 * Plugin Name: Packeta
 * Description: With the help of our official plugin, You can choose pickup points of Packeta and it's external carriers in all of Europe, or utilize address delivery for 25 countries in the European Union, straight from the cart in Your eshop. You can submit all of Your orders to Packeta with one click.
 * Version: 1.0.7
 * Author: Zásilkovna s.r.o.
 * Author URI: https://www.zasilkovna.cz/
 * Text Domain: packetery
 * Domain Path: /languages
 * Requires at least: 5.3
 * Requires PHP: 7.2
 *
 * WC requires at least: 4.5
 * WC tested up to: 5.7.1
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
use Packetery\Module\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if WooCommerce is active
 */
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	exit;
}

if ( php_sapi_name() === 'cli' ) {
	return;
}

if ( headers_sent() ) {
	trigger_error( 'Packeta plugin is unable to bootstrap because HTTP headers are already sent. This issue is probably caused by another plugin.', E_USER_WARNING );
	return;
}

$container = require __DIR__ . '/bootstrap.php';
$container->getByType( Plugin::class )->run();
