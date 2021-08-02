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
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_list_columns' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'fill_custom_order_list_columns' ) );
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

	/**
	 * Fills custom order list columns.
	 *
	 * @param string $column Current order column name.
	 */
	public function fill_custom_order_list_columns( $column ): void {
		global $post;
		$order = wc_get_order( $post->ID );

		switch ( $column ) {
			case 'packetery_destination':
				$packetery_point_name = $order->get_meta( 'packetery_point_name' );
				$packetery_point_id   = $order->get_meta( 'packetery_point_id' );

				$country = $order->get_shipping_country();
				if ( in_array( $country, array( 'CZ', 'SK', 'HU', 'RO' ), true ) ) {
					echo esc_html( "$packetery_point_name ($packetery_point_id)" );
				} else {
					echo esc_html( $packetery_point_name );
				}
				break;
			case 'packetery_packet_id':
				$packet_id = $order->get_meta( $column );
				echo '<a href="https://tracking.packeta.com/en/?id=' . esc_html( $packet_id ) . '" target="_blank">' . esc_html( $packet_id ) . '</a>';
				break;
		}
	}

	/**
	 * Add order list columns.
	 *
	 * @param string[] $columns Order list columns.
	 * @return string[] All columns.
	 */
	public function add_order_list_columns( array $columns ): array {

		$new_columns = array();

		foreach ( $columns as $column_name => $column_info ) {

			$new_columns[ $column_name ] = $column_info;

			if ( 'order_total' === $column_name ) {
				$new_columns['packetery_packet_id']   = __( 'Packet ID', 'packetery' );
				$new_columns['packetery_destination'] = __( 'Destination', 'packetery' );
			}
		}

		return $new_columns;
	}
}
