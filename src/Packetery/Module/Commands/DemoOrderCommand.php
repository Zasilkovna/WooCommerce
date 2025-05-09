<?php
declare(strict_types=1);

namespace Packetery\Module\Commands;

use Packetery\Module\Order\Builder;
use Packetery\Module\Order\Repository;
use stdClass;
use WC_Customer;
use WC_Order_Item_Shipping;
use WP_CLI;
use WP_User;

class DemoOrderCommand {

	/**
	 * @var Builder
	 */
	private $builder;

	/**
	 * @var Repository
	 */
	private $orderRepository;

	private function __construct( Builder $builder, Repository $orderRepository ) {
		$this->builder         = $builder;
		$this->orderRepository = $orderRepository;
	}

	/**
	 * Generates a demo order with Packet shipping.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<count>]
	 * : Number of orders to generate. Default: 1
	 *
	 * [--payment-method=<payment-method>]
	 * : Payment method to set. E.g., 'cod', 'bacs'. If not set, uses 'basc' by default.
	 *
	 * ## EXAMPLES
	 *
	 *     wp packeta-plugin-build-demo-order --count=5 --payment-method=cod
	 *
	 * @param string[] $args
	 * @param array<string, mixed> $assoc_args
	 */
	// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
	public function __invoke( array $args, array $assoc_args ): void {
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
		$count = isset( $assoc_args['count'] ) ? (int) $assoc_args['count'] : 1;
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
		$paymentMethod = isset( $assoc_args['payment-method'] ) ? sanitize_text_field( $assoc_args['payment-method'] ) : 'basc';

		WP_CLI::line( 'Disabling email sending' );
		$this->disableEmails();
		WP_CLI::line( 'Email sending has been disabled' );
		WP_CLI::line( 'Try create packeta demo customer' );
		$customer = $this->makeCustomer();
		WP_CLI::line( 'Start creating Packeta demo orders' );
		for ( $i = 0; $i < $count; $i++ ) {
			$wcOrder      = $this->makeWcOrder( $customer, $paymentMethod );
			$packetaData  = $this->makePacketaData( (string) $wcOrder->get_id() );
			$packetaOrder = $this->builder->build( $wcOrder, $packetaData );
			$this->orderRepository->save( $packetaOrder );
		}
		WP_CLI::line( 'Packeta demo orders have been successfully created' );
	}

	public static function createCommand( Builder $builder, Repository $orderRepository ): DemoOrderCommand {
		return new self( $builder, $orderRepository );
	}

	private function makeWcOrder( WC_Customer $customer, string $paymentMethod ): \WC_Order {
		$order    = new \WC_Order();
		$products = $this->getProducts();

		foreach ( $products as $product ) {
			$quantity = wp_rand( 1, 2 );
			$order->add_product( $product, $quantity );
		}

		$order->set_customer_id( $customer->get_id() );
		$order->set_created_via( 'Packeta demo order generator' );
		$order->set_currency( get_woocommerce_currency() );
		$order->set_billing_first_name( $customer->get_billing_first_name() );
		$order->set_billing_last_name( $customer->get_billing_last_name() );
		$order->set_billing_address_1( $customer->get_billing_address_1() );
		$order->set_billing_address_2( $customer->get_billing_address_2() );
		$order->set_billing_email( $customer->get_billing_email() );
		$order->set_billing_phone( $customer->get_billing_phone() );
		$order->set_billing_city( $customer->get_billing_city() );
		$order->set_billing_postcode( $customer->get_billing_postcode() );
		$order->set_billing_state( $customer->get_billing_state() );
		$order->set_billing_country( $customer->get_billing_country() );
		$order->set_billing_company( $customer->get_billing_company() );
		$order->set_shipping_first_name( $customer->get_shipping_first_name() );
		$order->set_shipping_last_name( $customer->get_shipping_last_name() );
		$order->set_shipping_address_1( $customer->get_shipping_address_1() );
		$order->set_shipping_address_2( $customer->get_shipping_address_2() );
		$order->set_shipping_city( $customer->get_shipping_city() );
		$order->set_shipping_postcode( $customer->get_shipping_postcode() );
		$order->set_shipping_state( $customer->get_shipping_state() );
		$order->set_shipping_country( $customer->get_shipping_country() );
		$order->set_shipping_company( $customer->get_shipping_company() );

		$shippingItem = new WC_Order_Item_Shipping();
		$shippingItem->set_method_title( 'CZ Zásilkovna domů HD' );
		$shippingItem->set_method_id( 'packeta_method_106' );
		$shippingItem->set_total( '119' );
		$order->add_item( $shippingItem );

		$order->set_payment_method( $paymentMethod );
		$order->set_status( 'on-hold' );

		$order->calculate_totals( true );
		$order->save();

		return $order;
	}

	private function getProducts(): array {
		global $wpdb;

		$minAmountProducts = 1;
		$maxAmountProducts = 3;
		$products          = [];

		$countProducts = (int) $wpdb->get_var(
			"SELECT COUNT( DISTINCT ID )
			FROM {$wpdb->posts}
			WHERE post_type='product'
			AND post_status='publish'"
		);

		$randomCountProducts = wp_rand( $minAmountProducts, $maxAmountProducts );

		if ( $randomCountProducts > $countProducts ) {
			$randomCountProducts = $countProducts;
		}

		$query = new \WC_Product_Query(
			[
				'limit'   => $randomCountProducts,
				'return'  => 'ids',
				'orderby' => 'rand',
			]
		);

		foreach ( $query->get_products() as $productId ) {
			$product = wc_get_product( $productId );

			if ( $product->is_type( 'variable' ) ) {
				if ( ! $product instanceof \WC_Product_Variable ) {
					continue;
				}
				$availableVariations = $product->get_available_variations();
				$countVariations     = count( $availableVariations );
				if ( $countVariations <= 0 ) {
					continue;
				}
				$index      = wp_rand( 0, $countVariations - 1 );
				$products[] = new \WC_Product_Variation( $availableVariations[ $index ]['variation_id'] );
			} else {
				$products[] = new \WC_Product( $productId );
			}
		}

		return $products;
	}

	private function makeCustomer(): WC_Customer {
		$email = 'john.packeta@example.cz';
		$user  = get_user_by( 'email', $email );

		if ( $user instanceof WP_User ) {
			WP_CLI::line( 'The user is already created with an ID: ' . $user->ID );

			return new WC_Customer( $user->ID );
		}

		$customer = new WC_Customer();
		$customer->set_username( 'john.packeta' );
		$customer->set_password( 'Packeta123456' );
		$customer->set_email( $email );

		$customer->set_props(
			[
				'billing_first_name'  => 'John',
				'billing_last_name'   => 'Packeta',
				'billing_company'     => '',
				'billing_address_1'   => 'Českomoravská 2408/1a',
				'billing_address_2'   => '',
				'billing_city'        => 'Praha',
				'billing_postcode'    => '190 00',
				'billing_country'     => 'CZ',
				'billing_state'       => '',
				'billing_email'       => 'john.packeta@example.cz',
				'billing_phone'       => '+420123456789',

				'shipping_first_name' => 'John',
				'shipping_last_name'  => 'Packeta',
				'shipping_company'    => '',
				'shipping_address_1'  => 'Českomoravská 2408/1a',
				'shipping_address_2'  => '',
				'shipping_city'       => 'Praha',
				'shipping_postcode'   => '190 00',
				'shipping_country'    => 'CZ',
				'shipping_state'      => '',
			]
		);
		$customerId = $customer->save();

		if ( ! is_int( $customerId ) ) {
			WP_CLI::error( 'Customer creation error' );
		}

		WP_CLI::line( 'Customer was successfully created with ID: ' . $customerId );

		return $customer;
	}

	private function makePacketaData( string $orderId ): stdClass {
		$data = [
			'id'                    => $orderId,
			'carrier_id'            => '106',
			'is_exported'           => 0,
			'packet_id'             => null,
			'packet_claim_id'       => null,
			'packet_claim_password' => null,
			'packet_status'         => null,
			'stored_until'          => null,
			'is_label_printed'      => 0,
			'carrier_number'        => null,
			'weight'                => 5,
			'car_delivery_id'       => null,
			'point_id'              => null,
			'point_name'            => null,
			'point_url'             => null,
			'point_street'          => null,
			'point_zip'             => null,
			'point_city'            => null,
			'address_validated'     => 0,
			'delivery_address'      => null,
			'length'                => 100,
			'width'                 => 100,
			'height'                => 100,
			'adult_content'         => null,
			'cod'                   => 50,
			'value'                 => 50,
			'api_error_message'     => null,
			'api_error_date'        => null,
			'deliver_on'            => null,
		];

		$result = new stdClass();
		foreach ( $data as $key => $value ) {
			$result->{$key} = $value;
		}

		return $result;
	}

	private function disableEmails(): void {
		$emailActions = [
			'woocommerce_low_stock',
			'woocommerce_no_stock',
			'woocommerce_product_on_backorder',
			'woocommerce_order_status_pending_to_processing',
			'woocommerce_order_status_pending_to_completed',
			'woocommerce_order_status_processing_to_cancelled',
			'woocommerce_order_status_pending_to_failed',
			'woocommerce_order_status_pending_to_on-hold',
			'woocommerce_order_status_failed_to_processing',
			'woocommerce_order_status_failed_to_completed',
			'woocommerce_order_status_failed_to_on-hold',
			'woocommerce_order_status_cancelled_to_processing',
			'woocommerce_order_status_cancelled_to_completed',
			'woocommerce_order_status_cancelled_to_on-hold',
			'woocommerce_order_status_on-hold_to_processing',
			'woocommerce_order_status_on-hold_to_cancelled',
			'woocommerce_order_status_on-hold_to_failed',
			'woocommerce_order_status_completed',
			'woocommerce_order_status_failed',
			'woocommerce_order_fully_refunded',
			'woocommerce_order_partially_refunded',
			'woocommerce_new_customer_note',
			'woocommerce_created_customer',
		];

		foreach ( $emailActions as $action ) {
			remove_action( $action, [ 'WC_Emails', 'send_transactional_email' ] );
		}

		if ( ! has_action( 'woocommerce_allow_send_queued_transactional_email', '__return_false' ) ) {
			add_action(
				'woocommerce_allow_send_queued_transactional_email',
				static function (): void {
					// intentionally do nothing, just stop queued emails.
				}
			);
		}
	}
}
