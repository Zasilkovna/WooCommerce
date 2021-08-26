<?php
/**
 * Packeta plugin class for checkout.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery;

use Packetery\Options\Provider;
use PacketeryLatte\Engine;

/**
 * Class Checkout
 *
 * @package Packetery
 */
class Checkout {

	const NONCE_ACTION = 'packetery_checkout';

	/**
	 * Pickup point attributes configuration.
	 *
	 * @var array[]
	 */
	public static $pickup_point_attrs = array(
		'id'                   => array(
			'name'     => 'point_id',
			'required' => true,
		),
		'name'                 => array(
			'name'     => 'point_name',
			'required' => true,
		),
		'city'                 => array(
			'name'     => 'point_city',
			'required' => true,
		),
		'zip'                  => array(
			'name'     => 'point_zip',
			'required' => true,
		),
		'street'               => array(
			'name'     => 'point_street',
			'required' => true,
		),
		'pickupPointType'      => array(
			'name'     => 'point_type',
			'required' => true,
		),
		'carrierId'            => array(
			'name'     => 'carrier_id',
			'required' => false,
		),
		'carrierPickupPointId' => array(
			'name'     => 'point_carrier_id',
			'required' => false,
		),
		'url'                  => array(
			'name'     => 'point_url',
			'required' => true,
		),
	);

	/**
	 * PacketeryLatte engine
	 *
	 * @var Engine
	 */
	private $latte_engine;

	/**
	 * Options provider.
	 *
	 * @var Provider Options provider.
	 */
	private $options_provider;

	/**
	 * Checkout constructor.
	 *
	 * @param Engine                 $latte_engine PacketeryLatte engine.
	 * @param Provider               $options_provider Options provider.
	 */
	public function __construct( Engine $latte_engine, Provider $options_provider ) {
		$this->latte_engine     = $latte_engine;
		$this->options_provider = $options_provider;
	}

	/**
	 * Check if order is bound with Packeta
	 *
	 * @return bool
	 */
	public static function is_packetery_order(): bool {
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
		foreach ( $chosen_methods as $method ) {
			if ( strpos( $method, 'packeta-zpoint-' ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Renders widget button and information about chosen pickup point
	 */
	public function render_widget_button(): void {
		if ( self::is_packetery_order() ) {
			$language = substr( get_locale(), 0, 2 );

			$country = strtolower( WC()->customer->get_shipping_country() );
			if ( ! $country ) {
				$country = strtolower( WC()->customer->get_billing_country() );
			}
			if ( ! $country ) {
				$this->latte_engine->render( PACKETERY_PLUGIN_DIR . '/template/checkout/error_country.latte' );
				return;
			}

			$weight = WC()->cart->cart_contents_weight;
			$weight = wc_get_weight( $weight, 'kg' );

			$this->latte_engine->render(
				PACKETERY_PLUGIN_DIR . '/template/checkout/widget_button.latte',
				array(
					'language' => $language,
					'country'  => $country,
					'weight'   => $weight,
				)
			);
		}
	}

	/**
	 * Renders main checkout script
	 */
	public function render_after_checkout_form(): void {
		$app_identity = 'woocommerce-' . get_bloginfo( 'version' ) . '-' . WC_VERSION . '-' . Plugin::VERSION;

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/checkout/checkout_script.latte',
			array(
				'app_identity'       => $app_identity,
				'pickup_point_attrs' => self::$pickup_point_attrs,
				'packetery_api_key'  => $this->options_provider->get_api_key(),
			)
		);
	}

	/**
	 * Adds fields to checkout page to save the values later
	 *
	 * @link https://docs.woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/
	 *
	 * @param array $fields Fields before adding.
	 *
	 * @return array
	 */
	public static function add_pickup_point_fields( array $fields ): array {
		foreach ( self::$pickup_point_attrs as $attr ) {
			$fields['shipping'][ $attr['name'] ] = array(
				// You can use label key for other types.
				'type'     => 'hidden',
				'required' => false,
			);
		}

		return $fields;
	}

	/**
	 * Checks if all pickup point attributes are set, sets an error otherwise.
	 */
	public static function validate_pickup_point_data(): void {
		if ( self::is_packetery_order() ) {
			if (
				! isset( $_POST['_wpnonce'] ) ||
				! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), self::NONCE_ACTION )
			) {
				wp_nonce_ays( '' );
			}

			// TODO: validate carrier_id and point_carrier_id.
			$required_attrs = array_filter(
				array_combine(
					array_column( self::$pickup_point_attrs, 'name' ),
					array_column( self::$pickup_point_attrs, 'required' )
				)
			);
			foreach ( $required_attrs as $attr => $required ) {
				$attr_value = null;
				if ( isset( $_POST[ $attr ] ) ) {
					$attr_value = sanitize_text_field( wp_unslash( $_POST[ $attr ] ) );
				}
				if ( ! $attr_value ) {
					// translators: keep %s intact.
					wc_add_notice( sprintf( __( 'Pick up point attribute %s is not given.', 'packetery' ), $attr ), 'error' );
				}
			}
		}
	}

	/**
	 * Saves pickup point information to order
	 *
	 * @param int $order_id Order id.
	 */
	public static function update_order_meta( int $order_id ): void {
		if ( self::is_packetery_order() ) {
			if (
				! isset( $_POST['_wpnonce'] ) ||
				! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), self::NONCE_ACTION )
			) {
				wp_nonce_ays( '' );
			}
			foreach ( self::$pickup_point_attrs as $attr ) {
				if ( isset( $_POST[ $attr['name'] ] ) ) {
					$attr_value = sanitize_text_field( wp_unslash( $_POST[ $attr['name'] ] ) );
					update_post_meta( $order_id, 'packetery_' . $attr['name'], sanitize_text_field( $attr_value ) );
				}
			}
		}
	}

	/**
	 * Renders nonce field
	 */
	public static function render_nonce_field() {
		wp_nonce_field( self::NONCE_ACTION );
	}

	/**
	 * Registers Packeta checkout hooks
	 */
	public function register_hooks(): void {
		add_action( 'woocommerce_review_order_before_payment', array( $this, 'render_widget_button' ) );
		add_action( 'woocommerce_after_checkout_form', array( $this, 'render_after_checkout_form' ) );
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'add_pickup_point_fields' ) );
		add_action( 'woocommerce_after_order_notes', array( __CLASS__, 'render_nonce_field' ) );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'validate_pickup_point_data' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'update_order_meta' ) );
	}

}
