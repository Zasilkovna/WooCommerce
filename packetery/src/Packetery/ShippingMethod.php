<?php
/**
 * Packeta shipping method class.
 *
 * @package Packetery
 */

namespace Packetery;

/**
 * Packeta shipping method class.
 */
class ShippingMethod extends \WC_Shipping_Method {

	/**
	 * Checkout object.
	 *
	 * @var Checkout
	 */
	private $checkout;

	/**
	 * Constructor for Packeta shipping class
	 *
	 * @param int $instance_id Shipping method instance id.
	 */
	public function __construct( int $instance_id = 0 ) {
		parent::__construct();
		$this->id                 = 'packetery_shipping_method';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Packeta Shipping Method', 'packetery' );
		$this->title              = __( 'Packeta Shipping Method', 'packetery' );
		$this->method_description = __( 'Description of Packeta shipping method', 'packetery' );
		$this->enabled            = 'yes'; // This can be added as an setting but for this example its forced enabled.
		$this->supports           = array(
			'shipping-zones',
		);
		$this->init();

		$container      = CompatibilityBridge::getContainer();
		$this->checkout = $container->getByType( Checkout::class );
	}

	/**
	 * Init settings.
	 *
	 * @return void
	 */
	public function init(): void {
		// todo Load the settings API
		// $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
		// $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

		// Save settings in admin if you have any defined.
		\add_action(
			'woocommerce_update_options_shipping_' . $this->id,
			array(
				$this,
				'process_admin_options',
			)
		);
	}

	/**
	 * Function to calculate shipping fee.
	 * Triggered by cart contents change, country change.
	 *
	 * @param array $package Order information.
	 *
	 * @return void
	 */
	public function calculate_shipping( $package = array() ): void {
		$customRates = $this->checkout->getShippingRates();
		foreach ( $customRates as $customRate ) {
			$this->add_rate( $customRate );
		}
	}
}
