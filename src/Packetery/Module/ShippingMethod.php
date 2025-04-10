<?php

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Module\Checkout\ShippingRateFactory;
use Packetery\Module\Exception\ProductNotFoundException;
use WC_Shipping_Method;

class ShippingMethod extends WC_Shipping_Method {

	public const PACKETERY_METHOD_ID = 'packetery_shipping_method';

	/**
	 * @var ShippingRateFactory
	 */
	private $shippingRateFactory;

	public function __construct( int $instanceId = 0 ) {
		parent::__construct();

		$this->id = self::PACKETERY_METHOD_ID;
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		$this->instance_id = absint( $instanceId );
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		$this->method_title = __( 'Packeta', 'packeta' );
		$this->title        = __( 'Packeta', 'packeta' );
		$this->enabled      = 'yes'; // This can be added as a setting.
		$this->supports     = [
			'shipping-zones',
		];
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		$this->tax_status = 'taxable';

		$this->init();

		$container                 = CompatibilityBridge::getContainer();
		$this->shippingRateFactory = $container->getByType( ShippingRateFactory::class );
	}

	/**
	 * Init user set variables. Derived from WC_Shipping_Flat_Rate.
	 */
	public function init(): void {
		add_action(
			'woocommerce_update_options_shipping_' . $this->id,
			function () {
				$this->process_admin_options();
			}
		);
	}

	public function get_admin_options_html(): string {
		return '';
	}

	/**
	 * @param array<string|int, mixed> $package
	 *
	 * @return void
	 * @throws ProductNotFoundException
	 */
	public function calculate_shipping( $package = [] ): void {
		$allowedCarrierNames = null;

		$customRates = $this->shippingRateFactory->createShippingRates(
			$allowedCarrierNames,
			$this->id,
			// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
			$this->instance_id
		);
		foreach ( $customRates as $customRate ) {
			$this->add_rate( $customRate );
		}
	}

	/**
	 * Derived from settings-flat-rate.php.
	 *
	 * @return array<string|int, mixed>
	 */
	public function get_instance_form_fields(): array {
		return [];
	}
}
