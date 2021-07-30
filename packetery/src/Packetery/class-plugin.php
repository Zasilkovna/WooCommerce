<?php
/**
 * Main Packeta plugin class.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery;

/**
 * Class Plugin
 *
 * @package Packetery
 */
class Plugin {

	public const DOMAIN = 'packetery';

	/**
	 * Path to main plugin file.
	 *
	 * @var string Path to main plugin file.
	 */
	private $main_file_path;

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		$this->main_file_path = PACKETERY_PLUGIN_DIR . '/packetery.php';
	}

	/**
	 * Method to register hooks
	 */
	public function run(): void {
		add_action( 'init', array( $this, 'init' ) );

		register_activation_hook( $this->main_file_path, array( $this, 'activate' ) );

		// TODO: deactivation_hook.
		register_deactivation_hook(
			$this->main_file_path,
			static function () {
			}
		);

		register_uninstall_hook( $this->main_file_path, array( __CLASS__, 'uninstall' ) );

		// @link https://docs.woocommerce.com/document/shipping-method-api/
		add_action(
			'woocommerce_shipping_init',
			function () {
				if ( ! class_exists( 'WC_Packetery_Shipping_Method' ) ) {
					require_once PACKETERY_PLUGIN_DIR . '/src/class-wc-packetery-shipping-method.php';
				}
			}
		);

		add_filter( 'woocommerce_shipping_methods', array( $this, 'add_shipping_method' ) );
	}

	/**
	 * Inits plugin.
	 */
	public function init(): void {
		add_filter(
			'plugin_action_links_' . plugin_basename( $this->main_file_path ),
			array(
				$this,
				'plugin_action_links',
			)
		);
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Activates plugin.
	 */
	public function activate(): void {
		$this->init();
		CarrierRepository::create();
	}

	/**
	 * Uninstalls plugin and drops custom database table.
	 * Only a static class method or function can be used in an uninstall hook.
	 */
	public static function uninstall(): void {
		CarrierRepository::drop();
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/plugin_action_links_plugin_file/
	 *
	 * @param array $links Plugin Action links.
	 *
	 * @return array
	 */
	public function plugin_action_links( array $links ): array {
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
	 * @param array  $links Plugin Row Meta.
	 * @param string $plugin_file_name Plugin Base file.
	 *
	 * @return array
	 */
	public function plugin_row_meta( array $links, string $plugin_file_name ): array {
		if ( ! strpos( $plugin_file_name, basename( $this->main_file_path ) ) ) {
			return $links;
		}

		$links[] = '<a href="' . esc_url( 'https://www.packeta.com/todo-plugin-docs/' ) . '" aria-label="' .
				esc_attr__( 'View Packeta documentation', 'packetery' ) . '">' .
				esc_html__( 'Documentation', 'packetery' ) . '</a>';

		return $links;
	}

	/**
	 * Adds Packeta method to available shipping methods.
	 *
	 * @param array $methods Previous state.
	 *
	 * @return array
	 */
	public function add_shipping_method( array $methods ): array {
		$methods['packetery_shipping_method'] = \WC_Packetery_Shipping_Method::class;

		return $methods;
	}

}
