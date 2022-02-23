<?php
/**
 * Class Metabox
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Helper;
use Packetery\Module\Checkout;
use Packetery\Module\EntityFactory;
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
	 * Order factory.
	 *
	 * @var EntityFactory\Order
	 */
	private $orderFactory;

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
	 * Metabox constructor.
	 *
	 * @param Engine              $latte_engine    PacketeryLatte engine.
	 * @param MessageManager      $message_manager Message manager.
	 * @param Helper              $helper          Helper.
	 * @param Request             $request         Http request.
	 * @param EntityFactory\Order $orderFactory    Order factory.
	 * @param Options\Provider    $optionsProvider Options provider.
	 * @param FormFactory         $formFactory     Form factory.
	 */
	public function __construct(
		Engine $latte_engine,
		MessageManager $message_manager,
		Helper $helper,
		Request $request,
		EntityFactory\Order $orderFactory,
		Options\Provider $optionsProvider,
		FormFactory $formFactory
	) {
		$this->latte_engine    = $latte_engine;
		$this->message_manager = $message_manager;
		$this->helper          = $helper;
		$this->request         = $request;
		$this->orderFactory    = $orderFactory;
		$this->optionsProvider = $optionsProvider;
		$this->formFactory     = $formFactory;
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
		$order = $this->orderFactory->fromGlobals();
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
		$this->order_form->addText( Entity::META_WEIGHT, __( 'Weight (kg)', 'packetery' ) )
							->setRequired( false )
							->addRule( $this->order_form::FLOAT, __( 'Provide numeric value!', 'packetery' ) );
		$this->order_form->addText( Entity::META_WIDTH, __( 'Width (mm)', 'packetery' ) )
							->setRequired( false )
							->addRule( $this->order_form::FLOAT, __( 'Provide numeric value!', 'packetery' ) );
		$this->order_form->addText( Entity::META_LENGTH, __( 'Length (mm)', 'packetery' ) )
							->setRequired( false )
							->addRule( $this->order_form::FLOAT, __( 'Provide numeric value!', 'packetery' ) );
		$this->order_form->addText( Entity::META_HEIGHT, __( 'Height (mm)', 'packetery' ) )
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
		$order = $this->orderFactory->fromGlobals();
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
				Entity::META_WEIGHT             => $order->getWeight(),
				Entity::META_WIDTH              => $order->getWidth(),
				Entity::META_LENGTH             => $order->getLength(),
				Entity::META_HEIGHT             => $order->getHeight(),
			]
		);

		$prev_invalid_values = get_transient( 'packetery_metabox_nette_form_prev_invalid_values' );
		if ( $prev_invalid_values ) {
			$this->order_form->setValues( $prev_invalid_values );
			$this->order_form->validate();
		}
		delete_transient( 'packetery_metabox_nette_form_prev_invalid_values' );

		$wpOrder        = wc_get_order( $order->getNumber() );
		$widgetSettings = [
			'packeteryApiKey'  => $this->optionsProvider->get_api_key(),
			'country'          => mb_strtolower( $wpOrder->get_shipping_country() ),
			'language'         => substr( get_locale(), 0, 2 ),
			'appIdentity'      => Plugin::getAppIdentity(),
			'weight'           => $order->getWeight(),
			'carriers'         => Checkout::getWidgetCarriersParam( $order->isPickupPointDelivery(), $order->getCarrierId() ),
			'pickupPointAttrs' => Checkout::$pickupPointAttrs,
		];

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/metabox-form.latte',
			[
				'form'           => $this->order_form,
				'order'          => $order,
				'widgetSettings' => $widgetSettings,
				'logo'           => plugin_dir_url( PACKETERY_PLUGIN_DIR . '/packetery.php' ) . 'public/packeta-symbol.png',
			]
		);
	}

	/**
	 * Saves added packetery form fields to order metas.
	 *
	 * @param mixed $post_id Order id.
	 *
	 * @return mixed Order id.
	 */
	public function save_fields( $post_id ) {
		$order = $this->orderFactory->fromPostId( $post_id );
		if (
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			null === $this->request->getPost( 'packetery_order_metabox_nonce' ) ||
			null === $order
		) {
			return $post_id;
		}

		if ( false === $this->order_form->isValid() ) {
			set_transient( 'packetery_metabox_nette_form_prev_invalid_values', $this->order_form->getValues( true ) );
			$this->message_manager->flash_message( __( 'Error happened in Packeta fields!', 'packetery' ), MessageManager::TYPE_ERROR );

			return $post_id;
		}

		$values = $this->order_form->getValues( 'array' );

		if ( ! wp_verify_nonce( $values['packetery_order_metabox_nonce'] ) ) {
			$this->message_manager->flash_message( __( 'Session has expired! Please try again.', 'packetery' ), MessageManager::TYPE_ERROR );

			return $post_id;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			$this->message_manager->flash_message( __( 'You are not allowed to edit posts!', 'packetery' ), MessageManager::TYPE_ERROR );

			return $post_id;
		}

		update_post_meta( $post_id, Entity::META_WEIGHT, ( is_numeric( $values[ Entity::META_WEIGHT ] ) ? Plugin::simplifyWeight( $values[ Entity::META_WEIGHT ] ) : '' ) );
		update_post_meta( $post_id, Entity::META_WIDTH, ( is_numeric( $values[ Entity::META_WIDTH ] ) ? number_format( $values[ Entity::META_WIDTH ], 0, '.', '' ) : '' ) );
		update_post_meta( $post_id, Entity::META_LENGTH, ( is_numeric( $values[ Entity::META_LENGTH ] ) ? number_format( $values[ Entity::META_LENGTH ], 0, '.', '' ) : '' ) );
		update_post_meta( $post_id, Entity::META_HEIGHT, ( is_numeric( $values[ Entity::META_HEIGHT ] ) ? number_format( $values[ Entity::META_HEIGHT ], 0, '.', '' ) : '' ) );

		if ( $values[ Entity::META_POINT_ID ] && $order->isPickupPointDelivery() ) {
			foreach ( Checkout::$pickupPointAttrs as $pickupPointAttr ) {
				$value = $values[ $pickupPointAttr['name'] ];

				if ( Entity::META_CARRIER_ID === $pickupPointAttr['name'] ) {
					$value = ( ! empty( $values[ Entity::META_CARRIER_ID ] ) ? $values[ Entity::META_CARRIER_ID ] : \Packetery\Module\Carrier\Repository::INTERNAL_PICKUP_POINTS_ID );
				}

				update_post_meta( $post_id, $pickupPointAttr['name'], $value );
			}
		}

		return $post_id;
	}
}
