<?php
/**
 * Main Packeta plugin class.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery;

use Packetery\Carrier\Downloader;
use Packetery\Carrier\Repository;
use Packetery\Order;

/**
 * Class Plugin
 *
 * @package Packetery
 */
class Plugin {

	public const VERSION = '1.0.0';
	public const DOMAIN  = 'packetery';

	/**
	 * Options page.
	 *
	 * @var \Packetery\Options\Page Options page,
	 */
	private $options_page;

	/**
	 * Latte engine.
	 *
	 * @var \Latte\Engine
	 */
	private $latte_engine;

	/**
	 * Carrier downloader object.
	 *
	 * @var Downloader
	 */
	private $carrier_downloader;

	/**
	 * Carrier repository.
	 *
	 * @var Repository
	 */
	private $carrier_repository;

	/**
	 * Path to main plugin file.
	 *
	 * @var string Path to main plugin file.
	 */
	private $main_file_path;

	/**
	 * Admin edit order page Packeta metabox.
	 *
	 * @var Order\Metabox
	 */
	private $order_metabox;

	/**
	 * Message manager.
	 *
	 * @var Message_Manager
	 */
	private $message_manager;

	/**
	 * Generic Helper.
	 *
	 * @var Helper
	 */
	private $helper;

	/**
	 * Checkout object.
	 *
	 * @var Checkout
	 */
	private $checkout;

	/**
	 * Plugin constructor.
	 *
	 * @param Order\Metabox   $order_metabox Order metabox.
	 * @param Message_Manager $message_manager Message manager.
	 * @param Helper          $helper Helper.
	 * @param Options\Page    $options_page Options page.
	 * @param \Latte\Engine   $latte_engine Latte engine.
	 * @param Repository      $carrier_repository Carrier repository.
	 * @param Downloader      $carrier_downloader Carrier downloader object.
	 * @param Checkout        $checkout Checkout class.
	 */
	public function __construct( Order\Metabox $order_metabox, Message_Manager $message_manager, Helper $helper, Options\Page $options_page, Repository $carrier_repository, Downloader $carrier_downloader, Checkout $checkout, \Latte\Engine $latte_engine ) {
		$this->options_page       = $options_page;
		$this->latte_engine   = $latte_engine;
		$this->carrier_repository = $carrier_repository;
		$this->carrier_downloader = $carrier_downloader;
		$this->main_file_path     = PACKETERY_PLUGIN_DIR . '/packetery.php';
		$this->order_metabox      = $order_metabox;
		$this->message_manager    = $message_manager;
		$this->helper             = $helper;
		$this->options_page       = $options_page;
		$this->checkout           = $checkout;
	}

	/**
	 * Method to register hooks
	 */
	public function run(): void {
		$this->load_textdomains();

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		\Nette\Forms\Form::initialize();

		add_action(
			'admin_notices',
			function () {
				$this->message_manager->render();
			}
		);
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

		add_action(
			'woocommerce_email_footer',
			function () {
				$this->latte_engine->render(
					PACKETERY_PLUGIN_DIR . '/template/email/footer.latte',
					array(
						'order' => \Packetery\Order\Wrapper::from_globals(),
					)
				);
			}
		);

		add_filter( 'woocommerce_shipping_methods', array( $this, 'add_shipping_method' ) );
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_list_columns' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'fill_custom_order_list_columns' ) );
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		$this->order_metabox->register();

		$this->checkout->register_hooks();

		add_action(
			'packetery_cron_carriers_hook',
			function () {
				$this->carrier_downloader->run();
			}
		);
		if ( ! wp_next_scheduled( 'packetery_cron_carriers_hook' ) ) {
			wp_schedule_event( time(), 'daily', 'packetery_cron_carriers_hook' );
		}
	}

	/**
	 * Enqueues javascript files for administration.
	 */
	public function admin_enqueue_scripts(): void {
		wp_enqueue_script( 'live-form-validation', plugin_dir_url( $this->main_file_path ) . 'public/libs/live-form-validation/live-form-validation.js', array(), '2.0-dev', false );
	}

	/**
	 *  Add links to left admin menu.
	 */
	public function add_menu_pages(): void {
		$this->options_page->register();
	}

	/**
	 * Loads plugin translations files.
	 */
	public function load_textdomains(): void {
		$mo_files = \Nette\Utils\Finder::findFiles( '*.mo' )->from( PACKETERY_PLUGIN_DIR . '/languages' );
		foreach ( $mo_files as $mo_file ) {
			load_textdomain( 'packetery', $mo_file );
		}
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
		$this->carrier_repository->create_table();
	}

	/**
	 * Uninstalls plugin and drops custom database table.
	 * Only a static class method or function can be used in an uninstall hook.
	 */
	public static function uninstall(): void {
		$container  = require PACKETERY_PLUGIN_DIR . '/bootstrap.php';
		$repository = $container->getByType( Repository::class );
		$repository->drop();
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
				if ( $packetery_point_name && $packetery_point_id && in_array( $country, array( 'CZ', 'SK', 'HU', 'RO' ), true ) ) {
					echo esc_html( "$packetery_point_name ($packetery_point_id)" );
				} elseif ( $packetery_point_name ) {
					echo esc_html( $packetery_point_name );
				}
				break;
			case 'packetery_packet_id':
				$packet_id = (string) $order->get_meta( $column );
				if ( $packet_id ) {
					echo '<a href="' . esc_attr( $this->helper->get_tracking_url( $packet_id ) ) . '" target="_blank">' . esc_html( $packet_id ) . '</a>';
				}
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
				$new_columns['packetery_packet_id']   = __( 'Barcode', 'packetery' );
				$new_columns['packetery_destination'] = __( 'Pick up point or carrier', 'packetery' );
			}
		}

		return $new_columns;
	}
}
