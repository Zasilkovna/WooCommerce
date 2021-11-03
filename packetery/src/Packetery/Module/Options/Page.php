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
use PacketeryNette\Http;

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
	 * Http request.
	 *
	 * @var Http\Request
	 */
	private $httpRequest;

	/**
	 * Plugin constructor.
	 *
	 * @param Engine       $latte_engine PacketeryLatte_engine.
	 * @param Provider     $optionsProvider Options provider.
	 * @param FormFactory  $formFactory Form factory.
	 * @param Http\Request $httpRequest Http request.
	 */
	public function __construct( Engine $latte_engine, Provider $optionsProvider, FormFactory $formFactory, Http\Request $httpRequest ) {
		$this->latte_engine    = $latte_engine;
		$this->optionsProvider = $optionsProvider;
		$this->formFactory     = $formFactory;
		$this->httpRequest     = $httpRequest;
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

		$gateways        = WC()->payment_gateways->get_available_payment_gateways();
		$enabledGateways = [];
		foreach ( $gateways as $gateway ) {
			$enabledGateways[ $gateway->id ] = $gateway->title;
		}
		$container->addSelect(
			'cod_payment_method',
			__( 'Payment method that represents cash on delivery', 'packetery' ),
			$enabledGateways
		)->setPrompt( '--' )->checkDefaultValue( false );

		if ( $this->optionsProvider->has_any() ) {
			$container->setDefaults( $this->optionsProvider->data_to_array() );
		}

		return $form;
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
		$latteParams['senderLink']      = trim( $this->latte_engine->renderToString( PACKETERY_PLUGIN_DIR . '/template/options/help-block-link.latte', [ 'href' => 'https://client.packeta.com/senders' ] ) );
		$latteParams['exportLink']      = $this->httpRequest->getUrl()->withQueryParameter( 'action', 'export-settings' )->getRelativeUrl();

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
