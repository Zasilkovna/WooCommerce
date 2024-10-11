<?php
/**
 * Class Metabox
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Entity;
use Packetery\Core\CoreHelper;
use Packetery\Core\Validator;
use Packetery\Core\Validator\Order;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Exception\InvalidCarrierException;
use Packetery\Module\Log;
use Packetery\Module\Log\Page;
use Packetery\Module\MessageManager;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Plugin;
use Packetery\Module\WidgetOptionsBuilder;
use Packetery\Latte\Engine;
use Packetery\Nette\Forms;
use Packetery\Nette\Http\Request;
use WC_Data_Exception;
use WC_Order;

/**
 * Class Metabox
 *
 * @package Packetery\Order
 */
class Metabox {
	private const PART_ERROR          = 'error';
	private const PART_CARRIER_CHANGE = 'carrierChange';
	private const PART_MAIN           = 'main';

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
	 * CoreHelper.
	 *
	 * @var CoreHelper
	 */
	private $coreHelper;

	/**
	 * HTTP request.
	 *
	 * @var Request
	 */
	private $request;

	/**
	 * Order form.
	 *
	 * @var Forms\Form
	 */
	private $form;

	/**
	 * OrderDetails
	 *
	 * @var Form
	 */
	private $orderForm;

	/**
	 * Options provider.
	 *
	 * @var OptionsProvider
	 */
	private $optionsProvider;

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
	 * Carrier modal.
	 *
	 * @var CarrierModal
	 */
	private $carrierModal;

	/**
	 * Metabox constructor.
	 *
	 * @param Engine               $latte_engine         PacketeryLatte engine.
	 * @param MessageManager       $message_manager      Message manager.
	 * @param CoreHelper           $coreHelper           CoreHelper.
	 * @param Request              $request              Http request.
	 * @param OptionsProvider      $optionsProvider      Options provider.
	 * @param Repository           $orderRepository      Order repository.
	 * @param Page                 $logPage              Log page.
	 * @param AttributeMapper      $mapper               AttributeMapper.
	 * @param WidgetOptionsBuilder $widgetOptionsBuilder Widget options builder.
	 * @param EntityRepository     $carrierRepository    Carrier repository.
	 * @param Order                $orderValidator       Order validator.
	 * @param DetailCommonLogic    $detailCommonLogic    Detail common logic.
	 * @param Form                 $orderForm            Order details.
	 * @param CarrierModal         $carrierModal         Carrier change modal.
	 */
	public function __construct(
		Engine $latte_engine,
		MessageManager $message_manager,
		CoreHelper $coreHelper,
		Request $request,
		OptionsProvider $optionsProvider,
		Repository $orderRepository,
		Page $logPage,
		AttributeMapper $mapper,
		WidgetOptionsBuilder $widgetOptionsBuilder,
		EntityRepository $carrierRepository,
		Order $orderValidator,
		DetailCommonLogic $detailCommonLogic,
		Form $orderForm,
		CarrierModal $carrierModal
	) {
		$this->latte_engine         = $latte_engine;
		$this->message_manager      = $message_manager;
		$this->coreHelper           = $coreHelper;
		$this->request              = $request;
		$this->optionsProvider      = $optionsProvider;
		$this->orderRepository      = $orderRepository;
		$this->logPage              = $logPage;
		$this->mapper               = $mapper;
		$this->widgetOptionsBuilder = $widgetOptionsBuilder;
		$this->carrierRepository    = $carrierRepository;
		$this->orderValidator       = $orderValidator;
		$this->detailCommonLogic    = $detailCommonLogic;
		$this->orderForm            = $orderForm;
		$this->carrierModal         = $carrierModal;
	}

	/**
	 *  Registers related hooks.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
	}

	/**
	 *  Add metaboxes
	 */
	public function add_meta_boxes(): void {
		if ( ! $this->detailCommonLogic->isPacketeryOrder() ) {
			return;
		}

		$this->initializeForm();
		$parts = $this->prepareMetaboxParts();

		if ( ! empty( $parts ) ) {
			add_meta_box(
				'packetery_metabox',
				__( 'Packeta', 'packeta' ),
				array(
					$this,
					'render_metabox',
				),
				ModuleHelper::isHposEnabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order',
				'side',
				'high'
			);
		}
	}

	/**
	 * Renders metabox content.
	 */
	public function render_metabox(): void {
		$parts = $this->prepareMetaboxParts();

		if ( isset( $parts[ self::PART_ERROR ] ) ) {
			ModuleHelper::renderString( $parts[ self::PART_ERROR ] );

			return;
		}

		if ( isset( $parts[ self::PART_CARRIER_CHANGE ] ) ) {
			ModuleHelper::renderString( $parts[ self::PART_CARRIER_CHANGE ] );
		}
		if ( isset( $parts[ self::PART_CARRIER_CHANGE ], $parts[ self::PART_MAIN ] ) ) {
			ModuleHelper::renderString( '<hr>' );
		}
		if ( isset( $parts[ self::PART_MAIN ] ) ) {
			ModuleHelper::renderString( $parts[ self::PART_MAIN ] );
		}
	}

	/**
	 * Prepares metabox parts.
	 */
	private function prepareMetaboxParts(): array {
		static $partsCache;

		if ( isset( $partsCache ) ) {
			return $partsCache;
		}

		$orderId = $this->detailCommonLogic->getOrderId();
		if ( null === $orderId ) {
			$partsCache = [];

			return $partsCache;
		}

		try {
			$order = $this->orderRepository->getById( $orderId );
		} catch ( InvalidCarrierException $exception ) {
			$partsCache = [
				self::PART_ERROR => $this->latte_engine->renderToString(
					PACKETERY_PLUGIN_DIR . '/template/order/metabox-form-error.latte',
					[
						'errorMessage' => $exception->getMessage(),
					]
				),
			];

			return $partsCache;
		}

		$parts = [];
		if ( $this->carrierModal->canBeDisplayed() ) {
			$parts[ self::PART_CARRIER_CHANGE ] = $this->carrierModal->getMetaboxHtml();
		}

		if ( null === $order ) {
			$partsCache = $parts;

			return $partsCache;
		}

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
			$packetClaimTrackingUrl = $this->coreHelper->get_tracking_url( $order->getPacketClaimId() );
		}

		$packetId = $order->getPacketId();
		if ( $packetId ) {
			$packetCancelLink = $this->getOrderActionLink(
				$order,
				PacketActionsCommonLogic::ACTION_CANCEL_PACKET,
				[
					PacketActionsCommonLogic::PARAM_PACKET_ID => $packetId,
				]
			);

			$statuses    = PacketSynchronizer::getPacketStatuses();
			$orderStatus = $statuses[ $order->getPacketStatus() ]->getTranslatedName();

			$statusClasses = [
				'received data'         => 'received-data',
				'unknown'               => 'unknown',
				'delivered'             => 'delivered',
				'cancelled'             => 'cancelled',
				'returned'              => 'returned',
				'rejected by recipient' => 'rejected',
			];

			$statusClass = 'delivery-status';
			$statusType  = $statuses[ $order->getPacketStatus() ]->getName();

			if ( isset( $statusClasses[ $statusType ] ) ) {
				$statusClass = $statusClasses[ $statusType ];
			}

			$parts[ self::PART_MAIN ] = $this->latte_engine->renderToString(
				PACKETERY_PLUGIN_DIR . '/template/order/metabox-common.latte',
				[
					'order'                      => $order,
					'orderStatus'                => $orderStatus,
					'statusClass'                => $statusClass,
					'isPacketSubmissionPossible' => false,
					'orderWarningFields'         => [],
					'packetCancelLink'           => $packetCancelLink,
					'packetTrackingUrl'          => $this->coreHelper->get_tracking_url( $packetId ),
					'packetClaimTrackingUrl'     => $packetClaimTrackingUrl,
					'showLogsLink'               => $showLogsLink,
					'packetClaimUrl'             => $packetClaimUrl,
					'packetClaimCancelUrl'       => $packetClaimCancelUrl,
					'translations'               => [
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

			$partsCache = $parts;

			return $partsCache;
		}

		$this->orderForm->setDefaults(
			$this->form,
			$order->getFinalWeight(),
			$order->getFinalWeight(),
			$order->getLength(),
			$order->getWidth(),
			$order->getHeight(),
			$order->getCod(),
			$order->getValue(),
			$order->containsAdultContent(),
			$this->coreHelper->getStringFromDateTime( $order->getDeliverOn(), CoreHelper::DATEPICKER_FORMAT )
		);

		$prev_invalid_values = get_transient( 'packetery_metabox_nette_form_prev_invalid_values' );
		if ( $prev_invalid_values ) {
			$this->form->setValues( $prev_invalid_values );
			$this->form->validate();
		}
		delete_transient( 'packetery_metabox_nette_form_prev_invalid_values' );

		$isPacketSubmissionPossible = $this->orderValidator->isValid( $order );
		$packetSubmitUrl            = $this->getOrderActionLink( $order, PacketActionsCommonLogic::ACTION_SUBMIT_PACKET );

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

		$parts[ self::PART_MAIN ] = $this->latte_engine->renderToString(
			PACKETERY_PLUGIN_DIR . '/template/order/metabox-form.latte',
			[
				'form'                       => $this->form,
				'order'                      => $order,
				'showWidgetButton'           => $showWidgetButton,
				'widgetButtonError'          => $widgetButtonError,
				'showHdWidget'               => $showHdWidget,
				'isPacketSubmissionPossible' => $isPacketSubmissionPossible,
				'orderWarningFields'         => Form::getInvalidFieldsFromValidationResult( $this->orderValidator->validate( $order ) ),
				'packetCancelLink'           => null,
				'packetTrackingUrl'          => null,
				'orderStatus'                => null,
				'packetSubmitUrl'            => $packetSubmitUrl,
				'packetClaimTrackingUrl'     => $packetClaimTrackingUrl,
				'packetClaimUrl'             => $packetClaimUrl,
				'packetClaimCancelUrl'       => $packetClaimCancelUrl,
				'orderCurrency'              => get_woocommerce_currency_symbol( $order->getCurrency() ),
				'isCodPayment'               => $order->hasCod(),
				'allowsAdultContent'         => $order->allowsAdultContent(),
				'requiresSizeDimensions'     => $order->getCarrier()->requiresSize(),
				'logo'                       => plugin_dir_url( PACKETERY_PLUGIN_DIR . '/packeta.php' ) . 'public/images/packeta-symbol.png',
				'showLogsLink'               => $showLogsLink,
				'hasOrderManualWeight'       => $order->hasManualWeight(),
				'isPacketaPickupPoint'       => $order->isPacketaInternalPickupPoint(),
				'translations'               => [
					'packetSubmissionValidationErrorTooltip' => __( 'It is not possible to submit the shipment because all the information required for this shipment is not filled.', 'packeta' ),
					'showLogs'                  => __( 'Show logs', 'packeta' ),
					'weightIsManual'            => __( 'Weight is manually set. To calculate weight remove field content and save.', 'packeta' ),
					'submitPacket'              => __( 'Submit to Packeta', 'packeta' ),
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

		$partsCache = $parts;

		return $partsCache;
	}

	/**
	 * Saves added packetery form fields to order metas.
	 *
	 * @param Entity\Order $order   Order.
	 * @param WC_Order     $wcOrder WC Order.
	 *
	 * @return void
	 * @throws WC_Data_Exception When invalid data are passed during shipping address update.
	 */
	public function saveFields( Entity\Order $order, WC_Order $wcOrder ): void {
		$this->initializeForm();

		$orderId = (int) $order->getNumber();
		if (
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			null === $this->request->getPost( 'packetery_order_metabox_nonce' )
		) {
			return;
		}

		if ( false === $this->form->isValid() ) {
			set_transient( 'packetery_metabox_nette_form_prev_invalid_values', $this->form->getValues( true ) );
			$this->message_manager->flash_message( __( 'Packeta: entered data is not valid!', 'packeta' ), MessageManager::TYPE_ERROR );

			return;
		}

		$formValues = $this->form->getValues( 'array' );

		if ( ! wp_verify_nonce( $formValues['packetery_order_metabox_nonce'] ) ) {
			$this->message_manager->flash_message( __( 'Session has expired! Please try again.', 'packeta' ), MessageManager::TYPE_ERROR );

			return;
		}

		if ( ! current_user_can( 'edit_post', $orderId ) ) {
			$this->message_manager->flash_message( __( 'You do not have sufficient rights to make changes!', 'packeta' ), MessageManager::TYPE_ERROR );

			return;
		}

		$propsToSave = [
			Form::FIELD_WIDTH  => ( is_numeric( $formValues[ Form::FIELD_WIDTH ] ) ? (float) number_format( $formValues[ Form::FIELD_WIDTH ], 0, '.', '' ) : null ),
			Form::FIELD_LENGTH => ( is_numeric( $formValues[ Form::FIELD_LENGTH ] ) ? (float) number_format( $formValues[ Form::FIELD_LENGTH ], 0, '.', '' ) : null ),
			Form::FIELD_HEIGHT => ( is_numeric( $formValues[ Form::FIELD_HEIGHT ] ) ? (float) number_format( $formValues[ Form::FIELD_HEIGHT ], 0, '.', '' ) : null ),
		];

		if ( ! is_numeric( $formValues[ Form::FIELD_WEIGHT ] ) ) {
			$propsToSave[ Form::FIELD_WEIGHT ] = null;
		} elseif ( (float) $formValues[ Form::FIELD_WEIGHT ] !== (float) $formValues[ Form::FIELD_ORIGINAL_WEIGHT ] ) {
			$propsToSave[ Form::FIELD_WEIGHT ] = (float) $formValues[ Form::FIELD_WEIGHT ];
		}

		if ( $formValues[ Attribute::POINT_ID ] && $order->isPickupPointDelivery() ) {
			foreach ( Attribute::$pickupPointAttrs as $pickupPointAttr ) {
				$pickupPointValue = $formValues[ $pickupPointAttr['name'] ];

				if ( Attribute::CARRIER_ID === $pickupPointAttr['name'] ) {
					if ( ! empty( $formValues[ Attribute::CARRIER_ID ] ) ) {
						$pickupPointValue = $formValues[ Attribute::CARRIER_ID ];
					} else {
						$pickupPointValue = $order->getCarrier()->getId();
					}
				}

				$propsToSave[ $pickupPointAttr['name'] ] = $pickupPointValue;

				if ( $this->optionsProvider->replaceShippingAddressWithPickupPointAddress() ) {
					$this->mapper->toWcOrderShippingAddress( $wcOrder, $pickupPointAttr['name'], (string) $pickupPointValue );
				}
			}
		}

		if ( '1' === $formValues[ Attribute::ADDRESS_IS_VALIDATED ] && $order->isHomeDelivery() ) {
			$address = $this->mapper->toValidatedAddress( $formValues );
			$order->setDeliveryAddress( $address );
			$order->setAddressValidated( true );
		}

		$order->setAdultContent( $formValues[ Form::FIELD_ADULT_CONTENT ] );
		$order->setCod( is_numeric( $formValues[ Form::FIELD_COD ] ) ? CoreHelper::simplifyFloat( $formValues[ Form::FIELD_COD ], 10 ) : null );
		$order->setValue( is_numeric( $formValues[ Form::FIELD_VALUE ] ) ? CoreHelper::simplifyFloat( $formValues[ Form::FIELD_VALUE ], 10 ) : null );
		$order->setDeliverOn( $this->coreHelper->getDateTimeFromString( $formValues[ Form::FIELD_DELIVER_ON ] ) );

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

	/**
	 * Initializes form to render or process.
	 *
	 * @return void
	 */
	private function initializeForm(): void {
		$this->form = $this->orderForm->create();
		$this->form->addHidden( 'packetery_order_metabox_nonce' );
		$this->form->setDefaults( [ 'packetery_order_metabox_nonce' => wp_create_nonce() ] );

		foreach ( Attribute::$pickupPointAttrs as $pickupPointAttr ) {
			$this->form->addHidden( $pickupPointAttr['name'] );
		}

		foreach ( Attribute::$homeDeliveryAttrs as $homeDeliveryAttr ) {
			$this->form->addHidden( $homeDeliveryAttr['name'] );
		}

		$this->form->addButton( 'packetery_pick_pickup_point', __( 'Choose pickup point', 'packeta' ) );
		$this->form->addButton( 'packetery_pick_address', __( 'Check shipping address', 'packeta' ) );
	}

}
