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
use Packetery\Nette\DI\Container;
use Packetery\Nette\Http\Request;

/**
 * Packeta shipping method class.
 */
class ShippingMethod extends \WC_Shipping_Method {

	public const PACKETERY_METHOD_ID = 'packetery_shipping_method';

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
	private $carrierRepository;

	/**
	 * Latte engine
	 *
	 * @var Engine|null
	 */
	private $latteEngine;

	/**
	 * Http request.
	 *
	 * @var Request|null
	 */
	private $httpRequest;

	/**
	 * Are we using carrier settings native for WooCommerce?
	 *
	 * @var WcSettingsConfig
	 */
	private $wcCarrierSettingsConfig;

	/**
	 * Constructor for Packeta shipping class
	 *
	 * @param int $instance_id Shipping method instance id.
	 */
	public function __construct( int $instance_id = 0 ) {
		parent::__construct();

		$this->container               = CompatibilityBridge::getContainer();
		$this->wcCarrierSettingsConfig = $this->container->getByType( WcSettingsConfig::class );

		$this->id           = self::PACKETERY_METHOD_ID;
		$this->instance_id  = absint( $instance_id );
		$this->method_title = __( 'Packeta', 'packeta' );
		$this->title        = __( 'Packeta', 'packeta' );
		$this->enabled      = 'yes'; // This can be added as a setting.
		$this->supports     = [
			'shipping-zones',
		];

		if ( $this->wcCarrierSettingsConfig->isActive() ) {
			$this->method_description = __( 'Allows to choose one of Packeta delivery methods', 'packeta' );
			$this->supports[]         = 'instance-settings';
			$this->supports[]         = 'instance-settings-modal';
		}

		$this->init();
		$this->checkout = $this->container->getByType( Checkout::class );

		if ( $this->wcCarrierSettingsConfig->isActive() ) {
			$this->httpRequest       = $this->container->getByType( Request::class );
			$this->carrierRepository = $this->container->getByType( Carrier\EntityRepository::class );

			// Called in WC_Shipping_Method during update_option.
			add_filter(
				'woocommerce_shipping_' . $this->id . '_instance_settings_values',
				[
					$this,
					'saveCustomSettings',
				],
				10,
				2
			);
			$this->options = get_option( sprintf( 'woocommerce_%s_%s_settings', $this->id, $this->instance_id ) );
		}
	}

	/**
	 * Init user set variables. Derived from WC_Shipping_Flat_Rate.
	 */
	public function init(): void {
		if ( ! $this->wcCarrierSettingsConfig->isActive() ) {
			add_action(
				'woocommerce_update_options_shipping_' . $this->id,
				[
					$this,
					'process_admin_options',
				]
			);

			return;
		}

		$this->instance_form_fields = $this->getInstanceFormFields();
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

		$this->latteEngine = $this->container->getByType( Engine::class );
		$availableCarriers = $this->carrierRepository->getCarriersForShippingRate( $this->get_rate_id() );

		// We don't want to add default fields.
		if ( $this->instance_id ) {
			$settingsHtml = $this->generate_settings_html( $this->get_instance_form_fields(), false );
		} else {
			$settingsHtml = $this->generate_settings_html( $this->get_form_fields(), false );
		}

		$carrierSettingsLinkBase = add_query_arg(
			[
				'page'                                    => Carrier\OptionsPage::SLUG,
				Carrier\OptionsPage::PARAMETER_CARRIER_ID => '',
			],
			get_admin_url( null, 'admin.php' )
		);

		$latteParams = [
			'settingsHtml'            => $settingsHtml,
			'availableCarriers'       => $availableCarriers,
			'options'                 => $this->options,
			'jsUrl'                   => Plugin::buildAssetUrl( 'public/admin-carrier-modal.js' ),
			'carrierSettingsLinkBase' => $carrierSettingsLinkBase . '=',
			'translations'            => [
				'selectedShippingMethod'  => __( 'Selected shipping method', 'packeta' ),
				'pleaseSelect'            => __( 'please select', 'packeta' ),
				'carrierSettingsLinkText' => __( 'Configure selected carrier', 'packeta' ),
			],
		];

		return $this->latteEngine->renderToString( PACKETERY_PLUGIN_DIR . '/template/carrier/carrier-modal.latte', $latteParams );
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
		$allowedCarrierNames = null;

		if ( $this->wcCarrierSettingsConfig->isActive() ) {
			$allowedCarrierNames = [];
			$zone                = \WC_Shipping_Zones::get_zone_matching_package( $package );
			$shippingMethods     = $zone->get_shipping_methods( true );
			if ( $shippingMethods ) {
				foreach ( $shippingMethods as $shippingMethod ) {
					if ( isset( $shippingMethod->options[ self::PACKETERY_METHOD_ID ] ) ) {
						$allowedCarrierNames[ $shippingMethod->options[ self::PACKETERY_METHOD_ID ] ] = $shippingMethod->options['title'];
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
	 * Saves method's settings.
	 *
	 * @param mixed $settings Settings.
	 *
	 * @return mixed
	 */
	public function saveCustomSettings( $settings ) {
		$post = $this->httpRequest->getPost();
		if ( isset( $post['data'] ) ) {
			$settings[ self::PACKETERY_METHOD_ID ] = $post['data'][ self::PACKETERY_METHOD_ID ];
		}

		return $settings;
	}

	/**
	 * Derived from settings-flat-rate.php.
	 *
	 * @return array
	 */
	private function getInstanceFormFields(): array {
		$settings = [
			'title' => [
				'title'       => __( 'Method title', 'packeta' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'packeta' ),
				'default'     => __( 'Packeta', 'packeta' ),
				'desc_tip'    => true,
			],
		];

		return $settings;
	}

}
