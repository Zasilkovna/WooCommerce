<?php
/**
 * Class Message_Manager
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use PacketeryLatte\Engine;

/**
 * Class Message_Manager
 *
 * @package Packetery
 */
class MessageManager {

	public const TYPE_ERROR = 'error';

	/**
	 * PacketeryLatte engine.
	 *
	 * @var Engine
	 */
	private $latte_engine;

	/**
	 * Messages to be displayed.
	 *
	 * @var array
	 */
	private $messages = [];

	/**
	 * Message_Manager constructor.
	 *
	 * @param Engine $latte_engine PacketeryLatte engine.
	 */
	public function __construct( Engine $latte_engine ) {
		$this->latte_engine = $latte_engine;
	}

	/**
	 * Inits manager.
	 */
	public function init(): void {
		$messages = get_transient( $this->getTransientName() );
		if ( ! $messages ) {
			$messages = [];
		}

		$this->messages = $messages;
	}

	/**
	 * Gets transient name.
	 */
	private function getTransientName(): string {
		return 'packetery_message_manager_messages_' . wp_get_session_token();
	}

	/**
	 * Flashes messages to end user.
	 *
	 * @param string $message Text.
	 * @param string $type Type of message.
	 */
	public function flash_message( string $message, string $type = 'success' ): void {
		$message          = [
			'type'    => $type,
			'message' => $message,
		];
		$this->messages[] = $message;

		set_transient( $this->getTransientName(), $this->messages, 120 );
	}

	/**
	 * Renders messages.
	 */
	public function render(): void {
		foreach ( $this->messages as $message ) {
			$this->latte_engine->render(
				PACKETERY_PLUGIN_DIR . '/template/admin-notice.latte',
				[
					'message' => $message,
				]
			);
		}
		$this->clear();
	}

	/**
	 * Delete messages.
	 */
	private function clear() {
		$this->messages = [];
		delete_transient( $this->getTransientName() );
	}
}
