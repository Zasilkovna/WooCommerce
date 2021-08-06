<?php
/**
 * Class Metabox
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );


namespace Packetery\Order;

use Packetery\Helper;
use Packetery\Message_Manager;

/**
 * Class Metabox
 *
 * @package Packetery\Order
 */
class Metabox {

	/**
	 * Latte engine.
	 *
	 * @var \Latte\Engine
	 */
	private $latte_engine;

	/**
	 * Message manager.
	 *
	 * @var Message_Manager
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
	 * @var \Nette\Forms\Form
	 */
	private $order_form;

	/**
	 * Metabox constructor.
	 *
	 * @param \Latte\Engine   $latte_engine Latte engine.
	 * @param Message_Manager $message_manager Message manager.
	 * @param Helper          $helper Helper.
	 */
	public function __construct( \Latte\Engine $latte_engine, Message_Manager $message_manager, Helper $helper ) {
		$this->latte_engine    = $latte_engine;
		$this->message_manager = $message_manager;
		$this->helper          = $helper;
	}

	/**
	 *  Registers related hooks.
	 */
	public function register(): void {
		$this->order_form = new \Nette\Forms\Form();
		$this->add_fields();
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_fields' ) );
	}

	/**
	 *  Add metaboxes
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'packetery_metabox',
			__( 'Packeta', 'packetery' ),
			array(
				$this,
				'render_metabox',
			),
			'shop_order',
			'side',
			'core'
		);
	}

	/**
	 *  Adds packetery fields to order form.
	 */
	public function add_fields(): void {
		$this->order_form->addHidden( 'packetery_order_metabox_nonce' );
		$this->order_form->addText( 'packetery_weight', __( 'Weight (kg)', 'packetery' ) )
							->setRequired( false )
							->addRule( $this->order_form::FLOAT, __( 'Provide numeric value!', 'packetery' ) );
		$this->order_form->addText( 'packetery_width', __( 'Width (mm)', 'packetery' ) )
							->setRequired( false )
							->addRule( $this->order_form::FLOAT, __( 'Provide numeric value!', 'packetery' ) );
		$this->order_form->addText( 'packetery_length', __( 'Length (mm)', 'packetery' ) )
							->setRequired( false )
							->addRule( $this->order_form::FLOAT, __( 'Provide numeric value!', 'packetery' ) );
		$this->order_form->addText( 'packetery_height', __( 'Height (mm)', 'packetery' ) )
							->setRequired( false )
							->addRule( $this->order_form::FLOAT, __( 'Provide numeric value!', 'packetery' ) );
	}

	/**
	 *  Renders metabox
	 */
	public function render_metabox(): void {
		global $post;
		$order               = wc_get_order( $post->ID );
		$packetery_packet_id = $order->get_meta( 'packetery_packet_id' );

		if ( $packetery_packet_id ) {
			$this->latte_engine->render(
				PACKETERY_PLUGIN_DIR . '/template/Order/metabox-overview.latte',
				array(
					'packet_id'           => $packetery_packet_id,
					'packet_tracking_url' => $this->helper->get_tracking_url( $packetery_packet_id ),
				)
			);

			return;
		}

		$this->order_form->setDefaults(
			array(
				'packetery_order_metabox_nonce' => wp_create_nonce(),
				'packetery_weight'              => get_post_meta( $post->ID, 'packetery_weight', true ),
				'packetery_width'               => get_post_meta( $post->ID, 'packetery_width', true ),
				'packetery_length'              => get_post_meta( $post->ID, 'packetery_length', true ),
				'packetery_height'              => get_post_meta( $post->ID, 'packetery_height', true ),
			)
		);

		$prev_invalid_values = get_transient( 'packetery_metabox_nette_form_prev_invalid_values' );
		if ( $prev_invalid_values ) {
			$this->order_form->setValues( $prev_invalid_values );
			$this->order_form->validate();
		}
		delete_transient( 'packetery_metabox_nette_form_prev_invalid_values' );

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/Order/metabox-form.latte',
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
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( ! isset( $_POST['packetery_order_metabox_nonce'] ) ) {
			return $post_id; // Form is not rendered to user.
		}

		if ( $this->order_form->isValid() === false ) {
			set_transient( 'packetery_metabox_nette_form_prev_invalid_values', $this->order_form->getValues( true ) );
			$this->message_manager->flash_error_message( __( 'Error happened in Packeta fields!', 'packetery' ) );

			return $post_id;
		}

		$values = $this->order_form->getValues();

		if ( ! wp_verify_nonce( $values->packetery_order_metabox_nonce ) ) {
			$this->message_manager->flash_error_message( __( 'Session has expired! Please try again.', 'packetery' ) );

			return $post_id;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			$this->message_manager->flash_error_message( __( 'You are not allowed to edit posts!', 'packetery' ) );

			return $post_id;
		}

		update_post_meta( $post_id, 'packetery_weight', ( is_numeric( $values->packetery_weight ) ? number_format( $values->packetery_weight, 4, '.', '' ) : '' ) );
		update_post_meta( $post_id, 'packetery_width', ( is_numeric( $values->packetery_width ) ? number_format( $values->packetery_width, 0, '.', '' ) : '' ) );
		update_post_meta( $post_id, 'packetery_length', ( is_numeric( $values->packetery_length ) ? number_format( $values->packetery_length, 0, '.', '' ) : '' ) );
		update_post_meta( $post_id, 'packetery_height', ( is_numeric( $values->packetery_height ) ? number_format( $values->packetery_height, 0, '.', '' ) : '' ) );

		return $post_id;
	}
}
