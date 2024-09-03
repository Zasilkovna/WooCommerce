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
use Packetery\Module\Order\PacketAutoSubmitter;
use Packetery\Module\Order\PacketSynchronizer;
use Packetery\Latte\Engine;
use Packetery\Module\PaymentGatewayHelper;
use Packetery\Nette\Forms\Container;
use Packetery\Nette\Forms\Form;
use Packetery\Core\CoreHelper;

/**
 * Class Page
 *
 * @package Packetery\Options
 */
class Page {

	private const FORM_FIELDS_CONTAINER           = 'packetery';
	private const FORM_FIELD_PACKETA_LABEL_FORMAT = 'packeta_label_format';
	private const FORM_FIELD_CARRIER_LABEL_FORMAT = 'carrier_label_format';
	private const FORM_FIELD_FREE_SHIPPING_SHOWN  = 'free_shipping_shown';

	public const ACTION_VALIDATE_SENDER = 'validate-sender';

	public const SLUG = 'packeta-options';

	public const TAB_GENERAL            = 'general';
	public const TAB_SUPPORT            = 'support';
	public const TAB_PACKET_STATUS_SYNC = 'packet-status-sync';
	public const TAB_AUTO_SUBMISSION    = 'auto-submission';

	public const PARAM_TAB = 'tab';

	/**
	 * PacketeryLatte_engine.
	 *
	 * @var Engine PacketeryLatte engine.
	 */
	private $latte_engine;

	/**
	 * Options Provider
	 *
	 * @var OptionsProvider
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
	 * @var \Packetery\Nette\Http\Request
	 */
	private $httpRequest;

	/**
	 * Plugin constructor.
	 *
	 * @param Engine                          $latte_engine       PacketeryLatte_engine.
	 * @param OptionsProvider                 $optionsProvider    Options provider.
	 * @param FormFactory                     $formFactory        Form factory.
	 * @param \Packetery\Core\Api\Soap\Client $packetaClient      Packeta Client.
	 * @param Log\ILogger                     $logger             Logger.
	 * @param MessageManager                  $messageManager     Message manager.
	 * @param \Packetery\Nette\Http\Request   $httpRequest        HTTP request.
	 */
	public function __construct(
		Engine $latte_engine,
		OptionsProvider $optionsProvider,
		FormFactory $formFactory,
		\Packetery\Core\Api\Soap\Client $packetaClient,
		Log\ILogger $logger,
		MessageManager $messageManager,
		\Packetery\Nette\Http\Request $httpRequest
	) {
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
			__( 'Packeta', 'packeta' ),
			__( 'Packeta', 'packeta' ),
			'manage_options',
			self::SLUG,
			'',
			$icon
		);
		add_submenu_page(
			self::SLUG,
			__( 'Settings', 'packeta' ),
			__( 'Settings', 'packeta' ),
			'manage_options',
			self::SLUG,
			array(
				$this,
				'render',
			),
			1
		);
		add_action( 'admin_init', [ $this, 'processActions' ] );

		add_filter( 'custom_menu_order', '__return_true' );
		add_filter( 'menu_order', [ $this, 'customMenuOrder' ] );
	}

	/**
	 * Returns menu_order with Packeta item in last position.
	 *
	 * @param array $menuOrder WP $menu_order.
	 *
	 * @return array
	 */
	public function customMenuOrder( array $menuOrder ): array {
		$currentPosition = array_search( self::SLUG, $menuOrder, true );

		if ( false !== $currentPosition ) {
			unset( $menuOrder[ $currentPosition ] );
			$menuOrder[] = self::SLUG;
		}

		return $menuOrder;
	}

	/**
	 * Get chosen keys by hashed choice data.
	 *
	 * @param array $choiceData Choice data.
	 * @param array $chosenHashedFormOrderStatuses Picked items.
	 *
	 * @return array
	 */
	private function getChosenKeys( array $choiceData, array $chosenHashedFormOrderStatuses ): array {
		$statuses = [];
		foreach ( $chosenHashedFormOrderStatuses as $hash => $chosen ) {
			if ( $chosen ) {
				$statuses[] = $choiceData[ $hash ]['key'];
			}
		}

		return $statuses;
	}

	/**
	 * Translate hashes to packet status keys.
	 *
	 * @param array $choiceData Choice data.
	 * @param array $formData   Picked items.
	 *
	 * @return array
	 */
	private function translateStatuses( array $choiceData, array $formData ): array {
		$statuses = [];
		foreach ( $formData as $hash => $value ) {
			if ( $value ) {
				$statuses[ $choiceData[ $hash ]['key'] ] = $value;
			}
		}

		return $statuses;
	}

	/**
	 * Gets all packet statuses.
	 *
	 * @return array
	 */
	public function getPacketStatusesChoiceData(): array {
		$statuses = PacketSynchronizer::getPacketStatuses();

		$result = [];

		foreach ( $statuses as $status => $statusEntity ) {
			$result[ md5( $status ) ] = [
				'key'     => $status,
				'default' => $statusEntity->hasDefaultSynchronization(),
				'label'   => $statusEntity->getTranslatedName(),
			];
		}

		return $result;
	}

	/**
	 * Gets order statuses for form.
	 *
	 * @return array
	 */
	public static function getOrderStatusesChoiceData(): array {
		$orderStatuses            = wc_get_order_statuses();
		$orderStatusesTransformed = [];

		foreach ( $orderStatuses as $orderStatusKey => $orderStatusLabel ) {
			$orderStatusesTransformed[ md5( $orderStatusKey ) ] = [
				'key'   => $orderStatusKey,
				'label' => $orderStatusLabel,
			];
		}

		return $orderStatusesTransformed;
	}

	/**
	 * Creates auto submission form.
	 *
	 * @return Form
	 */
	public function createAutoSubmissionForm(): Form {
		$gateways = PaymentGatewayHelper::getAvailablePaymentGateways();
		$form     = $this->formFactory->create( 'packetery_auto_submission_form' );
		$defaults = $this->optionsProvider->getOptionsByName( OptionsProvider::OPTION_NAME_PACKETERY_AUTO_SUBMISSION );

		$form->addCheckbox( 'allow', __( 'Allow packet auto-submission', 'packeta' ) )
				->setRequired( false )
				->setDefaultValue( OptionsProvider::PACKET_AUTO_SUBMISSION_ALLOWED_DEFAULT );

		$eventChoices = [
			PacketAutoSubmitter::EVENT_ON_ORDER_CREATION_FE => __( 'On order creation at e-shop checkout', 'packeta' ),
			PacketAutoSubmitter::EVENT_ON_ORDER_PROCESSING => __( 'On order processing', 'packeta' ),
			PacketAutoSubmitter::EVENT_ON_ORDER_COMPLETED  => __( 'On order completed', 'packeta' ),
		];

		$paymentMethodEvents = $form->addContainer( 'payment_method_events' );
		foreach ( $gateways as $gateway ) {
			$paymentMethodEventsMethod = $paymentMethodEvents->addContainer(
				$this->optionsProvider->sanitizePaymentGatewayId( $gateway->id )
			);
			$paymentMethodEventsMethod->addSelect( 'event', $gateway->get_method_title(), $eventChoices )
										->setPrompt( __( 'Select event', 'packeta' ) )
										->checkDefaultValue( false );
		}

		$form->addSubmit( 'save', __( 'Save changes', 'packeta' ) );

		$form->setDefaults( $defaults );

		$form->onSuccess[] = [ $this, 'onAutoSubmissionFormSuccess' ];

		return $form;
	}

	/**
	 * On auto submission form success.
	 *
	 * @param Form  $form Form.
	 * @param array $values Form values.
	 *
	 * @return void
	 */
	public function onAutoSubmissionFormSuccess( Form $form, array $values ): void {
		update_option( OptionsProvider::OPTION_NAME_PACKETERY_AUTO_SUBMISSION, $values );

		$this->messageManager->flash_message( __( 'Settings saved.', 'packeta' ), MessageManager::TYPE_SUCCESS, MessageManager::RENDERER_PACKETERY, 'plugin-options' );

		if ( wp_safe_redirect( $this->createLink( self::TAB_AUTO_SUBMISSION ) ) ) {
			exit;
		}
	}

	/**
	 * Creates settings form.
	 *
	 * @return Form
	 */
	private function createPacketStatusSyncForm(): Form {
		$form     = $this->formFactory->create( 'packetery_packet_status_sync_form' );
		$settings = $this->optionsProvider->getOptionsByName( OptionsProvider::OPTION_NAME_PACKETERY_SYNC );

		$form->addText( 'max_status_syncing_packets', __( 'Number of orders synced during one cron call', 'packeta' ) )
				->setRequired( false )
				->addRule( Form::INTEGER )
				->addRule( Form::MIN, null, 0 )
				->setDefaultValue( OptionsProvider::MAX_STATUS_SYNCING_PACKETS_DEFAULT );

		$form->addText( 'max_days_of_packet_status_syncing', __( 'Number of days for which the order status is checked', 'packeta' ) )
				->setRequired( false )
				->addRule( Form::INTEGER )
				->addRule( Form::MIN, null, 0 )
				->setDefaultValue( OptionsProvider::MAX_DAYS_OF_PACKET_STATUS_SYNCING_DEFAULT );

		$orderStatusesTransformed = self::getOrderStatusesChoiceData();
		$orderStatusesContainer   = $form->addContainer( 'status_syncing_order_statuses' );

		foreach ( $orderStatusesTransformed as $orderStatusKeyHash => $orderStatusData ) {
			$item = $orderStatusesContainer->addCheckbox( $orderStatusKeyHash, $orderStatusData['label'] );
			if ( isset( $settings['status_syncing_order_statuses'] ) && in_array( $orderStatusData['key'], $settings['status_syncing_order_statuses'], true ) ) {
				$item->setDefaultValue( true );
			}
		}
		unset( $settings['status_syncing_order_statuses'] );

		$packetStatuses          = $this->getPacketStatusesChoiceData();
		$packetStatusesContainer = $form->addContainer( 'status_syncing_packet_statuses' );

		foreach ( $packetStatuses as $packetStatusHash => $packetStatusData ) {
			$item = $packetStatusesContainer->addCheckbox( $packetStatusHash, $packetStatusData['label'] );
			if ( ! isset( $settings['status_syncing_packet_statuses'] ) ) {
				$item->setDefaultValue( $packetStatusData['default'] );
			} elseif ( in_array( $packetStatusData['key'], $settings['status_syncing_packet_statuses'], true ) ) {
				$item->setDefaultValue( true );
			}
		}
		unset( $settings['status_syncing_packet_statuses'] );

		$form->addCheckbox( 'allow_order_status_change', __( 'Allow order status change', 'packeta' ) )
			->setRequired( false )
			->setDefaultValue( false )
			->addCondition( Form::EQUAL, true )
			->toggle( '.order_status_change_content' );

		$orderStatusChangePacketStatuses = $form->addContainer( 'order_status_change_packet_statuses' );
		$orderStatuses                   = wc_get_order_statuses();
		foreach ( $packetStatuses as $packetStatusHash => $packetStatusData ) {
			$item         = $orderStatusChangePacketStatuses->addSelect( $packetStatusHash, $packetStatusData['label'], $orderStatuses )
													->setPrompt( __( 'Order status', 'packeta' ) );
			$targetStatus = $settings['order_status_change_packet_statuses'][ $packetStatusData['key'] ] ?? null;
			if ( null !== $targetStatus && array_key_exists( $targetStatus, $orderStatuses ) ) {
				$item->setDefaultValue( $targetStatus );
			}
		}
		unset( $settings['order_status_change_packet_statuses'] );

		$form->setDefaults( $settings );

		$form->addSubmit( 'save', __( 'Save changes', 'packeta' ) );

		$form->onSuccess[] = [ $this, 'onPacketStatusSyncFormSuccess' ];

		return $form;
	}

	/**
	 * On packet status sync form success.
	 *
	 * @param Form  $form Form.
	 * @param array $values Values.
	 *
	 * @return void
	 */
	public function onPacketStatusSyncFormSuccess( Form $form, array $values ): void {

		$values['status_syncing_order_statuses']       = $this->getChosenKeys(
			self::getOrderStatusesChoiceData(),
			$values['status_syncing_order_statuses']
		);
		$values['status_syncing_packet_statuses']      = $this->getChosenKeys(
			$this->getPacketStatusesChoiceData(),
			$values['status_syncing_packet_statuses']
		);
		$values['order_status_change_packet_statuses'] = $this->translateStatuses(
			$this->getPacketStatusesChoiceData(),
			$values['order_status_change_packet_statuses']
		);

		if ( '' === $values['max_status_syncing_packets'] ) {
			unset( $values['max_status_syncing_packets'] );
		}

		if ( '' === $values['max_days_of_packet_status_syncing'] ) {
			unset( $values['max_days_of_packet_status_syncing'] );
		}

		update_option( OptionsProvider::OPTION_NAME_PACKETERY_SYNC, $values );

		$this->messageManager->flash_message( __( 'Settings saved.', 'packeta' ), MessageManager::TYPE_SUCCESS, MessageManager::RENDERER_PACKETERY, 'plugin-options' );

		if ( wp_safe_redirect( $this->createLink( self::TAB_PACKET_STATUS_SYNC ) ) ) {
			exit;
		}
	}

	/**
	 * Creates settings form.
	 *
	 * @return Form
	 */
	public function create_form(): Form {
		$form = $this->formFactory->create();
		$form->setAction( 'options.php' );

		$container = $form->addContainer( self::FORM_FIELDS_CONTAINER );
		$container->addText( 'api_password', __( 'API password', 'packeta' ) )
					->setRequired()
					->addRule( $form::PATTERN, __( 'API password must be 32 characters long and must contain valid characters!', 'packeta' ), '[a-z\d]{32}' );
		$container->addText( 'sender', __( 'Sender', 'packeta' ) )
					->setRequired();

		$packetaLabelFormats = $this->optionsProvider->getPacketaLabelFormats();
		$container->addSelect(
			self::FORM_FIELD_PACKETA_LABEL_FORMAT,
			__( 'Packeta Label Format', 'packeta' ),
			$packetaLabelFormats
		)->checkDefaultValue( false )->setDefaultValue( OptionsProvider::DEFAULT_VALUE_PACKETA_LABEL_FORMAT );

		$carrierLabelFormats = $this->optionsProvider->getCarrierLabelFormat();
		$container->addSelect(
			self::FORM_FIELD_CARRIER_LABEL_FORMAT,
			__( 'Carrier Label Format', 'packeta' ),
			$carrierLabelFormats
		)->checkDefaultValue( false )->setDefaultValue( OptionsProvider::DEFAULT_VALUE_CARRIER_LABEL_FORMAT );

		$gateways        = PaymentGatewayHelper::getAvailablePaymentGateways();
		$enabledGateways = [];
		foreach ( $gateways as $gateway ) {
			$enabledGateways[ $gateway->id ] = $gateway->get_method_title();
		}
		$container->addMultiSelect(
			'cod_payment_methods',
			__( 'Payment methods that represent cash on delivery', 'packeta' ),
			$enabledGateways
		)->checkDefaultValue( false );

		$container->addText( 'packaging_weight', __( 'Weight of packaging material', 'packeta' ) . ' (kg)' )
					->setRequired( true )
					->addRule( Form::FLOAT )
					->addRule( Form::MIN, null, 0 )
					->setDefaultValue( 0 );

		$container->addCheckbox( 'default_weight_enabled', __( 'Enable default weight', 'packeta' ) )
					->addCondition( Form::EQUAL, true )
						->toggle( '#packetery-default-weight-value' );

		$container->addText( 'default_weight', __( 'Default weight', 'packeta' ) . ' (kg)' )
					->addRule( Form::FLOAT )
					->addRule( Form::MIN, null, 0.1 )
					->addConditionOn( $form[ self::FORM_FIELDS_CONTAINER ]['default_weight_enabled'], Form::EQUAL, true )
						->setRequired();
		$container->addCheckbox( 'default_dimensions_enabled', __( 'Enable default dimensions', 'packeta' ) )
			->addCondition( Form::EQUAL, true )
			->toggle( '#packetery-default-dimensions-value' );

		$container->addText( 'default_length', __( 'Length', 'packeta' ) . ' (mm)' )
			->addRule( Form::INTEGER )
			->addRule( Form::MIN, null, 0 )
			->addConditionOn( $form[ self::FORM_FIELDS_CONTAINER ]['default_dimensions_enabled'], Form::EQUAL, true )
			->setRequired();

		$container->addText( 'default_height', __( 'Height', 'packeta' ) . ' (mm)' )
			->addRule( Form::INTEGER )
			->addRule( Form::MIN, null, 0 )
			->addConditionOn( $form[ self::FORM_FIELDS_CONTAINER ]['default_dimensions_enabled'], Form::EQUAL, true )
			->setRequired();

		$container->addText( 'default_width', __( 'Width', 'packeta' ) . ' (mm)' )
			->addRule( Form::INTEGER )
			->addRule( Form::MIN, null, 0 )
			->addConditionOn( $form[ self::FORM_FIELDS_CONTAINER ]['default_dimensions_enabled'], Form::EQUAL, true )
			->setRequired();

		// TODO: Packet status sync.

		$container->addCheckbox( 'replace_shipping_address_with_pickup_point_address', __( 'Replace shipping address with pickup point address', 'packeta' ) )
					->setRequired( false );

		$container->addSelect(
			'checkout_detection',
			__( 'Force checkout type', 'packeta' ),
			[
				OptionsProvider::AUTOMATIC_CHECKOUT_DETECTION => __( 'Automatic detection', 'packeta' ),
				OptionsProvider::CLASSIC_CHECKOUT_DETECTION => __( 'Classic checkout', 'packeta' ),
				OptionsProvider::BLOCK_CHECKOUT_DETECTION => __( 'Block-based checkout', 'packeta' ),
			]
		);

		$container->addSelect(
			'checkout_widget_button_location',
			__( 'Widget button location in checkout', 'packeta' ),
			[
				'after_shipping_rate'     => __( 'After shipping rate', 'packeta' ),
				'after_transport_methods' => __( 'After transport methods', 'packeta' ),
			]
		);

		$container->addSelect(
			'email_hook',
			__( 'Hook used to view information in email', 'packeta' ),
			[
				'woocommerce_email_footer'             => 'woocommerce_email_footer',
				'woocommerce_email_before_order_table' => 'woocommerce_email_before_order_table',
				'woocommerce_email_after_order_table'  => 'woocommerce_email_after_order_table',
				'woocommerce_email_order_meta'         => 'woocommerce_email_order_meta',
			]
		);

		$container->addCheckbox( 'force_packet_cancel', __( 'Force order cancellation', 'packeta' ) )
					->setRequired( false )
					->setDefaultValue( OptionsProvider::FORCE_PACKET_CANCEL_DEFAULT );

		$container->addCheckbox( 'widget_auto_open', __( 'Automatically open widget when shipping was selected', 'packeta' ) )
					->setRequired( false )
					->setDefaultValue( OptionsProvider::WIDGET_AUTO_OPEN_DEFAULT );

		$container->addCheckbox( self::FORM_FIELD_FREE_SHIPPING_SHOWN, __( 'Display the FREE shipping text in checkout', 'packeta' ) )
			->setRequired( false )
			->setDefaultValue( OptionsProvider::DISPLAY_FREE_SHIPPING_IN_CHECKOUT_DEFAULT );

		$container->addCheckbox( 'prices_include_tax', __( 'Prices include tax', 'packeta' ) )
			->setRequired( false )
			->setDefaultValue( OptionsProvider::PRICES_INCLUDE_TAX_DEFAULT );

		$form->addSubmit( 'save', __( 'Save changes', 'packeta' ) );

		if ( $this->optionsProvider->has_any( OptionsProvider::OPTION_NAME_PACKETERY ) ) {
			$container->setDefaults( $this->optionsProvider->getOptionsByName( OptionsProvider::OPTION_NAME_PACKETERY ) );
		}

		return $form;
	}

	/**
	 *  Admin_init callback.
	 */
	public function admin_init(): void {
		add_filter( 'pre_update_option_packetery', [ $this, 'validatePacketeryOptions' ] );
		register_setting( self::FORM_FIELDS_CONTAINER, self::FORM_FIELDS_CONTAINER, [ $this, 'sanitizePacketeryOptions' ] );
		add_settings_section( 'packetery_main', __( 'Main Settings', 'packeta' ), '', self::SLUG );
	}

	/**
	 * Validates packetery options.
	 *
	 * @param array $options Options to be validated.
	 *
	 * @return array
	 */
	public function validatePacketeryOptions( array $options ): array {
		$form = $this->create_form();
		/**
		 * Packetery container.
		 *
		 * @var Container $packeteryContainer
		 */
		$packeteryContainer = $form[ self::FORM_FIELDS_CONTAINER ];
		$packeteryContainer->setValues( $options );
		if ( $form->isValid() === false ) {
			foreach ( $packeteryContainer->getControls() as $control ) {
				if ( $control->hasErrors() === false ) {
					continue;
				}

				add_settings_error( $control->getCaption(), esc_attr( $control->getName() ), $control->getError() );
			}
		}

		$apiPassword = $packeteryContainer['api_password'];
		if ( $apiPassword->hasErrors() === false ) {
			$this->packetaClient->setApiPassword( $apiPassword->getValue() );
		}

		$this->validateSender( $options['sender'] );

		return $options;
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $options Options to be sanitized.
	 *
	 * @return array
	 */
	public function sanitizePacketeryOptions( array $options ): array {
		$form = $this->create_form();
		/**
		 * Packetery container.
		 *
		 * @var Container $packeteryContainer
		 */
		$packeteryContainer = $form[ self::FORM_FIELDS_CONTAINER ];
		$packeteryContainer->setValues( $options );
		if ( $form->isValid() === false ) {
			foreach ( $packeteryContainer->getControls() as $control ) {
				if ( $control->hasErrors() === false ) {
					continue;
				}

				$options[ $control->getName() ] = '';
			}
		}

		$api_password = $packeteryContainer['api_password'];
		if ( $api_password->hasErrors() === false ) {
			$api_pass           = $api_password->getValue();
			$options['api_key'] = substr( $api_pass, 0, 16 );
			$this->packetaClient->setApiPassword( $api_pass );
		} else {
			$options['api_key'] = '';
		}

		$defaultWeight                  = $packeteryContainer['default_weight']->getValue();
		$options['default_weight']      = is_numeric( $defaultWeight ) ? CoreHelper::trimDecimalPlaces( (float) $defaultWeight, 3 ) : $defaultWeight;
		$options['force_packet_cancel'] = (int) $packeteryContainer['force_packet_cancel']->getValue();
		$options['free_shipping_shown'] = (int) $packeteryContainer['free_shipping_shown']->getValue();

		return $options;
	}

	/**
	 * Validates sender.
	 *
	 * @param string $senderLabel Sender label.
	 *
	 * @return bool|null
	 */
	private function validateSender( string $senderLabel ): ?bool {
		$senderValidationRequest  = new SenderGetReturnRouting( $senderLabel );
		$senderValidationResponse = $this->packetaClient->senderGetReturnRouting( $senderValidationRequest );

		$senderValidationLog         = new Log\Record();
		$senderValidationLog->action = Log\Record::ACTION_SENDER_VALIDATION;

		$senderValidationLog->status = Log\Record::STATUS_SUCCESS;
		$senderValidationLog->title  = __( 'Sender validation was successful', 'packeta' );

		if ( $senderValidationResponse->hasFault() ) {
			$senderValidationLog->status = Log\Record::STATUS_ERROR;
			$senderValidationLog->params = [
				'errorMessage' => $senderValidationResponse->getFaultString(),
			];
			$senderValidationLog->title  = __( 'Sender could not be validated', 'packeta' );
		}

		$this->logger->add( $senderValidationLog );

		$senderExists = $senderValidationResponse->senderExists();

		if ( false === $senderExists ) {
			$this->messageManager->flash_message( __( 'Specified sender does not exist', 'packeta' ), MessageManager::TYPE_INFO, MessageManager::RENDERER_PACKETERY, 'plugin-options' );
		}

		if ( null === $senderExists ) {
			$this->messageManager->flash_message( __( 'Unable to check specified sender', 'packeta' ), MessageManager::TYPE_INFO, MessageManager::RENDERER_PACKETERY, 'plugin-options' );
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
				$this->messageManager->flash_message( __( 'Specified sender has been validated.', 'packeta' ), MessageManager::TYPE_SUCCESS, MessageManager::RENDERER_PACKETERY, 'plugin-options' );
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

		$packetStatusSyncForm = $this->createPacketStatusSyncForm();
		if ( $packetStatusSyncForm['save']->isSubmittedBy() ) {
			$packetStatusSyncForm->fireEvents();
		}

		$autoSubmissionForm = $this->createAutoSubmissionForm();
		if ( $autoSubmissionForm['save']->isSubmittedBy() ) {
			$autoSubmissionForm->fireEvents();
		}
	}

	/**
	 *  Renders page.
	 */
	public function render(): void {
		$activeTab = ( $this->httpRequest->getQuery( self::PARAM_TAB ) ?? self::TAB_GENERAL );

		$latteParams = [];
		if ( self::TAB_PACKET_STATUS_SYNC === $activeTab ) {
			$latteParams = [ 'form' => $this->createPacketStatusSyncForm() ];
		} elseif ( self::TAB_AUTO_SUBMISSION === $activeTab ) {
			$latteParams = [ 'form' => $this->createAutoSubmissionForm() ];
		} elseif ( self::TAB_GENERAL === $activeTab ) {
			$latteParams = [ 'form' => $this->create_form() ];
		}

		if ( ! extension_loaded( 'soap' ) ) {
			$latteParams['error'] = __( 'This plugin requires an active SOAP library for proper operation. Contact your web hosting administrator.', 'packeta' );
		}

		$latteParams['apiPasswordLink'] = trim( $this->latte_engine->renderToString( PACKETERY_PLUGIN_DIR . '/template/options/help-block-link.latte', [ 'href' => 'https://client.packeta.com/support' ] ) );

		$latteParams['exportLink'] = add_query_arg(
			[
				'page'   => self::SLUG,
				'action' => Exporter::ACTION_EXPORT_SETTINGS,
			],
			get_admin_url( null, 'admin.php' )
		);

		$latteParams['activeTab']               = ( $this->httpRequest->getQuery( self::PARAM_TAB ) ?? self::TAB_GENERAL );
		$latteParams['generalTabLink']          = $this->createLink();
		$latteParams['supportTabLink']          = $this->createLink( self::TAB_SUPPORT );
		$latteParams['packetStatusSyncTabLink'] = $this->createLink( self::TAB_PACKET_STATUS_SYNC );
		$latteParams['autoSubmissionTabLink']   = $this->createLink( self::TAB_AUTO_SUBMISSION );

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

		$latteParams['forcePacketCancelDescription'] = __( 'Cancel the packet for an order even if the cancellation in the Packeta system will not be successful.', 'packeta' );
		$latteParams['messages']                     = $this->messageManager->renderToString( MessageManager::RENDERER_PACKETERY, 'plugin-options' );
		$latteParams['translations']                 = [
			'packeta'                                => __( 'Packeta', 'packeta' ),
			'title'                                  => __( 'Options', 'packeta' ),
			'general'                                => __( 'General', 'packeta' ),
			'packetAutoSubmission'                   => __( 'Packet auto-submission', 'packeta' ),
			'packetAutoSubmissionMappingDescription' => __( 'Choose events for payment methods that will trigger packet submission', 'packeta' ),
			// translators: %s represents URL.
			'apiPasswordCanBeFoundAt%sUrl'           => __( 'API password can be found at %s', 'packeta' ),

			'saveChanges'                            => __( 'Save changes', 'packeta' ),
			'validateSender'                         => __( 'Validate sender', 'packeta' ),
			'support'                                => __( 'Support', 'packeta' ),
			'optionsExportInfo1'                     => __(
				'By clicking the button, you will export the settings of your plugin into a separate file. The export does not contain any sensitive information about your e-shop. Please send the resulting file to the technical support of Packeta (you can find the e-mail address here:',
				'packeta'
			),
			'optionsExportInfo2'                     => __(
				') along with the description of your problem. For a better understanding of your problem, we recommend adding screenshots, which show the problem (if possible).',
				'packeta'
			),
			'exportPluginSettings'                   => __( 'Export the plugin settings', 'packeta' ),
			'settingsExportDatetime'                 => __( 'Date and time of the last export of settings', 'packeta' ),
			'settingsNotYetExported'                 => __( 'The settings have not been exported yet.', 'packeta' ),
			'senderDescription'                      => sprintf(
				/* translators: 1: emphasis start 2: emphasis end 3: client section link start 4: client section link end */
				esc_html__( 'Fill here %1$ssender label%2$s - you will find it in %3$sclient section%4$s - user information - field \'Indication\'.', 'packeta' ),
				'<strong>',
				'</strong>',
				'<a href="https://client.packeta.com/senders" target="_blank">',
				'</a>'
			),
			'packagingWeightDescription'             => __( 'This parameter is used to determine the weight of the packaging material. This value is automatically added to the total weight of each order that contains products with non-zero weight. This value is also taken into account when evaluating the weight rules in the cart.', 'packeta' ),
			'defaultWeightDescription'               => __( 'This value is automatically added to the total weight of each order that contains products with zero weight.', 'packeta' ),
			'defaultDimensionsDescription'           => __( 'These dimensions will be applied to the packet by default, if required by the carrier.', 'packeta' ),
			'setCheckoutDetectionDescription'        => __( 'If you have trouble displaying the widget button in the checkout, you can force what type of checkout you are using.', 'packeta' ),
			'packetStatusSyncTabLinkLabel'           => __( 'Packet status tracking', 'packeta' ),
			'statusSyncingOrderStatusesLabel'        => __( 'Order statuses, for which cron will check the packet status', 'packeta' ),
			'statusSyncingOrderStatusesDescription'  => __( 'Cron will automatically track all orders with these statuses and check if the shipment status has changed.', 'packeta' ),
			'statusSyncingPacketStatusesLabel'       => __( 'Packet statuses that are being checked', 'packeta' ),
			'statusSyncingPacketStatusesDescription' => __( 'If an order has a shipment with one of these selected statuses, the shipment status will be tracked.', 'packeta' ),
			'numberOfDaysToCheckDescription'         => __( 'Number of days after the creation of an order, during which the order status will be checked.', 'packeta' ),
			'widgetAutoOpenDescription'              => __( 'If this option is active, the widget for selecting pickup points will open automatically after selecting the shipping method at the checkout.', 'packeta' ),
			'autoOrderStatusChangeDescription'       => __( 'Change order status after data submission to Packeta.', 'packeta' ),
			'freeShippingTextDescription'            => __( 'If enabled, "FREE" will be displayed after the name of the shipping method, if free shipping is applied.', 'packeta' ),
			'orderStatusChangeSettings'              => __( 'Order status change settings', 'packeta' ),
			'dimensionsLabel'                        => __( 'Dimensions', 'packeta' ),
		];

		$this->latte_engine->render( PACKETERY_PLUGIN_DIR . '/template/options/page.latte', $latteParams );
	}

	/**
	 * Creates tab link.
	 *
	 * @param string|null $tab Tab ID.
	 * @return string
	 */
	public function createLink( ?string $tab = null ): string {
		$params = [
			'page' => self::SLUG,
		];

		if ( null !== $tab ) {
			$params[ self::PARAM_TAB ] = $tab;
		}

		return add_query_arg(
			$params,
			get_admin_url( null, 'admin.php' )
		);
	}
}
