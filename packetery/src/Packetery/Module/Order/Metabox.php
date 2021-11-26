<?php
/**
 * Class Metabox
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Helper;
use Packetery\Module\FormFactory;
use Packetery\Module\MessageManager;
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
	 * Form factory.
	 *
	 * @var FormFactory
	 */
	private $formFactory;

	/**
	 * Metabox constructor.
	 *
	 * @param Engine         $latte_engine    PacketeryLatte engine.
	 * @param MessageManager $message_manager Message manager.
	 * @param Helper         $helper          Helper.
	 * @param Request        $request         Http request.
	 * @param FormFactory    $formFactory     Form factory.
	 */
	public function __construct( Engine $latte_engine, MessageManager $message_manager, Helper $helper, Request $request, FormFactory $formFactory ) {
		$this->latte_engine    = $latte_engine;
		$this->message_manager = $message_manager;
		$this->helper          = $helper;
		$this->request         = $request;
		$this->formFactory     = $formFactory;
	}

	/**
	 *  Registers related hooks.
	 */
	public function register(): void {
		$this->order_form = $this->formFactory->create();
		$this->add_fields();
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_fields' ) );
	}

	/**
	 *  Add metaboxes
	 */
	public function add_meta_boxes(): void {
		$order = Entity::fromGlobals();
		if ( null === $order || false === $order->isPacketeryRelated() ) {
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
	}

	/**
	 *  Renders metabox
	 */
	public function render_metabox(): void {
		/**
		 * We know for sure $post exists and thus $entity is never null.
		 *
		 * @var Entity $entity
		 */
		$entity   = Entity::fromGlobals();
		$packetId = $entity->getPacketId();

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
				Entity::META_WEIGHT             => $entity->getWeight(),
				Entity::META_WIDTH              => $entity->getWidth(),
				Entity::META_LENGTH             => $entity->getLength(),
				Entity::META_HEIGHT             => $entity->getHeight(),
			]
		);

		$prev_invalid_values = get_transient( 'packetery_metabox_nette_form_prev_invalid_values' );
		if ( $prev_invalid_values ) {
			$this->order_form->setValues( $prev_invalid_values );
			$this->order_form->validate();
		}
		delete_transient( 'packetery_metabox_nette_form_prev_invalid_values' );

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/metabox-form.latte',
			array(
				'form' => $this->order_form,
			)
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
		$order = Entity::fromPostId( $post_id );

		if (
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			! isset( $this->request->post['packetery_order_metabox_nonce'] ) ||
			null === $order || false === $order->isPacketeryRelated()
		) {
			return $post_id;
		}

		if ( $this->order_form->isValid() === false ) {
			set_transient( 'packetery_metabox_nette_form_prev_invalid_values', $this->order_form->getValues( true ) );
			$this->message_manager->flash_message( __( 'Error happened in Packeta fields!', 'packetery' ), MessageManager::TYPE_ERROR );

			return $post_id;
		}

		$values = $this->order_form->getValues();

		if ( ! wp_verify_nonce( $values->packetery_order_metabox_nonce ) ) {
			$this->message_manager->flash_message( __( 'Session has expired! Please try again.', 'packetery' ), MessageManager::TYPE_ERROR );

			return $post_id;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			$this->message_manager->flash_message( __( 'You are not allowed to edit posts!', 'packetery' ), MessageManager::TYPE_ERROR );

			return $post_id;
		}

		update_post_meta( $post_id, Entity::META_WEIGHT, ( is_numeric( $values->packetery_weight ) ? number_format( $values->packetery_weight, 4, '.', '' ) : '' ) );
		update_post_meta( $post_id, Entity::META_WIDTH, ( is_numeric( $values->packetery_width ) ? number_format( $values->packetery_width, 0, '.', '' ) : '' ) );
		update_post_meta( $post_id, Entity::META_LENGTH, ( is_numeric( $values->packetery_length ) ? number_format( $values->packetery_length, 0, '.', '' ) : '' ) );
		update_post_meta( $post_id, Entity::META_HEIGHT, ( is_numeric( $values->packetery_height ) ? number_format( $values->packetery_height, 0, '.', '' ) : '' ) );

		return $post_id;
	}
}
