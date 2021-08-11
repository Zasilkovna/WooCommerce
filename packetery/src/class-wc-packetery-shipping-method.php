<?php
/**
 * Packeta shipping method class.

 * @package Packetery
 */

/**
 * Packeta shipping method class.
 */
class WC_Packetery_Shipping_Method extends WC_Shipping_Method {
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
		add_action(
			'woocommerce_update_options_shipping_' . $this->id,
			array(
				$this,
				'process_admin_options',
			)
		);
	}

	/**
	 * Function to calculate shipping fee.
	 *
	 * @param array $package Order information.
	 *
	 * @return void
	 */
	public function calculate_shipping( $package = array() ): void {
		$custom_rates = array(
			array(
				'label'    => 'Packeta ZPoint rate 1',
				'id'       => 'packeta-zpoint-1',
				'cost'     => 0,
				'taxes'    => '',
				'calc_tax' => 'per_order',
			),
			array(
				'label'    => 'Packeta ZPoint rate 2',
				'id'       => 'packeta-zpoint-2',
				'cost'     => 0,
				'taxes'    => '',
				'calc_tax' => 'per_order',
			),
			array(
				'label'    => 'Packeta HD rate',
				'id'       => 'packeta-hd-1',
				'cost'     => 0,
				'taxes'    => '',
				'calc_tax' => 'per_order',
			),
		);

		foreach ( $custom_rates as $custom_rate ) {
			$this->add_rate( $custom_rate );
		}
	}
}
