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
use Packetery\Core\Validator;
use Packetery\Module;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Exception\InvalidCarrierException;
use Packetery\Module\FormFactory;
use Packetery\Module\FormValidators;
use Packetery\Module\Log;
use Packetery\Module\MessageManager;
use Packetery\Module\Options;
use Packetery\Module\Plugin;
use Packetery\Module\WidgetOptionsBuilder;
use Packetery\Latte\Engine;
use Packetery\Nette\Forms\Form;
use Packetery\Nette\Http\Request;
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
	 * HTTP request.
	 *
	 * @var Request
	 */
	private $request;

	/**
	 * Order form.
	 *
	 * @var Form
	 */
	private $order_form;

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
	 * Repository.
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
	 * Carrier repository.
	 *
	 * @var EntityRepository
	 */
	private $carrierRepository;

	/**
	 * Order validator.
	 *
	 * @var Validator\Order
	 */
	private $orderValidator;

	/**
	 * Order detail common logic.
	 *
	 * @var DetailCommonLogic
	 */
	private $detailCommonLogic;

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
	 * @param EntityRepository     $carrierRepository    Carrier repository.
	 * @param Validator\Order      $orderValidator       Order validator.
	 * @param DetailCommonLogic    $detailCommonLogic    Detail common logic.
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
		WidgetOptionsBuilder $widgetOptionsBuilder,
		EntityRepository $carrierRepository,
		Validator\Order $orderValidator,
		DetailCommonLogic $detailCommonLogic
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
		$this->carrierRepository    = $carrierRepository;
		$this->orderValidator       = $orderValidator;
		$this->detailCommonLogic    = $detailCommonLogic;
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
	}

	/**
	 *  Add metaboxes
	 */
	public function add_meta_boxes(): void {
		$orderId = $this->detailCommonLogic->getOrderId();
		if ( null === $orderId ) {
			return;
		}

		try {
			$order = $this->orderRepository->getById( $orderId );
			if ( null === $order ) {
				return;
			}
			// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		} catch ( InvalidCarrierException $exception ) {
			// Let's display message in the box.
		}

		add_meta_box(
			'packetery_metabox',
			__( 'Packeta', 'packeta' ),
			array(
				$this,
				'render_metabox',
			),
			Module\Helper::isHposEnabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order',
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
		$orderId = $this->detailCommonLogic->getOrderId();
		if ( null === $orderId ) {
			return;
		}

		$wcOrder = $this->orderRepository->getWcOrderById( $orderId );
		if ( null === $wcOrder ) {
			return;
		}

		try {
			$order = $this->orderRepository->getByWcOrder( $wcOrder );
		} catch ( InvalidCarrierException $exception ) {
			$this->latte_engine->render(
				PACKETERY_PLUGIN_DIR . '/template/order/metabox-form-error.latte',
				[
					'errorMessage' => $exception->getMessage(),
				]
			);

			return;
		}
		if ( null === $order ) {
			return;
		}
		$packetId = $order->getPacketId();

		$showLogsLink = null;
		if ( $this->logPage->hasAnyRows( (int) $order->getNumber() ) ) {
			$showLogsLink = $this->logPage->createLogListUrl( (int) $order->getNumber() );
		}

		$packetClaimUrl = null;
		if ( $order->isPacketClaimCreationPossible() ) {
			$packetClaimUrl = $this->getOrderActionLink( $order, PacketActionsCommonLogic::ACTION_SUBMIT_PACKET_CLAIM );
		}

		$packetClaimCancelUrl   = null;
		$packetClaimTrackingUrl = null;
		if ( $order->getPacketClaimId() ) {
			$packetClaimCancelUrl   = $this->getOrderActionLink(
				$order,
				PacketActionsCommonLogic::ACTION_CANCEL_PACKET,
				[
					PacketActionsCommonLogic::PARAM_PACKET_ID => $order->getPacketClaimId(),
				]
			);
			$packetClaimTrackingUrl = $this->helper->get_tracking_url( $order->getPacketClaimId() );
		}

		if ( $packetId ) {
			$packetCancelLink = $this->getOrderActionLink(
				$order,
				PacketActionsCommonLogic::ACTION_CANCEL_PACKET,
				[
					PacketActionsCommonLogic::PARAM_PACKET_ID => $packetId,
				]
			);
			$this->latte_engine->render(
				PACKETERY_PLUGIN_DIR . '/template/order/metabox-common.latte',
				[
					'order'                  => $order,
					'showSubmitPacketButton' => false,
					'packetCancelLink'       => $packetCancelLink,
					'packetTrackingUrl'      => $this->helper->get_tracking_url( $packetId ),
					'packetClaimTrackingUrl' => $packetClaimTrackingUrl,
					'showLogsLink'           => $showLogsLink,
					'packetClaimUrl'         => $packetClaimUrl,
					'packetClaimCancelUrl'   => $packetClaimCancelUrl,
					'translations'           => [
						'packetTrackingOnline'      => __( 'Packet tracking online', 'packeta' ),
						'packetClaimTrackingOnline' => __( 'Packet claim tracking', 'packeta' ),
						'showLogs'                  => __( 'Show logs', 'packeta' ),
						// translators: %s: Order number.
						'reallyCancelPacketHeading' => sprintf( __( 'Order #%s', 'packeta' ), $order->getCustomNumber() ),
						// translators: %s: Packet number.
						'reallyCancelPacket'        => sprintf( __( 'Do you really wish to cancel parcel number %s?', 'packeta' ), $packetId ),
						// translators: %s: Packet claim number.
						'reallyCancelPacketClaim'   => sprintf( __( 'Do you really wish to cancel packet claim number %s?', 'packeta' ), $order->getPacketClaimId() ),

						'cancelPacket'              => __( 'Cancel packet', 'packeta' ),
						'createPacketClaim'         => __( 'Create packet claim', 'packeta' ),
						'printPacketLabel'          => __( 'Print packet label', 'packeta' ),
						'printPacketClaimLabel'     => __( 'Print packet claim label', 'packeta' ),
						'cancelPacketClaim'         => __( 'Cancel packet claim', 'packeta' ),
						'packetClaimPassword'       => __( 'Packet claim password', 'packeta' ),
						'submissionPassword'        => __( 'submission password', 'packeta' ),
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

		$showSubmitPacketButton = $this->orderValidator->isValid( $order );
		$packetSubmitUrl        = $this->getOrderActionLink( $order, PacketActionsCommonLogic::ACTION_SUBMIT_PACKET );

		$showWidgetButton  = $order->isPickupPointDelivery();
		$widgetButtonError = null;
		$shippingCountry   = $order->getShippingCountry();
		$showHdWidget      = $order->isHomeDelivery() && in_array( $shippingCountry, Entity\Carrier::ADDRESS_VALIDATION_COUNTRIES, true );
		if (
			null === $shippingCountry ||
			! $this->carrierRepository->isValidForCountry( $order->getCarrier()->getId(), $shippingCountry )
		) {
			if ( $order->isPickupPointDelivery() ) {
				$showWidgetButton = false;
				if ( empty( $shippingCountry ) ) {
					$widgetButtonError = __(
						'The pickup point cannot be changed because the shipping address has no country set. First, change the country of delivery in the shipping address.',
						'packeta'
					);
				} else {
					$widgetButtonError = sprintf(
					// translators: %s is country code.
						__(
							'The pickup point cannot be changed because the selected carrier does not deliver to country "%s". First, change the country of delivery in the shipping address.',
							'packeta'
						),
						$shippingCountry
					);
				}
			} elseif ( in_array( $order->getCarrier()->getCountry(), Entity\Carrier::ADDRESS_VALIDATION_COUNTRIES, true ) ) {
				$showHdWidget = false;
				if ( empty( $shippingCountry ) ) {
					$widgetButtonError = __(
						'The address cannot be validated because the shipping address has no country set. First, change the country of delivery in the shipping address.',
						'packeta'
					);
				} else {
					$widgetButtonError = sprintf(
					// translators: %s is country code.
						__(
							'The address cannot be validated because the selected carrier does not deliver to country "%s". First, change the country of delivery in the shipping address.',
							'packeta'
						),
						$shippingCountry
					);
				}
			}
		}

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/metabox-form.latte',
			[
				'form'                   => $this->order_form,
				'order'                  => $order,
				'showWidgetButton'       => $showWidgetButton,
				'widgetButtonError'      => $widgetButtonError,
				'showHdWidget'           => $showHdWidget,
				'showSubmitPacketButton' => $showSubmitPacketButton,
				'packetCancelLink'       => null,
				'packetTrackingUrl'      => null,
				'packetSubmitUrl'        => $packetSubmitUrl,
				'packetClaimTrackingUrl' => $packetClaimTrackingUrl,
				'packetClaimUrl'         => $packetClaimUrl,
				'packetClaimCancelUrl'   => $packetClaimCancelUrl,
				'orderCurrency'          => get_woocommerce_currency_symbol( $order->getCurrency() ),
				'isCodPayment'           => $wcOrder->get_payment_method() === $this->optionsProvider->getCodPaymentMethod(),
				'logo'                   => plugin_dir_url( PACKETERY_PLUGIN_DIR . '/packeta.php' ) . 'public/packeta-symbol.png',
				'showLogsLink'           => $showLogsLink,
				'hasOrderManualWeight'   => $order->hasManualWeight(),
				'isPacketaPickupPoint'   => $order->isPacketaInternalPickupPoint(),
				'translations'           => [
					'showLogs'                  => __( 'Show logs', 'packeta' ),
					'weightIsManual'            => __( 'Weight is manually set. To calculate weight remove field content and save.', 'packeta' ),
					'submitPacket'              => __( 'Submit to packeta', 'packeta' ),
					'packetClaimTrackingOnline' => __( 'Packet claim tracking', 'packeta' ),
					'printPacketClaimLabel'     => __( 'Print packet claim label', 'packeta' ),
					'cancelPacketClaim'         => __( 'Cancel packet claim', 'packeta' ),
					'packetClaimPassword'       => __( 'Packet claim password', 'packeta' ),
					'submissionPassword'        => __( 'submission password', 'packeta' ),
					// translators: %s: Order number.
					'reallyCancelPacketHeading' => sprintf( __( 'Order #%s', 'packeta' ), $order->getCustomNumber() ),
					// translators: %s: Packet claim number.
					'reallyCancelPacketClaim'   => sprintf( __( 'Do you really wish to cancel packet claim number %s?', 'packeta' ), $order->getPacketClaimId() ),
				],
			]
		);
	}

	/**
	 * Saves added packetery form fields to order metas.
	 *
	 * @param Entity\Order $order Order.
	 *
	 * @return void
	 * @throws WC_Data_Exception When invalid data are passed during shipping address update.
	 */
	public function saveFields( Entity\Order $order ): void {
		$orderId = (int) $order->getNumber();
		if (
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			null === $this->request->getPost( 'packetery_order_metabox_nonce' )
		) {
			return;
		}

		if ( false === $this->order_form->isValid() ) {
			set_transient( 'packetery_metabox_nette_form_prev_invalid_values', $this->order_form->getValues( true ) );
			$this->message_manager->flash_message( __( 'Packeta: entered data is not valid!', 'packeta' ), MessageManager::TYPE_ERROR );

			return;
		}

		$orderFormValues = $this->order_form->getValues( 'array' );

		if ( ! wp_verify_nonce( $orderFormValues['packetery_order_metabox_nonce'] ) ) {
			$this->message_manager->flash_message( __( 'Session has expired! Please try again.', 'packeta' ), MessageManager::TYPE_ERROR );

			return;
		}

		if ( ! current_user_can( 'edit_post', $orderId ) ) {
			$this->message_manager->flash_message( __( 'You do not have sufficient rights to make changes!', 'packeta' ), MessageManager::TYPE_ERROR );

			return;
		}

		$propsToSave = [
			self::FIELD_WIDTH  => ( is_numeric( $orderFormValues[ self::FIELD_WIDTH ] ) ? (float) number_format( $orderFormValues[ self::FIELD_WIDTH ], 0, '.', '' ) : null ),
			self::FIELD_LENGTH => ( is_numeric( $orderFormValues[ self::FIELD_LENGTH ] ) ? (float) number_format( $orderFormValues[ self::FIELD_LENGTH ], 0, '.', '' ) : null ),
			self::FIELD_HEIGHT => ( is_numeric( $orderFormValues[ self::FIELD_HEIGHT ] ) ? (float) number_format( $orderFormValues[ self::FIELD_HEIGHT ], 0, '.', '' ) : null ),
		];

		if ( ! is_numeric( $orderFormValues[ self::FIELD_WEIGHT ] ) ) {
			$propsToSave[ self::FIELD_WEIGHT ] = null;
		} elseif ( (float) $orderFormValues[ self::FIELD_WEIGHT ] !== (float) $orderFormValues[ self::FIELD_ORIGINAL_WEIGHT ] ) {
			$propsToSave[ self::FIELD_WEIGHT ] = (float) $orderFormValues[ self::FIELD_WEIGHT ];
		}

		if ( $orderFormValues[ Attribute::POINT_ID ] && $order->isPickupPointDelivery() ) {
			/**
			 * Cannot be null due to the condition at the beginning of the method.
			 *
			 * @var WC_Order $wcOrder
			 */
			$wcOrder = $this->orderRepository->getWcOrderById( (int) $orderId );
			foreach ( Attribute::$pickupPointAttrs as $pickupPointAttr ) {
				$pickupPointValue = $orderFormValues[ $pickupPointAttr['name'] ];

				if ( Attribute::CARRIER_ID === $pickupPointAttr['name'] ) {
					if ( ! empty( $orderFormValues[ Attribute::CARRIER_ID ] ) ) {
						$pickupPointValue = $orderFormValues[ Attribute::CARRIER_ID ];
					} else {
						$pickupPointValue = $order->getCarrier()->getId();
					}
				}

				$propsToSave[ $pickupPointAttr['name'] ] = $pickupPointValue;

				if ( $this->optionsProvider->replaceShippingAddressWithPickupPointAddress() ) {
					$this->mapper->toWcOrderShippingAddress( $wcOrder, $pickupPointAttr['name'], (string) $pickupPointValue );
				}
			}
			$wcOrder->save();
		}

		if ( '1' === $orderFormValues[ Attribute::ADDRESS_IS_VALIDATED ] && $order->isHomeDelivery() ) {
			$address = $this->mapper->toValidatedAddress( $orderFormValues );
			$order->setDeliveryAddress( $address );
			$order->setAddressValidated( true );
		}

		$order->setAdultContent( $orderFormValues[ self::FIELD_ADULT_CONTENT ] );
		$order->setCod( is_numeric( $orderFormValues[ self::FIELD_COD ] ) ? Helper::simplifyFloat( $orderFormValues[ self::FIELD_COD ], 10 ) : null );
		$order->setValue( is_numeric( $orderFormValues[ self::FIELD_VALUE ] ) ? Helper::simplifyFloat( $orderFormValues[ self::FIELD_VALUE ], 10 ) : null );
		$order->setDeliverOn( $this->helper->getDateTimeFromString( $orderFormValues[ self::FIELD_DELIVER_ON ] ) );

		$orderSize = $this->mapper->toOrderSize( $order, $propsToSave );
		$order->setSize( $orderSize );

		$pickupPoint = $this->mapper->toOrderEntityPickupPoint( $order, $propsToSave );
		$order->setPickupPoint( $pickupPoint );

		$this->orderRepository->save( $order );
	}

	/**
	 * Creates pickup point picker settings.
	 *
	 * @return array|null
	 */
	public function getPickupPointWidgetSettings(): ?array {
		$order = $this->detailCommonLogic->getOrder();
		if ( null === $order || false === $order->isPickupPointDelivery() || null === $order->getShippingCountry() ) {
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
		$order = $this->detailCommonLogic->getOrder();
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
	 * @param array        $extraParams Extra params.
	 *
	 * @return string
	 */
	private function getOrderActionLink( Entity\Order $order, string $action, array $extraParams = [] ): string {
		$baseParams = [
			PacketActionsCommonLogic::PARAM_ORDER_ID    => $order->getNumber(),
			PacketActionsCommonLogic::PARAM_REDIRECT_TO => PacketActionsCommonLogic::REDIRECT_TO_ORDER_DETAIL,
			Plugin::PARAM_PACKETERY_ACTION              => $action,
			Plugin::PARAM_NONCE                         => wp_create_nonce( PacketActionsCommonLogic::createNonceAction( $action, $order->getNumber() ) ),
		];

		return add_query_arg(
			array_merge( $baseParams, $extraParams ),
			admin_url( 'admin.php' )
		);
	}

}
