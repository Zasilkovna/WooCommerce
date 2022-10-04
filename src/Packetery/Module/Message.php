<?php
/**
 * Class Message
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );

namespace Packetery\Module;

/**
 * Class Message
 *
 * @package Packetery\Module
 */
class Message {

	/**
	 * Message.
	 *
	 * @var string
	 */
	private $message = '';

	/**
	 * Type.
	 *
	 * @var string
	 */
	private $type = MessageManager::TYPE_SUCCESS;

	/**
	 * Renderer.
	 *
	 * @var string
	 */
	private $renderer = MessageManager::RENDERER_WORDPRESS;

	/**
	 * Context.
	 *
	 * @var string
	 */
	private $context = '';

	/**
	 * Tells if text should be escaped.
	 *
	 * @var bool
	 */
	private $escape = true;

	/**
	 * Creates message.
	 *
	 * @return static
	 */
	public static function create(): self {
		return new self();
	}

	/**
	 * Sets text.
	 *
	 * @param string $message The message text.
	 *
	 * @return $this
	 */
	public function setText( string $message ): self {
		$this->message = $message;
		return $this;
	}

	/**
	 * Sets type.
	 *
	 * @param string $type Type.
	 *
	 * @return $this
	 */
	public function setType( string $type ): self {
		$this->type = $type;
		return $this;
	}

	/**
	 * Sets renderer.
	 *
	 * @param string $renderer Renderer.
	 *
	 * @return $this
	 */
	public function setRenderer( string $renderer ): self {
		$this->renderer = $renderer;
		return $this;
	}

	/**
	 * Sets context.
	 *
	 * @param string $context Context.
	 *
	 * @return $this
	 */
	public function setContext( string $context ): self {
		$this->context = $context;
		return $this;
	}

	/**
	 * Sets escape flag.
	 *
	 * @param bool $escape Tells if message is HTML.
	 *
	 * @return $this
	 */
	public function setEscape( bool $escape ): self {
		$this->escape = $escape;
		return $this;
	}

	/**
	 * Converts object to array.
	 *
	 * @return array
	 */
	public function toArray(): array {
		return get_object_vars( $this );
	}
}
