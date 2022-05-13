<?php
/**
 * Class Page
 *
 * @package Packetery\Options
 */

declare( strict_types=1 );

namespace Packetery\Module\Options;

use Packetery\Core\Api\Soap\Request\SenderGetReturnRouting;
use Packetery\Core\Log;
use Packetery\Module\FormFactory;
use Packetery\Module\MessageManager;
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

	public const ACTION_VALIDATE_SENDER = 'validate-sender';

	public const SLUG = 'packeta-options';

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
	 * Packeta client.
	 *
	 * @var \Packetery\Core\Api\Soap\Client
	 */
	private $packetaClient;

	/**
	 * Logger
	 *
	 * @var Log\ILogger
	 */
	private $logger;

	/**
	 * Message manager.
	 *
	 * @var MessageManager
	 */
	private $messageManager;

	/**
	 * HTTP request.
	 *
	 * @var \PacketeryNette\Http\Request
	 */
	private $httpRequest;

	/**
	 * Plugin constructor.
	 *
	 * @param Engine                          $latte_engine    PacketeryLatte_engine.
	 * @param Provider                        $optionsProvider Options provider.
	 * @param FormFactory                     $formFactory     Form factory.
	 * @param \Packetery\Core\Api\Soap\Client $packetaClient   Packeta Client.
	 * @param Log\ILogger                     $logger          Logger.
	 * @param MessageManager                  $messageManager  Message manager.
	 * @param \PacketeryNette\Http\Request    $httpRequest     HTTP request.
	 */
	public function __construct( Engine $latte_engine, Provider $optionsProvider, FormFactory $formFactory, \Packetery\Core\Api\Soap\Client $packetaClient, Log\ILogger $logger, MessageManager $messageManager, \PacketeryNette\Http\Request $httpRequest ) {
		$this->latte_engine    = $latte_engine;
		$this->optionsProvider = $optionsProvider;
		$this->formFactory     = $formFactory;
		$this->packetaClient   = $packetaClient;
		$this->logger          = $logger;
		$this->messageManager  = $messageManager;
		$this->httpRequest     = $httpRequest;
	}

	/**
	 * Registers WP callbacks.
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		$icon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDI1LjEuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IlZyc3R2YV8xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4PSIwcHgiIHk9IjBweCIKCSB2aWV3Qm94PSIwIDAgMzcgNDAiIHN0eWxlPSJmaWxsOiNhN2FhYWQiIHhtbDpzcGFjZT0icHJlc2VydmUiPgo8c3R5bGUgdHlwZT0idGV4dC9jc3MiPgoJLnN0MHtmaWxsLXJ1bGU6ZXZlbm9kZDtjbGlwLXJ1bGU6ZXZlbm9kZDtmaWxsOiAjYTdhYWFkO30KPC9zdHlsZT4KPHBhdGggY2xhc3M9InN0MCIgZD0iTTE5LjQsMTYuMWwtMC45LDAuNGwtMC45LTAuNGwtMTMtNi41bDYuMi0yLjRsMTMuNCw2LjVMMTkuNCwxNi4xeiBNMzIuNSw5LjZsLTQuNywyLjNsLTEzLjUtNmw0LjItMS42CglMMzIuNSw5LjZ6Ii8+CjxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0xOSwwbDE3LjIsNi42bC0yLjQsMS45bC0xNS4yLTZMMy4yLDguNkwwLjgsNi42TDE4LDBMMTksMEwxOSwweiBNMzQuMSw5LjFsMi44LTEuMWwtMi4xLDE3LjZsLTAuNCwwLjgKCUwxOS40LDQwbC0wLjUtMy4xbDEzLjQtMTJMMzQuMSw5LjF6IE0yLjUsMjYuNWwtMC40LTAuOEwwLDguMWwyLjgsMS4xbDEuOSwxNS43bDEzLjQsMTJMMTcuNiw0MEwyLjUsMjYuNXoiLz4KPHBhdGggY2xhc3M9InN0MCIgZD0iTTI4LjIsMTIuNGw0LjMtMi43bC0xLjcsMTQuMkwxOC42LDM1bDAuNi0xN2w1LjQtMy4zTDI0LjMsMjNsMy4zLTIuM0wyOC4yLDEyLjR6Ii8+CjxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0xNy43LDE3LjlsMC42LDE3bC0xMi4yLTExTDQuNCw5LjhMMTcuNywxNy45eiIvPgo8L3N2Zz4K';
		add_menu_page(
			__( 'Packeta', PACKETERY_LANG_DOMAIN ),
			__( 'Packeta', PACKETERY_LANG_DOMAIN ),
			'manage_options',
			self::SLUG,
			'',
			$icon,
			55 // todo Move item to last position in menu.
		);
		add_submenu_page(
			self::SLUG,
			__( 'Settings', PACKETERY_LANG_DOMAIN ),
			__( 'Settings', PACKETERY_LANG_DOMAIN ),
			'manage_options',
			self::SLUG,
			array(
				$this,
				'render',
			),
			1
		);
		add_action( 'admin_init', [ $this, 'processActions' ] );
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
		$container->addText( 'api_password', __( 'API password', PACKETERY_LANG_DOMAIN ) )
					->setRequired()
					->addRule( $form::PATTERN, __( 'API password must be 32 characters long and must contain valid characters!', PACKETERY_LANG_DOMAIN ), '[a-z\d]{32}' );
		$container->addText( 'sender', __( 'Sender', PACKETERY_LANG_DOMAIN ) )
					->setRequired();

		$packetaLabelFormats = $this->optionsProvider->getPacketaLabelFormats();
		$container->addSelect(
			self::FORM_FIELD_PACKETA_LABEL_FORMAT,
			__( 'Packeta Label Format', PACKETERY_LANG_DOMAIN ),
			$packetaLabelFormats
		)->checkDefaultValue( false )->setDefaultValue( self::DEFAULT_VALUE_PACKETA_LABEL_FORMAT );

		$carrierLabelFormats = $this->optionsProvider->getCarrierLabelFormat();
		$container->addSelect(
			self::FORM_FIELD_CARRIER_LABEL_FORMAT,
			__( 'Carrier Label Format', PACKETERY_LANG_DOMAIN ),
			$carrierLabelFormats
		)->checkDefaultValue( false )->setDefaultValue( self::DEFAULT_VALUE_CARRIER_LABEL_FORMAT );

		$gateways        = $this->getAvailablePaymentGateways();
		$enabledGateways = [];
		foreach ( $gateways as $gateway ) {
			$enabledGateways[ $gateway->id ] = $gateway->get_method_title();
		}
		$container->addSelect(
			'cod_payment_method',
			__( 'Payment method that represents cash on delivery', PACKETERY_LANG_DOMAIN ),
			$enabledGateways
		)->setPrompt( '--' )->checkDefaultValue( false );

		$container->addText( 'packaging_weight', __( 'Packaging weight', PACKETERY_LANG_DOMAIN ) . ' (kg)' )
					->setRequired( true )
					->addRule( Form::FLOAT )
					->addRule( Form::MIN, null, 0 )
					->setDefaultValue( 0 );

		// TODO: Packet status sync.

		$container->addCheckbox( 'replace_shipping_address_with_pickup_point_address', __( 'Replace shipping address with pickup point address', PACKETERY_LANG_DOMAIN ) )
			->setRequired( false );

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

		/**
		 * Applies woocommerce_available_payment_gateways filters.
		 *
		 * @since 1.0.5
		 */
		return array_filter( (array) apply_filters( 'woocommerce_available_payment_gateways', $availableGateways ), [ $this, 'filterValidGatewayClass' ] );
	}

	/**
	 * Callback for array filter. Returns true if gateway is of correct type.
	 *
	 * @param object $gateway Gateway to check.
	 * @return bool
	 */
	protected function filterValidGatewayClass( object $gateway ): bool {
		return ( $gateway instanceof \WC_Payment_Gateway );
	}

	/**
	 *  Admin_init callback.
	 */
	public function admin_init(): void {
		register_setting( self::FORM_FIELDS_CONTAINER, self::FORM_FIELDS_CONTAINER, array( $this, 'options_validate' ) );
		add_settings_section( 'packetery_main', __( 'Main Settings', PACKETERY_LANG_DOMAIN ), '', self::SLUG );
	}

	/**
	 * Validates options.
	 *
	 * @param array $options Packetery_options.
	 *
	 * @return array
	 */
	public function options_validate( array $options ): array {
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
			$this->packetaClient->setApiPassword( $api_pass );
		} else {
			$options['api_key'] = '';
		}

		$this->validateSender( $options['sender'] );

		return $options;
	}

	/**
	 * Validates sender.
	 *
	 * @param string $senderLabel Sender lbel.
	 *
	 * @return bool|null
	 */
	private function validateSender( string $senderLabel ): ?bool {
		$senderValidationRequest  = new SenderGetReturnRouting( $senderLabel );
		$senderValidationResponse = $this->packetaClient->senderGetReturnRouting( $senderValidationRequest );

		$senderValidationLog         = new Log\Record();
		$senderValidationLog->action = Log\Record::ACTION_SENDER_VALIDATION;

		$senderValidationLog->status = Log\Record::STATUS_SUCCESS;
		$senderValidationLog->title  = __( 'Sender validation success', PACKETERY_LANG_DOMAIN );

		if ( $senderValidationResponse->hasFault() ) {
			$senderValidationLog->status = Log\Record::STATUS_ERROR;
			$senderValidationLog->params = [
				'errorMessage' => $senderValidationResponse->getFaultString(),
			];
			$senderValidationLog->title  = __( 'Sender validation error', PACKETERY_LANG_DOMAIN );
		}

		$this->logger->add( $senderValidationLog );

		$senderExists = $senderValidationResponse->senderExists();

		if ( false === $senderExists ) {
			$this->messageManager->flash_message( __( 'Specified sender does not exist', PACKETERY_LANG_DOMAIN ), MessageManager::TYPE_INFO, MessageManager::RENDERER_PACKETERY, 'plugin-options' );
		}

		if ( null === $senderExists ) {
			$this->messageManager->flash_message( __( 'Unable to check specified sender', PACKETERY_LANG_DOMAIN ), MessageManager::TYPE_INFO, MessageManager::RENDERER_PACKETERY, 'plugin-options' );
		}

		return $senderExists;
	}

	/**
	 * Process actions.
	 *
	 * @return void
	 */
	public function processActions(): void {
		$action = $this->httpRequest->getQuery( 'action' );
		if ( self::ACTION_VALIDATE_SENDER === $action ) {
			$result = $this->validateSender( $this->optionsProvider->get_sender() );

			if ( true === $result ) {
				$this->messageManager->flash_message( __( 'Specified sender is OK', PACKETERY_LANG_DOMAIN ), MessageManager::TYPE_SUCCESS, MessageManager::RENDERER_PACKETERY, 'plugin-options' );
			}

			$doRedirect = wp_safe_redirect(
				add_query_arg(
					[
						'page' => self::SLUG,
					],
					get_admin_url( null, 'admin.php' )
				)
			);

			if ( $doRedirect ) {
				exit;
			}
		}
	}

	/**
	 *  Renders page.
	 */
	public function render(): void {
		$latteParams = [ 'form' => $this->create_form() ];
		if ( ! extension_loaded( 'soap' ) ) {
			$latteParams['error'] = __( 'This plugin requires an active SOAP library for proper operation. Contact your web hosting administrator.', PACKETERY_LANG_DOMAIN );
		}

		$latteParams['apiPasswordLink'] = trim( $this->latte_engine->renderToString( PACKETERY_PLUGIN_DIR . '/template/options/help-block-link.latte', [ 'href' => 'https://client.packeta.com/support' ] ) );

		$latteParams['senderDescription'] = sprintf(
			/* translators: 1: emphasis start 2: emphasis end 3: client section link start 4: client section link end */
			esc_html__( 'Fill here %1$ssender label%2$s - you will find it in %3$sclient section%4$s - user information - field \'Indication\'.', PACKETERY_LANG_DOMAIN ),
			'<strong>',
			'</strong>',
			'<a href="https://client.packeta.com/senders" target="_blank">',
			'</a>'
		);

		$latteParams['exportLink'] = add_query_arg(
			[
				'page'   => self::SLUG,
				'action' => Exporter::ACTION_EXPORT_SETTINGS,
			],
			get_admin_url( null, 'admin.php' )
		);

		$latteParams['canValidateSender']    = (bool) $this->optionsProvider->get_sender();
		$latteParams['senderValidationLink'] = add_query_arg(
			[
				'page'   => self::SLUG,
				'action' => self::ACTION_VALIDATE_SENDER,
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

		$latteParams['messages'] = $this->messageManager->renderToString( MessageManager::RENDERER_PACKETERY, 'plugin-options' );
		$latteParams['translations'] = [
			'packeta'                      => __( 'Packeta', PACKETERY_LANG_DOMAIN ),
			'options'                      => __( 'Options', PACKETERY_LANG_DOMAIN ),
			'general'                      => __( 'General', PACKETERY_LANG_DOMAIN ),
			'apiPasswordCanBeFoundAt%sUrl' => __( 'API password can be found at %s', PACKETERY_LANG_DOMAIN ),
			'saveChanges'                  => __( 'Save Changes', PACKETERY_LANG_DOMAIN ),
			'validateSender'               => __( 'Validate sender', PACKETERY_LANG_DOMAIN ),
			'support'                      => __( 'Support', PACKETERY_LANG_DOMAIN ),
			'optionsExportInfo1'           => __(
				'By clicking the button, you will export the settings of your plugin into a '
				. 'separate file. The export does not contain any sensitive information about '
				. 'your e-shop. Please send the resulting file to the technical support of '
				. 'Packeta (you can find the e-mail address here:',
				PACKETERY_LANG_DOMAIN ),
			'optionsExportInfo2'           => __(
				') along with the description of your problem. For a better understanding of '
				. 'your problem, we recommend adding screenshots, which show the problem (if '
				. 'possible).',
				PACKETERY_LANG_DOMAIN ),
			'exportPluginSettings'         => __( 'Export the plugin settings', PACKETERY_LANG_DOMAIN ),
			'settingsExportDatetime'       => __( 'Date and time of the last export of settings', PACKETERY_LANG_DOMAIN ),
			'settingsNotYetExported'       => __( 'The settings have not been exported yet.', PACKETERY_LANG_DOMAIN ),
		];

		$this->latte_engine->render( PACKETERY_PLUGIN_DIR . '/template/options/page.latte', $latteParams );
	}
}
