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

/**
 * Show action links on the plugin screen.
 *
 * @link https://developer.wordpress.org/reference/hooks/plugin_action_links_plugin_file/
 *
 * @param mixed $links Plugin Action links.
 *
 * @return array
 */
function packetery_plugin_action_links( $links ) {
	$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=todo' ) ) . '" aria-label="' .
		esc_attr__( 'View Packeta settings', 'packetery' ) . '">' .
		esc_html__( 'Settings', 'packetery' ) . '</a>';

	return $links;
}

/**
 * Show row meta on the plugin screen.
 *
 * @link https://developer.wordpress.org/reference/hooks/plugin_row_meta/
 *
 * @param mixed $links Plugin Row Meta.
 * @param mixed $plugin_file_name Plugin Base file.
 *
 * @return array
 */
function packetery_plugin_row_meta( $links, $plugin_file_name ) {
	if ( ! strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
		return $links;
	}

	$links[] = '<a href="' . esc_url( 'https://www.packeta.com/todo-plugin-docs/' ) . '" aria-label="' .
		esc_attr__( 'View Packeta documentation', 'packetery' ) . '">' .
		esc_html__( 'Documentation', 'packetery' ) . '</a>';

	return $links;
}

/**
 * Plugin initialization.
 */
function packetery_init() {
	// @link https://developer.wordpress.org/reference/functions/add_filter/
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'packetery_plugin_action_links' );
	add_filter( 'plugin_row_meta', 'packetery_plugin_row_meta', 10, 2 );
}

add_action( 'init', 'packetery_init' );

/**
 * Activate the plugin and create custom database table.
 */
function packetery_activate() {
	packetery_init();
	\Packetery\CarrierRepository::create();
}

register_activation_hook( __FILE__, 'packetery_activate' );

/**
 * Deactivation hook.
 */
function packetery_deactivate() {
}

register_deactivation_hook( __FILE__, 'packetery_deactivate' );

/**
 * Uninstall plugin and drop custom database table.
 */
function packetery_uninstall() {
	\Packetery\CarrierRepository::drop();
}

register_uninstall_hook( __FILE__, 'packetery_uninstall' );

require_once __DIR__ . '/vendor/autoload.php';

$configurator = new \Nette\Bootstrap\Configurator();
$configurator->setDebugMode( WP_DEBUG );
$configurator->addConfig( __DIR__ . '/config/config.neon' );
$configurator->setTempDirectory( __DIR__ . '/temp' );

$configurator->createRobotLoader()
	->addDirectory( __DIR__ . '/src' )
	->register();

$container = $configurator->createContainer();
$container->getByType( \Packetery\Plugin::class )->run();

/**
 * Function accommodating shipping method class.
 *
 * @link https://docs.woocommerce.com/document/shipping-method-api/
 */
function packetery_shipping_method_init() {
	if ( ! class_exists( 'WC_Packetery_Shipping_Method' ) ) {
		require_once __DIR__ . '/src/class-wc-packetery-shipping-method.php';
	}
}

add_action( 'woocommerce_shipping_init', 'packetery_shipping_method_init' );

add_filter( 'woocommerce_shipping_methods', array( \Packetery\Plugin::class, 'add_shipping_method' ) );
