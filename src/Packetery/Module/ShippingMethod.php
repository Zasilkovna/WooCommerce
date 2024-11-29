<?php
/**
 * Packeta shipping method class.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Latte\Engine;
use Packetery\Module\Carrier\WcSettingsConfig;
use Packetery\Module\Exception\ProductNotFoundException;
use Packetery\Module\Views\UrlBuilder;
use Packetery\Nette\DI\Container;

/**
 * Packeta shipping method class.
 */
class ShippingMethod extends \WC_Shipping_Method {

	public const PACKETERY_METHOD_ID = 'packetery_shipping_method';
	public const OPTION_CARRIER_ID   = 'carrier_id';

	/**
	 * Options.
	 *
	 * @var null|false|array<string, string|null>
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
	private $carrierRepository;

	/**
	 * Latte engine
	 *
	 * @var Engine|null
	 */
	private $latteEngine;

	/**
	 * Are we using carrier settings native for WooCommerce?
	 *
	 * @var WcSettingsConfig
	 */
	private $wcCarrierSettingsConfig;

	/**
	 * @var UrlBuilder
	 */
	private $urlBuilder;

	/**
	 * Constructor for Packeta shipping class
	 *
	 * @param int $instanceId Shipping method instance id.
	 */
	public function __construct( int $instanceId = 0 ) {
		parent::__construct();

		$this->container               = CompatibilityBridge::getContainer();
		$this->wcCarrierSettingsConfig = $this->container->getByType( WcSettingsConfig::class );
		$this->urlBuilder              = $this->container->getByType( UrlBuilder::class );

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

		if ( $this->wcCarrierSettingsConfig->isActive() ) {
			// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
			$this->method_description = __( 'Allows to choose one of Packeta delivery methods', 'packeta' );
			$this->supports[]         = 'instance-settings';
			$this->supports[]         = 'instance-settings-modal';
			$this->carrierRepository  = $this->container->getByType( Carrier\EntityRepository::class );
			// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
			$this->options = get_option( sprintf( 'woocommerce_%s_%s_settings', $this->id, $this->instance_id ) );
		}

		$this->init();
		$this->checkout = $this->container->getByType( Checkout::class );
	}

	/**
	 * Init user set variables. Derived from WC_Shipping_Flat_Rate.
	 */
	public function init(): void {
		if ( ! $this->wcCarrierSettingsConfig->isActive() ) {
			add_action(
				'woocommerce_update_options_shipping_' . $this->id,
				function () {
					$this->process_admin_options();
				}
			);

			return;
		}

		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		$this->instance_form_fields = $this->get_instance_form_fields();
		$this->title                = $this->get_option( 'title' );
	}

	/**
	 * Returns admin options as a html string.
	 *
	 * @return string
	 */
	public function get_admin_options_html(): string {
		if ( ! $this->wcCarrierSettingsConfig->isActive() ) {
			return '';
		}

		$settingsHtml = $this->generate_settings_html( $this->get_instance_form_fields(), false );

		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
		return '<table class="form-table">' . $settingsHtml . "</table>\n" . '<script type="text/javascript" src="' . $this->urlBuilder->buildAssetUrl( 'public/js/admin-carrier-modal.js' ) . '"></script>';
	}

	/**
	 * @param array<string|int, mixed> $package Shipping package.
	 *
	 * @return void
	 * @throws ProductNotFoundException Product not found.
	 */
	public function calculate_shipping( $package = [] ): void {
		$allowedCarrierNames = null;

		if ( $this->wcCarrierSettingsConfig->isActive() ) {
			$allowedCarrierNames = [];
			$zone                = \WC_Shipping_Zones::get_zone_matching_package( $package );
			$shippingMethods     = $zone->get_shipping_methods( true );
			if ( count( $shippingMethods ) > 0 ) {
				foreach ( $shippingMethods as $shippingMethod ) {
					if ( isset( $shippingMethod->options[ self::OPTION_CARRIER_ID ] ) ) {
						$allowedCarrierNames[ $shippingMethod->options[ self::OPTION_CARRIER_ID ] ] = $shippingMethod->options['title'];
					}
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
	 * @return array<string|int, mixed>
	 */
	public function get_instance_form_fields(): array {
		if ( ! $this->wcCarrierSettingsConfig->isActive() ) {
			return [];
		}

		$this->latteEngine = $this->container->getByType( Engine::class );
		$availableCarriers = $this->carrierRepository->getCarriersForShippingRate( $this->get_rate_id() );

		$availableCarriersOptions = [
			'' => __( 'please select', 'packeta' ),
		];
		foreach ( $availableCarriers as $carrier ) {
			$availableCarriersOptions[ $carrier->getId() ] = $carrier->getName();
		}

		$carrierSettingsLinkBase = add_query_arg(
			[
				'page'                                    => Carrier\OptionsPage::SLUG,
				Carrier\OptionsPage::PARAMETER_CARRIER_ID => '',
			],
			get_admin_url( null, 'admin.php' )
		);

		$latteParams = [
			'options'                 => $this->options,
			'carrierSettingsLinkBase' => $carrierSettingsLinkBase . '=',
			'translations'            => [
				'carrierSettingsLinkText' => __( 'Configure selected carrier', 'packeta' ),
			],
		];

		$settings = [
			'title'                 => [
				'title'       => __( 'Method title', 'packeta' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'packeta' ),
				'default'     => __( 'Packeta', 'packeta' ),
				'desc_tip'    => true,
			],
			self::OPTION_CARRIER_ID => [
				'title'   => __( 'Selected shipping method', 'packeta' ),
				'type'    => 'select',
				'default' => '',
				'options' => $availableCarriersOptions,
			],
			'custom_html'           => [
				'title'       => '',
				'type'        => 'title',
				'description' => $this->latteEngine->renderToString(
					PACKETERY_PLUGIN_DIR . '/template/carrier/carrier-modal-fragment.latte',
					$latteParams
				),
				'default'     => '',
			],
		];

		return $settings;
	}
}
