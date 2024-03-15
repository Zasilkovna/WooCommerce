<?php
/**
 * WooCommerce Subscriptions Extend Store API.
 *
 * A class to extend the store public API with subscription related data
 * for each subscription item
 *
 * @package WooCommerce Subscriptions
 */
use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;

add_action( 'woocommerce_blocks_loaded', function() {
	$extend = StoreApi::container()->get( ExtendSchema::class );
	WC_Subscriptions_Extend_Store_Endpoint::init( $extend );
});

class WC_Subscriptions_Extend_Store_Endpoint {
	/**
	 * Stores Rest Extending instance.
	 *
	 * @var ExtendSchema
	 */
	private static $extend;

	/**
	 * Plugin Identifier, unique to each plugin.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'subscriptions';

	/**
	 * Bootstraps the class and hooks required data.
	 *
	 * @param ExtendSchema $extend_rest_api An instance of the ExtendSchema class.
	 *
	 * @since 3.1.0
	 */
	public static function init( ExtendSchema $extend_rest_api ) {
		self::$extend = $extend_rest_api;
		self::extend_store();
	}

	/**
	 * Registers the actual data into each endpoint.
	 */
	public static function extend_store() {

		// Register into `cart/items`
		self::$extend->register_endpoint_data(
			array(
				'endpoint'        => CartItemSchema::IDENTIFIER,
				'namespace'       => self::IDENTIFIER,
				'data_callback'   => array( 'WC_Subscriptions_Extend_Store_Endpoint', 'extend_cart_item_data' ),
				'schema_callback' => array( 'WC_Subscriptions_Extend_Store_Endpoint', 'extend_cart_item_schema' ),
				'schema_type'       => ARRAY_A,
			)
		);
	}

	/**
	 * Register subscription product data into cart/items endpoint.
	 *
	 * @param array $cart_item Current cart item data.
	 *
	 * @return array $item_data Registered data or empty array if condition is not satisfied.
	 */
	public static function extend_cart_item_data( $cart_item ) {
		$product   = $cart_item['data'];
		$item_data = array(
			'billing_period'      => null,
			'billing_interval'    => null,
			'subscription_length' => null,
			'trial_length'        => null,
			'trial_period'        => null,
			'sign_up_fees'        => null,
			'sign_up_fees_tax'    => null,

		);

		if ( in_array( $product->get_type(), array( 'subscription', 'subscription_variation' ), true ) ) {
			$item_data = array_merge(
				array(
					'billing_period'      => WC_Subscriptions_Product::get_period( $product ),
					'billing_interval'    => (int) WC_Subscriptions_Product::get_interval( $product ),
					'subscription_length' => (int) WC_Subscriptions_Product::get_length( $product ),
					'trial_length'        => (int) WC_Subscriptions_Product::get_trial_length( $product ),
					'trial_period'        => WC_Subscriptions_Product::get_trial_period( $product ),
				),
				self::format_sign_up_fees( $product )
			);
		}

		return $item_data;
	}

	/**
	 * Register subscription product schema into cart/items endpoint.
	 *
	 * @return array Registered schema.
	 */
	public static function extend_cart_item_schema() {
		return array(
			'billing_period'      => array(
				'description' => __( 'Billing period for the subscription.', 'woocommerce-subscriptions' ),
				'type'        => array( 'string', 'null' ),
				'enum'        => array_keys( wcs_get_subscription_period_strings() ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'billing_interval'    => array(
				'description' => __( 'The number of billing periods between subscription renewals.', 'woocommerce-subscriptions' ),
				'type'        => array( 'integer', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'subscription_length' => array(
				'description' => __( 'Subscription Product length.', 'woocommerce-subscriptions' ),
				'type'        => array( 'integer', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'trial_period'        => array(
				'description' => __( 'Subscription Product trial period.', 'woocommerce-subscriptions' ),
				'type'        => array( 'string', 'null' ),
				'enum'        => array_keys( wcs_get_subscription_period_strings() ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'trial_length'        => array(
				'description' => __( 'Subscription Product trial interval.', 'woocommerce-subscriptions' ),
				'type'        => array( 'integer', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'sign_up_fees'        => array(
				'description' => __( 'Subscription Product signup fees.', 'woocommerce-subscriptions' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'sign_up_fees_tax'    => array(
				'description' => __( 'Subscription Product signup fees taxes.', 'woocommerce-subscriptions' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}


	/**
	 * Format sign-up fees.
	 *
	 * @param \WC_Product $product current product.
	 * @return array
	 */
	private static function format_sign_up_fees( $product ) {
		$fees_excluding_tax = wcs_get_price_excluding_tax(
			$product,
			array(
				'qty'   => 1,
				'price' => WC_Subscriptions_Product::get_sign_up_fee( $product ),
			)
		);

		$fees_including_tax = wcs_get_price_including_tax(
			$product,
			array(
				'qty'   => 1,
				'price' => WC_Subscriptions_Product::get_sign_up_fee( $product ),
			)
		);

		$money_formatter = self::$extend->get_formatter( 'money' );

		return array(
			'sign_up_fees'     => $money_formatter->format(
				$fees_excluding_tax
			),
			'sign_up_fees_tax' => $money_formatter->format(
				$fees_including_tax
				- $fees_excluding_tax
			),

		);
	}
}
