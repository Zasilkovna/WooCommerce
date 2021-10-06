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
	public const TYPE_SUCCESS = 'success';

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
	 * User ID.
	 *
	 * @var int
	 */
	private $userId;

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
		$this->userId = get_current_user_id();
		$messages     = get_transient( $this->getTransientName() );
		if ( ! $messages ) {
			$messages = [];
		}

		$this->messages = array_merge( $this->messages, $messages );
	}

	/**
	 * Returns transient name.
	 *
	 * @return string
	 */
	private function getTransientName(): string {
		return 'packetery_message_manager_messages_' . $this->userId;
	}

	/**
	 * Flashes messages to end user.
	 *
	 * @param string $message  Text.
	 * @param string $type     Type of message.
	 * @param string $renderer Renderer of message.
	 */
	public function flashMessage( string $message, string $type = 'success', string $renderer = self::RENDERER_WORDPRESS ): void {
		$message = [
			'type'     => $type,
			'message'  => $message,
			'renderer' => $renderer,
		];

		$this->messages[ sha1( wp_json_encode( $message ) . microtime() ) ] = $message;

		set_transient( $this->getTransientName(), $this->messages, self::EXPIRATION );
	}

	/**
	 * Renders messages.
	 *
	 * @param string $renderer Message renderer.
	 */
	public function render( string $renderer = self::RENDERER_WORDPRESS ): void {
		foreach ( $this->messages as $key => $message ) {
			if ( $message['renderer'] !== $renderer ) {
				continue;
			}

			$this->latteEngine->render( PACKETERY_PLUGIN_DIR . '/template/admin-notice.latte', [
				'message' => $message,
			] );

			unset( $this->messages[ $key ] );
		}

		set_transient( $this->getTransientName(), $this->messages, self::EXPIRATION );
	}
}
