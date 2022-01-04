<?php

declare( strict_types=1 );


namespace Packetery\Core\Api\Soap\Request;

class SenderGetReturnRouting {

	/**
	 * @var string
	 */
	private $senderLabel;

	/**
	 * @param string $senderLabel
	 */
	public function __construct( string $senderLabel ) {
		$this->senderLabel = $senderLabel;
	}

	/**
	 * @return string
	 */
	public function getSenderLabel(): string {
		return $this->senderLabel;
	}
}
