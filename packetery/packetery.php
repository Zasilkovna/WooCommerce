<?php

/**
 * @package   Packeta
 * @author    Packeta
 * @copyright 2021 Packeta
 * @license   GPL-3.0
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
 *
 * WC requires at least: 5.0
 * WC tested up to: 5.5.0
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if WooCommerce is active
 **/
if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	exit;
}

/**
 * Show action links on the plugin screen.
 * @link https://developer.wordpress.org/reference/hooks/plugin_action_links_plugin_file/
 *
 * @param mixed $links Plugin Action links.
 *
 * @return array
 */
function packetery_plugin_action_links( $links ) {
	$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=todo' ) ). '" aria-label="' .
		esc_attr__( 'View Packeta settings', plugin_basename(__FILE__) ) . '">' .
		esc_html__( 'Settings', plugin_basename(__FILE__) ) . '</a>';

	return $links;
}

/**
 * Show row meta on the plugin screen.
 * @link https://developer.wordpress.org/reference/hooks/plugin_row_meta/
 *
 * @param mixed $links Plugin Row Meta.
 * @param mixed $plugin_file_name  Plugin Base file.
 *
 * @return array
 */
function packetery_plugin_row_meta( $links, $plugin_file_name ) {
	if ( ! strpos( $plugin_file_name, basename(__FILE__) ) ) {
		return $links;
	}

	$links[] = '<a href="' . esc_url( 'https://www.packeta.com/todo-plugin-docs/' ) . '" aria-label="' .
		esc_attr__( 'View Packeta documentation', plugin_basename(__FILE__) ) . '">' .
		esc_html__( 'Documentation', plugin_basename(__FILE__) ) . '</a>';

	return $links;
}

function packetery_init() {
	// @link https://developer.wordpress.org/reference/functions/add_filter/
	add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'packetery_plugin_action_links' );
	add_filter( 'plugin_row_meta', 'packetery_plugin_row_meta', 10, 2 );

	// nepomohlo
	load_plugin_textdomain();

	// todo for example register custom post type
	//register_post_type( 'book', ['public' => true ] );
}
add_action( 'init', 'packetery_init' );

/**
 * Activate the plugin.
 */
function packetery_activate() {
	packetery_init();
	// todo ? Clear the permalinks
	//flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'packetery_activate' );

/**
 * Deactivation hook.
 */
function packetery_deactivate() {
	// todo for example Unregister the post type, so the rules are no longer in memory.
	//unregister_post_type( 'book' );
	// todo ? Clear the permalinks to remove our post type's rules from the database.
	//flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'packetery_deactivate' );

/**
 * Uninstall hook.
 */
function packetery_uninstall() {
	// todo delete options
	//delete_option($option_name);

	// todo drop a custom database tables
	//global $wpdb;
	//$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}mytable");
}
register_uninstall_hook(__FILE__, 'packetery_uninstall');
