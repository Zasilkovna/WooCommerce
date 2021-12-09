<?php
/**
 * Class Page
 *
 * @package Packetery\Options
 */

declare( strict_types=1 );

namespace Packetery\Module\Options;

use Packetery\Module\FormFactory;
use PacketeryLatte\Engine;
use PacketeryNette\Forms\Form;

/**
 * Class Page
 *
 * @package Packetery\Options
 */
class Page {

	private const FORM_FIELDS_CONTAINER           = 'packetery';
	private const FORM_FIELD_PACKETA_LABEL_FORMAT = 'packeta_label_format';
	private const FORM_FIELD_CARRIER_LABEL_FORMAT = 'carrier_label_format';

	private const DEFAULT_VALUE_PACKETA_LABEL_FORMAT = 'A6 on A4';
	private const DEFAULT_VALUE_CARRIER_LABEL_FORMAT = self::DEFAULT_VALUE_PACKETA_LABEL_FORMAT;

	/**
	 * PacketeryLatte_engine.
	 *
	 * @var Engine PacketeryLatte engine.
	 */
	private $latte_engine;

	/**
	 * Options Provider
	 *
	 * @var Provider
	 */
	private $optionsProvider;

	/**
	 * Form factory.
	 *
	 * @var FormFactory Form factory.
	 */
	private $formFactory;

	/**
	 * Plugin constructor.
	 *
	 * @param Engine      $latte_engine PacketeryLatte_engine.
	 * @param Provider    $optionsProvider Options provider.
	 * @param FormFactory $formFactory Form factory.
	 */
	public function __construct( Engine $latte_engine, Provider $optionsProvider, FormFactory $formFactory ) {
		$this->latte_engine    = $latte_engine;
		$this->optionsProvider = $optionsProvider;
		$this->formFactory     = $formFactory;
	}

	/**
	 * Registers WP callbacks.
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_menu_page(
			__( 'Packeta', 'packetery' ),
			__( 'Packeta', 'packetery' ),
			'manage_options',
			'packeta-options',
			'',
			'dashicons-schedule',
			55 // todo Move item to last position in menu.
		);
		add_submenu_page(
			'packeta-options',
			__( 'Settings', 'packetery' ),
			__( 'Settings', 'packetery' ),
			'manage_options',
			'packeta-options',
			array(
				$this,
				'render',
			),
			1
		);

		if ( null !== $this->optionsProvider->getOrderGridPerPage() ) {
			add_filter(
				'edit_shop_order_per_page',
				function () {
					return $this->optionsProvider->getOrderGridPerPage();
				},
				$this->optionsProvider->getOrderGridPerPagePriority()
			);
		}
	}

	/**
	 * Sets default values.
	 */
	public function setDefaultValues(): void {
		$value = get_option( self::FORM_FIELDS_CONTAINER );
		$value[ self::FORM_FIELD_PACKETA_LABEL_FORMAT ] = self::DEFAULT_VALUE_PACKETA_LABEL_FORMAT;
		$value[ self::FORM_FIELD_CARRIER_LABEL_FORMAT ] = self::DEFAULT_VALUE_CARRIER_LABEL_FORMAT;
		update_option( self::FORM_FIELDS_CONTAINER, $value );
	}

	/**
	 * Creates settings form.
	 *
	 * @return Form
	 */
	private function create_form(): Form {
		$form = $this->formFactory->create();
		$form->setAction( 'options.php' );

		$container = $form->addContainer( self::FORM_FIELDS_CONTAINER );
		$container->addText( 'api_password', __( 'API password', 'packetery' ) )
					->setRequired()
					->addRule( $form::PATTERN, __( 'API password must be 32 characters long and must contain valid characters!', 'packetery' ), '[a-z\d]{32}' );
		$container->addText( 'sender', __( 'Sender', 'packetery' ) )
					->setRequired();

		$packetaLabelFormats = $this->optionsProvider->getPacketaLabelFormats();
		$container->addSelect(
			self::FORM_FIELD_PACKETA_LABEL_FORMAT,
			__( 'Packeta Label Format', 'packetery' ),
			$packetaLabelFormats
		)->checkDefaultValue( false )->setDefaultValue( self::DEFAULT_VALUE_PACKETA_LABEL_FORMAT );

		$carrierLabelFormats = $this->optionsProvider->getCarrierLabelFormat();
		$container->addSelect(
			self::FORM_FIELD_CARRIER_LABEL_FORMAT,
			__( 'Carrier Label Format', 'packetery' ),
			$carrierLabelFormats
		)->checkDefaultValue( false )->setDefaultValue( self::DEFAULT_VALUE_CARRIER_LABEL_FORMAT );

		$gateways        = $this->getAvailablePaymentGateways();
		$enabledGateways = [];
		foreach ( $gateways as $gateway ) {
			$enabledGateways[ $gateway->id ] = $gateway->get_method_title();
		}
		$container->addSelect(
			'cod_payment_method',
			__( 'Payment method that represents cash on delivery', 'packetery' ),
			$enabledGateways
		)->setPrompt( '--' )->checkDefaultValue( false );

		$container->addSelect(
			'order_grid_per_page',
			__( 'orderGridPerPageLabel', 'packetery' ),
			[
				'100' => '100',
				'200' => '200',
			]
		)->setPrompt( __( 'defaultValue', 'packetery' ) )->checkDefaultValue( false );

		$container->addText(
			'order_grid_per_page_priority',
			__( 'orderGridPerPagePriorityLabel', 'packetery' )
		)->setRequired( true )
			->addRule( Form::INTEGER )
			->addRule( Form::MIN, null, 0 )
			->addRule( Form::MAX, null, 999999 )
			->setDefaultValue( $this->optionsProvider->getOrderGridPerPagePriority() );

		if ( $this->optionsProvider->has_any() ) {
			$container->setDefaults( $this->optionsProvider->data_to_array() );
		}

		return $form;
	}

	/**
	 * Get available gateways.
	 *
	 * @return \WC_Payment_Gateway[]
	 */
	public function getAvailablePaymentGateways(): array {
		$availableGateways = [];

		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			if ( 'yes' === $gateway->enabled ) {
				$availableGateways[ $gateway->id ] = $gateway;
			}
		}

		return array_filter( (array) apply_filters( 'woocommerce_available_payment_gateways', $availableGateways ), [ $this, 'filterValidGatewayClass' ] );
	}

	/**
	 * Callback for array filter. Returns true if gateway is of correct type.
	 *
	 * @param object $gateway Gateway to check.
	 * @return bool
	 */
	protected function filterValidGatewayClass( $gateway ): bool {
		return $gateway && is_a( $gateway, 'WC_Payment_Gateway' );
	}

	/**
	 *  Admin_init callback.
	 */
	public function admin_init(): void {
		register_setting( self::FORM_FIELDS_CONTAINER, self::FORM_FIELDS_CONTAINER, array( $this, 'options_validate' ) );
		add_settings_section( 'packetery_main', __( 'Main Settings', 'packetery' ), '', 'packeta-options' );
	}

	/**
	 * Validates options.
	 *
	 * @param array $options Packetery_options.
	 *
	 * @return array
	 */
	public function options_validate( $options ): array {
		$form = $this->create_form();
		$form[ self::FORM_FIELDS_CONTAINER ]->setValues( $options );
		if ( $form->isValid() === false ) {
			foreach ( $form[ self::FORM_FIELDS_CONTAINER ]->getControls() as $control ) {
				if ( $control->hasErrors() === false ) {
					continue;
				}

				add_settings_error( $control->getCaption(), esc_attr( $control->getName() ), $control->getError() );
				$options[ $control->getName() ] = '';
			}
		}

		$perPage         = $form[ self::FORM_FIELDS_CONTAINER ]['order_grid_per_page'];
		$perPagePriority = $form[ self::FORM_FIELDS_CONTAINER ]['order_grid_per_page_priority'];

		if ( false === $perPage->hasErrors() && $perPage->getValue() ) {
			$perPageValue         = (int) $perPage->getValue();
			$perPagePriorityValue = (int) $perPagePriority->getValue();

			add_filter(
				'edit_shop_order_per_page',
				function () use ( $perPageValue ) {
					return $perPageValue;
				},
				$perPagePriorityValue
			);
			$filterPerPage = apply_filters( 'edit_shop_order_per_page', $perPageValue );
			$filterPerPage = apply_filters( 'edit_posts_per_page', $filterPerPage, 'shop_order' );

			if ( $filterPerPage !== $perPageValue ) {
				add_settings_error( 'order_grid_per_page', 'order_grid_per_page', __( 'orderGridPerPageWasNotUsedError', 'packetery' ) );
			}
		}

		$api_password = $form[ self::FORM_FIELDS_CONTAINER ]['api_password'];
		if ( $api_password->hasErrors() === false ) {
			$api_pass           = $api_password->getValue();
			$options['api_key'] = substr( $api_pass, 0, 16 );
		} else {
			$options['api_key'] = '';
		}

		return $options;
	}

	/**
	 *  Renders page.
	 */
	public function render(): void {
		$latteParams = [ 'form' => $this->create_form() ];
		if ( ! extension_loaded( 'soap' ) ) {
			$latteParams['error'] = __( 'This plugin requires an active SOAP library for proper operation. Contact your web hosting administrator.', 'packetery' );
		}

		$latteParams['apiPasswordLink'] = trim( $this->latte_engine->renderToString( PACKETERY_PLUGIN_DIR . '/template/options/help-block-link.latte', [ 'href' => 'https://client.packeta.com/support' ] ) );

		$latteParams['senderDescription'] = sprintf(
			/* translators: 1: emphasis start 2: emphasis end 3: client section link start 4: client section link end */
			esc_html__( 'senderDescription', 'packetery' ),
			'<strong>',
			'</strong>',
			'<a href="https://client.packeta.com/senders" target="_blank">',
			'</a>'
		);

		$latteParams['exportLink'] = add_query_arg(
			[
				'page'   => 'packeta-options',
				'action' => Exporter::ACTION_EXPORT_SETTINGS,
			],
			get_admin_url( null, 'admin.php' )
		);

		$lastExport       = null;
		$lastExportOption = get_option( Exporter::OPTION_LAST_SETTINGS_EXPORT );
		if ( false !== $lastExportOption ) {
			$date = \DateTime::createFromFormat( DATE_ATOM, $lastExportOption );
			if ( false !== $date ) {
				$date->setTimezone( wp_timezone() );
				$lastExport = $date->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
			}
		}
		if ( $lastExport ) {
			$latteParams['lastExport'] = $lastExport;
		}

		$this->latte_engine->render( PACKETERY_PLUGIN_DIR . '/template/options/page.latte', $latteParams );
	}
}
