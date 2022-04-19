<?php
/**
 * Packeta shipping method class.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

/**
 * Packeta shipping method class.
 */
class ShippingMethod extends \WC_Shipping_Method {

	public const PACKETERY_METHOD_ID = 'packetery_shipping_method';

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
		$this->id           = self::PACKETERY_METHOD_ID;
		$this->instance_id  = absint( $instance_id );
		$this->method_title = __( 'Packeta Shipping Method', 'packetery' );
		$this->title        = __( 'Packeta Shipping Method', 'packetery' );
		$this->enabled      = 'yes'; // This can be added as a setting.
		$this->supports     = array(
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
	public function calculate_shipping( $package = [] ): void {
		$customRates = $this->checkout->getShippingRates();
		foreach ( $customRates as $customRate ) {
			$this->add_rate( $customRate );
		}
	}
}
