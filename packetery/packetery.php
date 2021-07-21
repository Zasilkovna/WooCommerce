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
 * Text Domain: packetery
 * Domain Path: /languages
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
		esc_attr__( 'View Packeta settings', 'packetery' ) . '">' .
		esc_html__( 'Settings', 'packetery' ) . '</a>';

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
		esc_attr__( 'View Packeta documentation', 'packetery' ) . '">' .
		esc_html__( 'Documentation', 'packetery' ) . '</a>';

	return $links;
}

function packetery_init() {
	// @link https://developer.wordpress.org/reference/functions/add_filter/
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'packetery_plugin_action_links' );
	add_filter( 'plugin_row_meta', 'packetery_plugin_row_meta', 10, 2 );

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

require_once __DIR__ . '/vendor/autoload.php';

$configurator = new \Nette\Bootstrap\Configurator();
$configurator->setDebugMode(WP_DEBUG);
$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->setTempDirectory(__DIR__ . '/temp');

$configurator->createRobotLoader()
    ->addDirectory(__DIR__ . '/src')
    ->register();

$container = $configurator->createContainer();
$container->getByType(\Packetery\Plugin::class)->run();

/**
 * @link https://docs.woocommerce.com/document/shipping-method-api/
 */
function packetery_shipping_method_init() {
	// Your class will go here
	if ( ! class_exists( 'WC_Packetery_Shipping_Method' ) ) {

		class WC_Packetery_Shipping_Method extends WC_Shipping_Method {
			/**
			 * Constructor for Packeta shipping class
			 *
			 * @access public
			 *
			 * @param int $instance_id
			 */
			public function __construct( int $instance_id = 0 ) {
				parent::__construct();
				$this->id                 = 'packetery_shipping_method';
				$this->instance_id        = absint( $instance_id );
				$this->method_title       = __( 'Packeta Shipping Method', 'packetery' );
				$this->title              = __( 'Packeta Shipping Method' );
				$this->method_description = __( 'Description of Packeta shipping method' ); //
				$this->enabled            = 'yes'; // This can be added as an setting but for this example its forced enabled
				$this->supports           = array(
					'shipping-zones',
				);
				$this->init();
			}

			/**
			 * Init settings
			 *
			 * @access public
			 * @return void
			 */
			public function init(): void {
				// todo Load the settings API
				//$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
				//$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

				// Save settings in admin if you have any defined
				add_action( 'woocommerce_update_options_shipping_' . $this->id, array(
					$this,
					'process_admin_options'
				) );
			}

			/**
			 * calculate_shipping function.
			 *
			 * @access public
			 *
			 * @param array $package
			 *
			 * @return void
			 */
			public function calculate_shipping( array $package = array() ): void {
				// TODO: This is where you'll add your rates
				$defaultRate = array(
					'label'    => 'Packeta shipping rate',
					'cost'     => 0,
					'taxes'    => '',
					'calc_tax' => 'per_order'
				);

				// Register the rate
				$this->add_rate( $defaultRate );
			}
		}
	}
}

add_action( 'woocommerce_shipping_init', 'packetery_shipping_method_init' );

add_filter( 'woocommerce_shipping_methods', array( '\Packetery\Plugin', 'add_shipping_method' ) );
