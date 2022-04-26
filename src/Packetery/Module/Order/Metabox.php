<?php
/**
 * Class Metabox
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core;
use Packetery\Core\Helper;
use Packetery\Module\Checkout;
use Packetery\Module\FormFactory;
use Packetery\Module\MessageManager;
use Packetery\Module\Options;
use Packetery\Module\Plugin;
use PacketeryLatte\Engine;
use PacketeryNette\Forms\Form;
use PacketeryNette\Http\Request;

/**
 * Class Metabox
 *
 * @package Packetery\Order
 */
class Metabox {

	const FIELD_WEIGHT = 'packetery_weight';
	const FIELD_WIDTH  = 'packetery_width';
	const FIELD_LENGTH = 'packetery_length';
	const FIELD_HEIGHT = 'packetery_height';

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
	 * Metabox constructor.
	 *
	 * @param Engine           $latte_engine    PacketeryLatte engine.
	 * @param MessageManager   $message_manager Message manager.
	 * @param Helper           $helper          Helper.
	 * @param Request          $request         Http request.
	 * @param Options\Provider $optionsProvider Options provider.
	 * @param FormFactory      $formFactory     Form factory.
	 * @param Repository       $orderRepository Order repository.
	 */
	public function __construct(
		Engine $latte_engine,
		MessageManager $message_manager,
		Helper $helper,
		Request $request,
		Options\Provider $optionsProvider,
		FormFactory $formFactory,
		Repository $orderRepository
	) {
		$this->latte_engine    = $latte_engine;
		$this->message_manager = $message_manager;
		$this->helper          = $helper;
		$this->request         = $request;
		$this->optionsProvider = $optionsProvider;
		$this->formFactory     = $formFactory;
		$this->orderRepository = $orderRepository;
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
			__( 'Packeta', 'packetery' ),
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
		$this->order_form->addText( self::FIELD_WEIGHT, __( 'Weight (kg)', 'packetery' ) )
							->setRequired( false )
							->addRule( $this->order_form::FLOAT, __( 'Provide numeric value!', 'packetery' ) );
		$this->order_form->addText( self::FIELD_WIDTH, __( 'Width (mm)', 'packetery' ) )
							->setRequired( false )
							->addRule( $this->order_form::FLOAT, __( 'Provide numeric value!', 'packetery' ) );
		$this->order_form->addText( self::FIELD_LENGTH, __( 'Length (mm)', 'packetery' ) )
							->setRequired( false )
							->addRule( $this->order_form::FLOAT, __( 'Provide numeric value!', 'packetery' ) );
		$this->order_form->addText( self::FIELD_HEIGHT, __( 'Height (mm)', 'packetery' ) )
							->setRequired( false )
							->addRule( $this->order_form::FLOAT, __( 'Provide numeric value!', 'packetery' ) );

		foreach ( Checkout::$pickupPointAttrs as $attrs ) {
			$this->order_form->addHidden( $attrs['name'] );
		}
		$this->order_form->addButton( 'packetery_pick_pickup_point', __( 'choosePickupPoint', 'packetery' ) );
	}

	/**
	 *  Renders metabox
	 */
	public function render_metabox(): void {
		global $post;

		$order = $this->orderRepository->getById( (int) $post->ID );
		if ( null === $order ) {
			return;
		}
		$packetId = $order->getPacketId();

		if ( $packetId ) {
			$this->latte_engine->render(
				PACKETERY_PLUGIN_DIR . '/template/order/metabox-overview.latte',
				[
					'packet_id'           => $packetId,
					'packet_tracking_url' => $this->helper->get_tracking_url( $packetId ),
				]
			);

			return;
		}

		$this->order_form->setDefaults(
			[
				'packetery_order_metabox_nonce' => wp_create_nonce(),
				self::FIELD_WEIGHT              => $order->getWeight(),
				self::FIELD_WIDTH               => $order->getWidth(),
				self::FIELD_LENGTH              => $order->getLength(),
				self::FIELD_HEIGHT              => $order->getHeight(),
			]
		);

		$prev_invalid_values = get_transient( 'packetery_metabox_nette_form_prev_invalid_values' );
		if ( $prev_invalid_values ) {
			$this->order_form->setValues( $prev_invalid_values );
			$this->order_form->validate();
		}
		delete_transient( 'packetery_metabox_nette_form_prev_invalid_values' );

		$widgetSettings = [
			'packeteryApiKey'           => $this->optionsProvider->get_api_key(),
			'country'                   => ( $order->getShippingCountry() ? $order->getShippingCountry() : '' ),
			'language'                  => substr( get_locale(), 0, 2 ),
			'isAgeVerificationRequired' => $order->containsAdultContent(),
			'appIdentity'               => Plugin::getAppIdentity(),
			'weight'                    => $order->getWeight(),
			'carriers'                  => Checkout::getWidgetCarriersParam( $order->isPickupPointDelivery(), $order->getCarrierId() ),
			'pickupPointAttrs'          => Checkout::$pickupPointAttrs,
		];

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/metabox-form.latte',
			[
				'form'           => $this->order_form,
				'order'          => $order,
				'widgetSettings' => $widgetSettings,
				'logo'           => plugin_dir_url( PACKETERY_PLUGIN_DIR . '/packeta.php' ) . 'public/packeta-symbol.png',
			]
		);
	}

	/**
	 * Saves added packetery form fields to order metas.
	 *
	 * @param mixed $orderId Order id.
	 *
	 * @return mixed Order id.
	 * @throws \WC_Data_Exception When invalid data are passed during shipping address update.
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
			$this->message_manager->flash_message( __( 'Error happened in Packeta fields!', 'packetery' ), MessageManager::TYPE_ERROR );

			return $orderId;
		}

		$values = $this->order_form->getValues( 'array' );

		if ( ! wp_verify_nonce( $values['packetery_order_metabox_nonce'] ) ) {
			$this->message_manager->flash_message( __( 'Session has expired! Please try again.', 'packetery' ), MessageManager::TYPE_ERROR );

			return $orderId;
		}

		if ( ! current_user_can( 'edit_post', $orderId ) ) {
			$this->message_manager->flash_message( __( 'You are not allowed to edit posts!', 'packetery' ), MessageManager::TYPE_ERROR );

			return $orderId;
		}

		$propsToSave = [
			self::FIELD_WEIGHT => ( is_numeric( $values[ self::FIELD_WEIGHT ] ) ? (float) $values[ self::FIELD_WEIGHT ] : null ),
			self::FIELD_WIDTH  => ( is_numeric( $values[ self::FIELD_WIDTH ] ) ? (float) number_format( $values[ self::FIELD_WIDTH ], 0, '.', '' ) : null ),
			self::FIELD_LENGTH => ( is_numeric( $values[ self::FIELD_LENGTH ] ) ? (float) number_format( $values[ self::FIELD_LENGTH ], 0, '.', '' ) : null ),
			self::FIELD_HEIGHT => ( is_numeric( $values[ self::FIELD_HEIGHT ] ) ? (float) number_format( $values[ self::FIELD_HEIGHT ], 0, '.', '' ) : null ),
		];

		if ( $values[ Checkout::ATTR_POINT_ID ] && $order->isPickupPointDelivery() ) {
			$wcOrder = wc_get_order( $orderId ); // Can not be false due condition at the beginning of method.
			foreach ( Checkout::$pickupPointAttrs as $pickupPointAttr ) {
				$value = $values[ $pickupPointAttr['name'] ];

				if ( Checkout::ATTR_CARRIER_ID === $pickupPointAttr['name'] ) {
					$value = ( ! empty( $values[ Checkout::ATTR_CARRIER_ID ] ) ? $values[ Checkout::ATTR_CARRIER_ID ] : \Packetery\Module\Carrier\Repository::INTERNAL_PICKUP_POINTS_ID );
				}

				$propsToSave[ $pickupPointAttr['name'] ] = $value;

				if ( $this->optionsProvider->replaceShippingAddressWithPickupPointAddress() ) {
					Checkout::updateShippingAddressProperty( $wcOrder, $pickupPointAttr['name'], (string) $value );
				}
			}
			$wcOrder->save();
		}

		$orderSize = $order->getSize();
		if ( null === $orderSize ) {
			$orderSize = new Core\Entity\Size();
		}

		foreach ( $propsToSave as $attrName => $attrValue ) {
			switch ( $attrName ) {
				case self::FIELD_WEIGHT:
					$order->setWeight( $attrValue );
					break;
				case self::FIELD_WIDTH:
					$orderSize->setWidth( $attrValue );
					break;
				case self::FIELD_LENGTH:
					$orderSize->setLength( $attrValue );
					break;
				case self::FIELD_HEIGHT:
					$orderSize->setHeight( $attrValue );
					break;
			}
		}

		$order->setSize( $orderSize );
		Checkout::updateOrderEntityFromPropsToSave( $order, $propsToSave );
		$this->orderRepository->save( $order );

		return $orderId;
	}
}
