<?php
/**
 * Packeta shipping method class.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Shipping;

use Packetery\Latte\Engine;
use Packetery\Module\Carrier;
use Packetery\Module\Checkout;
use Packetery\Module\CompatibilityBridge;
use Packetery\Nette\DI\Container;

/**
 * Packeta shipping method class.
 */
abstract class BaseShippingMethod extends \WC_Shipping_Method {
	public const PACKETA_METHOD_PREFIX = 'packeta_method_';

	/**
	 * Options.
	 *
	 * @var null|array|false
	 */
	private $options;

	/**
	 * DI container.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Checkout object.
	 *
	 * @var Checkout
	 */
	private $checkout;

	/**
	 * CarrierRepository
	 *
	 * @var Carrier\EntityRepository|null
	 */
	protected $carrierRepository;

	/**
	 * Constructor for Packeta shipping class
	 *
	 * @param int $instance_id Shipping method instance id.
	 */
	public function __construct( int $instance_id = 0 ) {
		parent::__construct();

		$this->container         = CompatibilityBridge::getContainer();
		$this->carrierRepository = $this->container->getByType( Carrier\EntityRepository::class );

		$this->id          = static::getShippingMethodId();
		$this->instance_id = absint( $instance_id );

		$this->method_title = __( 'Packeta', 'packeta' );
		$this->title        = __( 'Packeta', 'packeta' );
		$carrier            = $this->carrierRepository->getAnyById( static::CARRIER_ID );
		if ( null !== $carrier ) {
			$this->method_title = $carrier->getName();
			$this->title        = $carrier->getName();
		}

		$this->enabled  = 'yes'; // This can be added as a setting.
		$this->supports = [
			'shipping-zones',
		];

		$this->method_description = __( 'Allows to choose one of Packeta delivery methods', 'packeta' );
		$this->supports[]         = 'instance-settings';
		$this->supports[]         = 'instance-settings-modal';
		$this->options            = get_option( sprintf( 'woocommerce_%s_%s_settings', $this->id, $this->instance_id ) );

		$this->init();
		$this->checkout = $this->container->getByType( Checkout::class );
	}

	/**
	 * Init user set variables. Derived from WC_Shipping_Flat_Rate.
	 */
	public function init(): void {
		$this->instance_form_fields = $this->get_instance_form_fields();
		$this->title                = $this->get_option( 'title' );
	}

	/**
	 * Returns admin options as a html string.
	 *
	 * @return string
	 */
	public function get_admin_options_html(): string {
		$settingsHtml = $this->generate_settings_html( $this->get_instance_form_fields(), false );

		return '<table class="form-table">' . $settingsHtml . "</table>\n";
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
		$allowedCarrierNames = [];
		$zone                = \WC_Shipping_Zones::get_zone_matching_package( $package );
		$shippingMethods     = $zone->get_shipping_methods( true );
		if ( $shippingMethods ) {
			foreach ( $shippingMethods as $shippingMethod ) {
				if ( $shippingMethod instanceof self ) {
					$allowedCarrierNames[ $shippingMethod::CARRIER_ID ] = $shippingMethod->options['title'] ?? $shippingMethod->get_title();
				}
			}
		}

		$customRates = $this->checkout->getShippingRates( $allowedCarrierNames );
		foreach ( $customRates as $customRate ) {
			$this->add_rate( $customRate );
		}
	}

	/**
	 * Derived from settings-flat-rate.php.
	 *
	 * @return array
	 */
	public function get_instance_form_fields(): array {
		$latteEngine = $this->container->getByType( Engine::class );

		$carrierSettingsLinkBase = add_query_arg(
			[
				'page'                                    => Carrier\OptionsPage::SLUG,
				Carrier\OptionsPage::PARAMETER_CARRIER_ID => static::CARRIER_ID,
			],
			get_admin_url( null, 'admin.php' )
		);

		$latteParams = [
			'options'             => $this->options,
			'carrierSettingsLink' => $carrierSettingsLinkBase,
			'translations'        => [
				'carrierSettingsLinkText' => __( 'Configure selected carrier', 'packeta' ),
			],
		];

		$settings = [
			'title'       => [
				'title'       => __( 'Method title', 'packeta' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'packeta' ),
				'default'     => $this->method_title,
				'desc_tip'    => true,
			],
			'custom_html' => [
				'title'       => '',
				'type'        => 'title',
				'default'     => '',
				'description' => $latteEngine->renderToString(
					PACKETERY_PLUGIN_DIR . '/template/carrier/carrier-modal-fragment.latte',
					$latteParams
				),
			],
		];

		return $settings;
	}

	/**
	 * Returns WooCommerce identificator of carrier shipping method.
	 *
	 * @return string
	 */
	public static function getShippingMethodId(): string {
		return self::PACKETA_METHOD_PREFIX . static::CARRIER_ID;
	}

}
