<?php
/**
 * Class Message_Manager
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery;

/**
 * Class Message_Manager
 *
 * @package Packetery
 */
class Message_Manager {

	/**
	 * Latte engine.
	 *
	 * @var \Latte\Engine
	 */
	private $latte_engine;

	/**
	 * Messages to be displayed.
	 *
	 * @var array
	 */
	private $messages;

	/**
	 * Message_Manager constructor.
	 *
	 * @param \Latte\Engine $latte_engine Latte engine.
	 */
	public function __construct( \Latte\Engine $latte_engine ) {
		$messages = \get_transient( 'packetery_message_manager_messages' );
		if ( ! $messages ) {
			$messages = array();
		}

		$this->messages     = $messages;
		$this->latte_engine = $latte_engine;
	}

	/**
	 * Flashes messages to end user.
	 *
	 * @param string $message Text.
	 * @param string $type Type of message.
	 */
	private function flash_message( string $message, string $type = 'success' ): void {
		$message          = array(
			'type'    => $type,
			'message' => $message,
		);
		$this->messages[] = $message;

		\set_transient( 'packetery_message_manager_messages', $this->messages, 120 );
	}

	/**
	 * Shows error message to end user.
	 *
	 * @param string $message Message.
	 */
	public function flash_error_message( string $message ): void {
		$this->flash_message( $message, 'error' );
	}

	/**
	 * Renders messages.
	 */
	public function render(): void {
		foreach ( $this->messages as $message ) {
			$this->latte_engine->render(
				PACKETERY_PLUGIN_DIR . '/template/admin-notice.latte',
				array(
					'message' => $message,
				)
			);
		}
		$this->clear();
	}

	/**
	 * Delete messages.
	 */
	private function clear() {
		$this->messages = array();
		\delete_transient( 'packetery_message_manager_messages' );
	}
}
