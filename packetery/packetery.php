<?php
/**
 * Plugin implementing Packeta shipping methods.
 *
 * @package   Packeta
 *
 * @wordpress-plugin
 *
 * Plugin Name: Packeta
 * Plugin URI: https://www.packeta.com/todo-plugin-page/
 * Description: Todo description.
 * Version: 1.0.0
 * Author: Packeta
 * Author URI: https://www.packeta.com/
 * Developer: Packeta
 * Developer URI: https://www.packeta.com/
 * Text Domain: packetery
 * Domain Path: /languages
 *
 * WC requires at least: 5.0
 * WC tested up to: 5.5.0
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if WooCommerce is active
 */
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	exit;
}
$container = require __DIR__ . '/bootstrap.php';
$container->getByType( \Packetery\Plugin::class )->run();
