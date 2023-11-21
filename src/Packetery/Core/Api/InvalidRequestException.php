<?php
/**
 * Class InvalidRequestException.
 *
 * @package Packetery\Api
 */

declare( strict_types=1 );

namespace Packetery\Core\Api;

/**
 * Class InvalidRequestException.
 *
 * @package Packetery\Api
 */
class InvalidRequestException extends \Exception {

	/**
	 * Error messages.
	 *
	 * @var string[]
	 */
	private $messages;

	/**
	 * InvalidRequestException.
	 *
	 * @param string   $mainMessage   Main message.
	 * @param string[] $errorMessages Array of messages.
	 */
	public function __construct( string $mainMessage, array $errorMessages ) {
		parent::__construct( $mainMessage );
		$this->messages = $errorMessages;
	}

	/**
	 * Messages getter.
	 *
	 * @return string[]
	 */
	public function getMessages(): array {
		return $this->messages;
	}

}
