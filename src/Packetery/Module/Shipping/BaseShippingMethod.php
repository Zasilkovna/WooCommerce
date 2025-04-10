<?php

declare( strict_types=1 );

namespace Packetery\Module\Shipping;

use Packetery\Latte\Engine;
use Packetery\Module\Carrier;
use Packetery\Module\Checkout\ShippingRateFactory;
use Packetery\Module\CompatibilityBridge;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Nette\DI\Container;

use function is_array;
use function sprintf;

abstract class BaseShippingMethod extends \WC_Shipping_Method {
	public const PACKETA_METHOD_PREFIX = 'packeta_method_';

	// Will be overwritten.
	public const CARRIER_ID = '';

	/**
	 * @var null|false|array<string, string|null>
	 */
	private $options;

	/**
	 * @var Container
	 */
	private $container;

	/**
	 * @var ShippingRateFactory
	 */
	private $shippingRateFactory;

	/**
	 * @var Carrier\EntityRepository|null
	 */
	protected $carrierRepository;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	/**
	 * @param int $instanceId Shipping method instance id.
	 */
	public function __construct( int $instanceId = 0 ) {
		parent::__construct();

		$this->container         = CompatibilityBridge::getContainer();
		$this->carrierRepository = $this->container->getByType( Carrier\EntityRepository::class );
		$this->wpAdapter         = $this->container->getByType( WpAdapter::class );
		$this->wcAdapter         = $this->container->getByType( WcAdapter::class );

		$this->id = static::getShippingMethodId();
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		$this->instance_id = $this->wpAdapter->absint( $instanceId );
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		$this->method_title = __( 'Packeta', 'packeta' );
		$this->title        = __( 'Packeta', 'packeta' );
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		$this->method_description = __( 'Allows to choose one of Packeta delivery methods', 'packeta' );

		$carrier = $this->carrierRepository->getAnyById( static::CARRIER_ID );
		if ( $carrier !== null ) {
			// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
			$this->method_title = $carrier->getName();
			$this->title        = $carrier->getName();
			// translators: %s is carrier name.
			$this->method_description = sprintf( __( 'Allows customers to use %s carrier delivery', 'packeta' ), $carrier->getName() ); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		}

		$this->enabled  = 'yes'; // This can be added as a setting.
		$this->supports = [
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		];
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		$this->tax_status = 'taxable';

		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		if ( $this->instance_id !== 0 ) {
			// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
			$this->options = $this->wpAdapter->getOption( sprintf( 'woocommerce_%s_%s_settings', $this->id, $this->instance_id ) );
		}

		$this->init();
		$this->shippingRateFactory = $this->container->getByType( ShippingRateFactory::class );
	}

	/**
	 * Init user set variables. Derived from WC_Shipping_Flat_Rate.
	 */
	public function init(): void {
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		$this->instance_form_fields = $this->get_instance_form_fields();
		$this->title                = $this->get_option( 'title' );
	}

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
		$zone                = $this->wcAdapter->shippingZonesGetZoneMatchingPackage( $package );
		$shippingMethods     = $zone->get_shipping_methods( true );
		if ( is_array( $shippingMethods ) && count( $shippingMethods ) > 0 ) {
			foreach ( $shippingMethods as $shippingMethod ) {
				if ( $shippingMethod instanceof self ) {
					$allowedCarrierNames[ $shippingMethod::CARRIER_ID ] = $shippingMethod->options['title'] ?? $shippingMethod->get_title();
				}
			}
		}

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
	 * @return array
	 */
	public function get_instance_form_fields(): array {
		$latteEngine = $this->container->getByType( Engine::class );

		$carrierSettingsLinkBase = $this->wpAdapter->addQueryArg(
			[
				'page'                                    => Carrier\OptionsPage::SLUG,
				Carrier\OptionsPage::PARAMETER_CARRIER_ID => static::CARRIER_ID,
			],
			$this->wpAdapter->getAdminUrl( null, 'admin.php' )
		);

		$latteParams = [
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
				// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
				'default'     => $this->method_title,
				'desc_tip'    => true,
			],
			'tax_status'  => [
				'type'    => 'hidden',
				'default' => 'taxable',
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
