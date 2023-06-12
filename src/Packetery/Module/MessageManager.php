<?php
/**
 * Class Message_Manager
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Latte\Engine;

/**
 * Class Message_Manager
 *
 * @package Packetery
 */
class MessageManager {

	public const TYPE_ERROR   = 'error';
	public const TYPE_SUCCESS = 'success';
	public const TYPE_INFO    = 'info';

	public const RENDERER_WORDPRESS = 'wordpress';
	public const RENDERER_PACKETERY = 'packetery';

	private const EXPIRATION = 120;

	/**
	 * PacketeryLatte engine.
	 *
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * Messages to be displayed.
	 *
	 * @var array
	 */
	private $messages = [];

	/**
	 * Message_Manager constructor.
	 *
	 * @param Engine $latteEngine PacketeryLatte engine.
	 */
	public function __construct( Engine $latteEngine ) {
		$this->latteEngine = $latteEngine;
	}

	/**
	 * Inits manager on plugin load.
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
	 * @param string $message  Text.
	 * @param string $type     Type of message.
	 * @param string $renderer Renderer of message.
	 * @param string $context  Message context.
	 */
	public function flash_message( string $message, string $type = self::TYPE_SUCCESS, string $renderer = self::RENDERER_WORDPRESS, string $context = '' ): void {
		$message = [
			'type'     => $type,
			'message'  => $message,
			'renderer' => $renderer,
			'context'  => $context,
		];

		$this->flashMessageArray( $message );
	}

	/**
	 * Flash message.
	 *
	 * @param Message $message Message.
	 *
	 * @return void
	 */
	public function flashMessageObject( Message $message ): void {
		$this->flashMessageArray( $message->toArray() );
	}

	/**
	 * Adds message.
	 *
	 * @param array $message Message.
	 *
	 * @return void
	 */
	private function flashMessageArray( array $message ): void {
		$this->messages[] = $message;

		set_transient( $this->getTransientName(), $this->messages, self::EXPIRATION );
	}

	/**
	 * Renders messages.
	 *
	 * @param string $renderer Message renderer.
	 * @param string $context  Message context.
	 */
	public function render( string $renderer, string $context = '' ): void {
		// @codingStandardsIgnoreStart
		echo $this->renderToString( $renderer, $context );
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Renders messages.
	 *
	 * @param string $renderer Message renderer.
	 * @param string $context  Message context.
	 */
	public function renderToString( string $renderer, string $context ): string {
		$output = '';

		foreach ( $this->messages as $key => $message ) {
			if ( $message['renderer'] !== $renderer ) {
				continue;
			}

			if ( $message['context'] !== $context ) {
				continue;
			}

			$output .= $this->latteEngine->renderToString(
				PACKETERY_PLUGIN_DIR . '/template/admin-notice.latte',
				[
					'message' => $message,
				]
			);

			unset( $this->messages[ $key ] );
		}

		set_transient( $this->getTransientName(), $this->messages, self::EXPIRATION );
		return $output;
	}
}
