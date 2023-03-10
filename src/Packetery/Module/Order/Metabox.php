<?php
/**
 * Class Metabox
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Entity;
use Packetery\Core\Helper;
use Packetery\Module\FormFactory;
use Packetery\Module\FormValidators;
use Packetery\Module\Log;
use Packetery\Module\MessageManager;
use Packetery\Module\Options;
use Packetery\Module\Plugin;
use Packetery\Module\WidgetOptionsBuilder;
use PacketeryLatte\Engine;
use PacketeryNette\Forms\Form;
use PacketeryNette\Http\Request;
use WC_Data_Exception;
use WC_Order;

/**
 * Class Metabox
 *
 * @package Packetery\Order
 */
class Metabox {

	public const FIELD_WEIGHT           = 'packetery_weight';
	private const FIELD_ORIGINAL_WEIGHT = 'packetery_original_weight';
	public const FIELD_WIDTH            = 'packetery_width';
	public const FIELD_LENGTH           = 'packetery_length';
	public const FIELD_HEIGHT           = 'packetery_height';
	public const FIELD_ADULT_CONTENT    = 'packetery_adult_content';
	public const FIELD_COD              = 'packetery_COD';
	public const FIELD_VALUE            = 'packetery_value';
	public const FIELD_DELIVER_ON       = 'packetery_deliver_on';

	/**
	 * PacketeryLatte engine.
	 *
	 * @var Engine
	 */
	private $latte_engine;

	/**
	 * Message manager.
	 *
	 * @var MessageManager
	 */
	private $message_manager;

	/**
	 * Helper.
	 *
	 * @var Helper
	 */
	private $helper;

	/**
	 * Order form.
	 *
	 * @var Form
	 */
	private $order_form;

	/**
	 * HTTP request.
	 *
	 * @var Request
	 */
	private $request;

	/**
	 * Options provider.
	 *
	 * @var Options\Provider
	 */
	private $optionsProvider;

	/**
	 * Form factory.
	 *
	 * @var FormFactory
	 */
	private $formFactory;

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * Log page.
	 *
	 * @var Log\Page
	 */
	private $logPage;

	/**
	 * OrderFacade.
	 *
	 * @var AttributeMapper
	 */
	private $mapper;

	/**
	 * Widget options builder.
	 *
	 * @var WidgetOptionsBuilder
	 */
	private $widgetOptionsBuilder;

	/**
	 * Metabox constructor.
	 *
	 * @param Engine               $latte_engine         PacketeryLatte engine.
	 * @param MessageManager       $message_manager      Message manager.
	 * @param Helper               $helper               Helper.
	 * @param Request              $request              Http request.
	 * @param Options\Provider     $optionsProvider      Options provider.
	 * @param FormFactory          $formFactory          Form factory.
	 * @param Repository           $orderRepository      Order repository.
	 * @param Log\Page             $logPage              Log page.
	 * @param AttributeMapper      $mapper               AttributeMapper.
	 * @param WidgetOptionsBuilder $widgetOptionsBuilder Widget options builder.
	 */
	public function __construct(
		Engine $latte_engine,
		MessageManager $message_manager,
		Helper $helper,
		Request $request,
		Options\Provider $optionsProvider,
		FormFactory $formFactory,
		Repository $orderRepository,
		Log\Page $logPage,
		AttributeMapper $mapper,
		WidgetOptionsBuilder $widgetOptionsBuilder
	) {
		$this->latte_engine         = $latte_engine;
		$this->message_manager      = $message_manager;
		$this->helper               = $helper;
		$this->request              = $request;
		$this->optionsProvider      = $optionsProvider;
		$this->formFactory          = $formFactory;
		$this->orderRepository      = $orderRepository;
		$this->logPage              = $logPage;
		$this->mapper               = $mapper;
		$this->widgetOptionsBuilder = $widgetOptionsBuilder;
	}

	/**
	 *  Registers related hooks.
	 */
	public function register(): void {
		add_action(
			'admin_init',
			function () {
				$this->order_form = $this->formFactory->create();
				$this->add_fields();
			}
		);
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_fields' ) );
	}

	/**
	 *  Add metaboxes
	 */
	public function add_meta_boxes(): void {
		global $post;

		$order = $this->orderRepository->getById( (int) $post->ID );
		if ( null === $order ) {
			return;
		}

		add_meta_box(
			'packetery_metabox',
			__( 'Packeta', 'packeta' ),
			array(
				$this,
				'render_metabox',
			),
			'shop_order',
			'side',
			'high'
		);
	}

	/**
	 *  Adds packetery fields to order form.
	 */
	public function add_fields(): void {
		$this->order_form->addHidden( 'packetery_order_metabox_nonce' );
		$this->order_form->addText( self::FIELD_WEIGHT, __( 'Weight (kg)', 'packeta' ) )
							->setRequired( false )
							->addRule( $this->order_form::FLOAT, __( 'Provide numeric value!', 'packeta' ) );
		$this->order_form->addHidden( self::FIELD_ORIGINAL_WEIGHT );
		$this->order_form->addText( self::FIELD_WIDTH, __( 'Width (mm)', 'packeta' ) )
							->setRequired( false )
							->addRule( $this->order_form::FLOAT, __( 'Provide numeric value!', 'packeta' ) );
		$this->order_form->addText( self::FIELD_LENGTH, __( 'Length (mm)', 'packeta' ) )
							->setRequired( false )
							->addRule( $this->order_form::FLOAT, __( 'Provide numeric value!', 'packeta' ) );
		$this->order_form->addText( self::FIELD_HEIGHT, __( 'Height (mm)', 'packeta' ) )
							->setRequired( false )
							->addRule( $this->order_form::FLOAT, __( 'Provide numeric value!', 'packeta' ) );
		$this->order_form->addCheckbox( self::FIELD_ADULT_CONTENT, __( 'Adult content', 'packeta' ) )
							->setRequired( false );
		$this->order_form->addText( self::FIELD_COD, __( 'Cash on delivery', 'packeta' ) )
							->setRequired( false )
							->addRule( $this->order_form::FLOAT );
		$this->order_form->addText( self::FIELD_VALUE, __( 'Order value', 'packeta' ) )
							->setRequired( false )
							->addRule( $this->order_form::FLOAT );
		$this->order_form->addText( self::FIELD_DELIVER_ON, __( 'Planned dispatch', 'packeta' ) )
							->setHtmlAttribute( 'autocomplete', 'off' )
							->setRequired( false )
							// translators: %s: Represents minimal date for delayed delivery.
							->addRule( [ FormValidators::class, 'dateIsLater' ], __( 'Date must be later than %s', 'packeta' ), wp_date( Helper::DATEPICKER_FORMAT ) );

		foreach ( Attribute::$pickupPointAttrs as $pickupPointAttr ) {
			$this->order_form->addHidden( $pickupPointAttr['name'] );
		}

		foreach ( Attribute::$homeDeliveryAttrs as $homeDeliveryAttr ) {
			$this->order_form->addHidden( $homeDeliveryAttr['name'] );
		}

		$this->order_form->addButton( 'packetery_pick_pickup_point', __( 'Choose pickup point', 'packeta' ) );
		$this->order_form->addButton( 'packetery_pick_address', __( 'Check shipping address', 'packeta' ) );
	}

	/**
	 *  Renders metabox
	 */
	public function render_metabox(): void {
		global $post;

		$wcOrder = $this->orderRepository->getWcOrderById( (int) $post->ID );
		if ( null === $wcOrder ) {
			return;
		}

		$order = $this->orderRepository->getByWcOrder( $wcOrder );
		if ( null === $order ) {
			return;
		}
		$packetId = $order->getPacketId();

		$showLogsLink = null;
		if ( $this->logPage->hasAnyRows( (int) $order->getNumber() ) ) {
			$showLogsLink = $this->logPage->createLogListUrl( (int) $order->getNumber() );
		}

		if ( $packetId ) {
			$packetCancelLink = $this->getOrderActionLink( $order, PacketActionsCommonLogic::ACTION_CANCEL_PACKET );
			$this->latte_engine->render(
				PACKETERY_PLUGIN_DIR . '/template/order/metabox-overview.latte',
				[
					'packetCancelLink'    => $packetCancelLink,
					'packet_id'           => $packetId,
					'packet_tracking_url' => $this->helper->get_tracking_url( $packetId ),
					'showLogsLink'        => $showLogsLink,
					'translations'        => [
						'packetTrackingOnline'      => __( 'Packet tracking online', 'packeta' ),
						'showLogs'                  => __( 'Show logs', 'packeta' ),
						// translators: %s: Order number.
						'reallyCancelPacketHeading' => sprintf( __( 'Order #%s', 'packeta' ), $order->getCustomNumber() ),
						// translators: %s: Packet number.
						'reallyCancelPacket'        => sprintf( __( 'Do you really wish to cancel parcel number %s?', 'packeta' ), $packetId ),
						'cancelPacket'              => __( 'Cancel packet', 'packeta' ),
					],
				]
			);

			return;
		}

		$this->order_form->setDefaults(
			[
				'packetery_order_metabox_nonce' => wp_create_nonce(),
				self::FIELD_WEIGHT              => $order->getFinalWeight(),
				self::FIELD_ORIGINAL_WEIGHT     => $order->getFinalWeight(),
				self::FIELD_WIDTH               => $order->getWidth(),
				self::FIELD_LENGTH              => $order->getLength(),
				self::FIELD_HEIGHT              => $order->getHeight(),
				self::FIELD_ADULT_CONTENT       => $order->containsAdultContent(),
				self::FIELD_COD                 => $order->getCod(),
				self::FIELD_VALUE               => $order->getValue(),
				self::FIELD_DELIVER_ON          => $this->helper->getStringFromDateTime( $order->getDeliverOn(), Helper::DATEPICKER_FORMAT ),
			]
		);

		$prev_invalid_values = get_transient( 'packetery_metabox_nette_form_prev_invalid_values' );
		if ( $prev_invalid_values ) {
			$this->order_form->setValues( $prev_invalid_values );
			$this->order_form->validate();
		}
		delete_transient( 'packetery_metabox_nette_form_prev_invalid_values' );

		$showSubmitPacketButton = null !== $order->getFinalWeight() && $order->getFinalWeight() > 0;
		$packetSubmitUrl        = $this->getOrderActionLink( $order, PacketActionsCommonLogic::ACTION_SUBMIT_PACKET );
		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/metabox-form.latte',
			[
				'form'                   => $this->order_form,
				'order'                  => $order,
				'showSubmitPacketButton' => $showSubmitPacketButton,
				'packetSubmitUrl'        => $packetSubmitUrl,
				'orderCurrency'          => get_woocommerce_currency_symbol( $order->getCurrency() ),
				'isCodPayment'           => $wcOrder->get_payment_method() === $this->optionsProvider->getCodPaymentMethod(),
				'logo'                   => plugin_dir_url( PACKETERY_PLUGIN_DIR . '/packeta.php' ) . 'public/packeta-symbol.png',
				'showLogsLink'           => $showLogsLink,
				'hasOrderManualWeight'   => $order->hasManualWeight(),
				'isPacketaPickupPoint'   => $order->isPacketaInternalPickupPoint(),
				'translations'           => [
					'showLogs'       => __( 'Show logs', 'packeta' ),
					'weightIsManual' => __( 'Weight is manually set. To calculate weight remove field content and save.', 'packeta' ),
					'submitPacket'   => __( 'Submit to packeta', 'packeta' ),
				],
			]
		);
	}

	/**
	 * Saves added packetery form fields to order metas.
	 *
	 * @param mixed $orderId Order id.
	 *
	 * @return mixed Order id.
	 * @throws WC_Data_Exception When invalid data are passed during shipping address update.
	 */
	public function save_fields( $orderId ) {
		$order = $this->orderRepository->getById( $orderId );
		if (
			null === $order ||
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			null === $this->request->getPost( 'packetery_order_metabox_nonce' )
		) {
			return $orderId;
		}

		if ( false === $this->order_form->isValid() ) {
			set_transient( 'packetery_metabox_nette_form_prev_invalid_values', $this->order_form->getValues( true ) );
			$this->message_manager->flash_message( __( 'Packeta: entered data is not valid!', 'packeta' ), MessageManager::TYPE_ERROR );

			return $orderId;
		}

		$values = $this->order_form->getValues( 'array' );

		if ( ! wp_verify_nonce( $values['packetery_order_metabox_nonce'] ) ) {
			$this->message_manager->flash_message( __( 'Session has expired! Please try again.', 'packeta' ), MessageManager::TYPE_ERROR );

			return $orderId;
		}

		if ( ! current_user_can( 'edit_post', $orderId ) ) {
			$this->message_manager->flash_message( __( 'You do not have sufficient rights to make changes!', 'packeta' ), MessageManager::TYPE_ERROR );

			return $orderId;
		}

		$propsToSave = [
			self::FIELD_WIDTH  => ( is_numeric( $values[ self::FIELD_WIDTH ] ) ? (float) number_format( $values[ self::FIELD_WIDTH ], 0, '.', '' ) : null ),
			self::FIELD_LENGTH => ( is_numeric( $values[ self::FIELD_LENGTH ] ) ? (float) number_format( $values[ self::FIELD_LENGTH ], 0, '.', '' ) : null ),
			self::FIELD_HEIGHT => ( is_numeric( $values[ self::FIELD_HEIGHT ] ) ? (float) number_format( $values[ self::FIELD_HEIGHT ], 0, '.', '' ) : null ),
		];

		if ( ! is_numeric( $values[ self::FIELD_WEIGHT ] ) ) {
			$propsToSave[ self::FIELD_WEIGHT ] = null;
		} elseif ( (float) $values[ self::FIELD_WEIGHT ] !== (float) $values[ self::FIELD_ORIGINAL_WEIGHT ] ) {
			$propsToSave[ self::FIELD_WEIGHT ] = (float) $values[ self::FIELD_WEIGHT ];
		}

		if ( $values[ Attribute::ATTR_POINT_ID ] && $order->isPickupPointDelivery() ) {
			/**
			 * Cannot be null due to the condition at the beginning of the method.
			 *
			 * @var WC_Order $wcOrder
			 */
			$wcOrder = $this->orderRepository->getWcOrderById( $orderId );
			foreach ( Attribute::$pickupPointAttrs as $pickupPointAttr ) {
				$value = $values[ $pickupPointAttr['name'] ];

				if ( Attribute::ATTR_CARRIER_ID === $pickupPointAttr['name'] ) {
					$value = ( ! empty( $values[ Attribute::ATTR_CARRIER_ID ] ) ? $values[ Attribute::ATTR_CARRIER_ID ] : $order->getCarrierId() );
				}

				$propsToSave[ $pickupPointAttr['name'] ] = $value;

				if ( $this->optionsProvider->replaceShippingAddressWithPickupPointAddress() ) {
					$this->mapper->toWcOrderShippingAddress( $wcOrder, $pickupPointAttr['name'], (string) $value );
				}
			}
			$wcOrder->save();
		}

		if ( '1' === $values[ Attribute::ATTR_ADDRESS_IS_VALIDATED ] && $order->isHomeDelivery() ) {
			$address = $this->mapper->toValidatedAddress( $values );
			$order->setDeliveryAddress( $address );
			$order->setAddressValidated( true );
		}

		$order->setAdultContent( $values[ self::FIELD_ADULT_CONTENT ] );
		$order->setCod( is_numeric( $values[ self::FIELD_COD ] ) ? Helper::simplifyFloat( $values[ self::FIELD_COD ], 10 ) : null );
		$order->setValue( is_numeric( $values[ self::FIELD_VALUE ] ) ? Helper::simplifyFloat( $values[ self::FIELD_VALUE ], 10 ) : null );
		$order->setDeliverOn( $this->helper->getDateTimeFromString( $values[ self::FIELD_DELIVER_ON ] ) );

		$orderSize = $this->mapper->toOrderSize( $order, $propsToSave );
		$order->setSize( $orderSize );

		$pickupPoint = $this->mapper->toOrderEntityPickupPoint( $order, $propsToSave );
		$order->setPickupPoint( $pickupPoint );

		$this->orderRepository->save( $order );

		return $orderId;
	}

	/**
	 * Creates pickup point picker settings.
	 *
	 * @return array|null
	 */
	public function getPickupPointWidgetSettings(): ?array {
		global $post;

		$order = $this->orderRepository->getById( (int) $post->ID );
		if ( null === $order || false === $order->isPickupPointDelivery() ) {
			return null;
		}

		$widgetOptions = $this->widgetOptionsBuilder->createPickupPointForAdmin( $order );

		return [
			'packeteryApiKey'  => $this->optionsProvider->get_api_key(),
			'pickupPointAttrs' => Attribute::$pickupPointAttrs,
			'widgetOptions'    => $widgetOptions,
		];
	}

	/**
	 * Creates address picker settings.
	 *
	 * @return array|null
	 */
	public function getAddressWidgetSettings(): ?array {
		global $post;

		$order = $this->orderRepository->getById( (int) $post->ID );
		if ( null === $order || false === $order->isHomeDelivery() ) {
			return null;
		}

		$widgetOptions = $this->widgetOptionsBuilder->createAddressForAdmin( $order );

		return [
			'packeteryApiKey'   => $this->optionsProvider->get_api_key(),
			'homeDeliveryAttrs' => Attribute::$homeDeliveryAttrs,
			'widgetOptions'     => $widgetOptions,
			'translations'      => [
				'addressValidationIsOutOfOrder' => __( 'Address validation is out of order.', 'packeta' ),
				'invalidAddressCountrySelected' => __( 'The selected country does not correspond to the destination country.', 'packeta' ),
			],
		];
	}

	/**
	 * Gets order action link.
	 *
	 * @param Entity\Order $order  Order.
	 * @param string       $action Action.
	 *
	 * @return string
	 */
	private function getOrderActionLink( Entity\Order $order, string $action ): string {
		return add_query_arg(
			[
				PacketActionsCommonLogic::PARAM_ORDER_ID => $order->getNumber(),
				PacketActionsCommonLogic::PARAM_REDIRECT_TO => PacketActionsCommonLogic::REDIRECT_TO_ORDER_DETAIL,
				Plugin::PARAM_PACKETERY_ACTION           => $action,
				Plugin::PARAM_NONCE                      => wp_create_nonce( PacketActionsCommonLogic::createNonceAction( $action, $order->getNumber() ) ),
			],
			admin_url( 'admin.php' )
		);
	}

}
