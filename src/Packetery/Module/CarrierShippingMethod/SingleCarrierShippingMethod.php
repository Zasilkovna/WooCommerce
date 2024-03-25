<?php

namespace Packetery\Module\CarrierShippingMethod;

use Packetery\Module\Checkout;
use Packetery\Module\CompatibilityBridge;
use Packetery\Module\ShippingMethod;

abstract class SingleCarrierShippingMethod extends \WC_Shipping_Method {

	/**
	 * @var string
	 */
	public const CARRIER_ID = '';

	/**
	 * Checkout object.
	 *
	 * @var Checkout
	 */
	private $checkout;

	public function __construct( int $instance_id = 0 ) {
		parent::__construct();
		$this->id          = self::getMethodId();
		$this->instance_id = absint( $instance_id );
		// todo Load Carrier settings, use titles.
		$this->method_title = __( 'Packeta', 'packeta' );
		$this->title        = __( 'Packeta', 'packeta' );
		// todo According settings.
		$this->enabled  = 'yes';

		$this->supports = [
			'shipping-zones',
		];
		$this->init();

		$container      = CompatibilityBridge::getContainer();
		$this->checkout = $container->getByType( Checkout::class );
	}

	public function init(): void {
		\add_action(
			'woocommerce_update_options_shipping_' . $this->id,
			[
				$this,
				'process_admin_options',
			]
		);
	}

	public function calculate_shipping( $package = [] ): void {
		$customRate = $this->checkout->getCarrierShippingRate( self::CARRIER_ID );
		$this->add_rate( $customRate );
	}

	public static function getMethodId() {
		return ShippingMethod::PACKETERY_METHOD_ID . '_' . self::CARRIER_ID;
	}

}
