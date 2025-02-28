<?php
/**
 * Class PacketStatus
 *
 * @package Packetery\Api\Soap\Request
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Response;

use Packetery\Core\CoreHelper;

/**
 * Class PacketStatus
 *
 * @package Packetery\Api\Soap\Request
 */
class PacketStatus extends BaseResponse {

	/**
	 * Code text.
	 *
	 * @var string
	 */
	private $codeText;

	/**
	 * The last possible day to pick up the packet.
	 *
	 * @var \DateTimeImmutable|null
	 */
	private $storedUntil;

	/**
	 * Gets code text.
	 *
	 * @return string
	 */
	public function getCodeText(): string {
		return $this->codeText;
	}

	/**
	 * Sets code text.
	 *
	 * @param string $codeText Code text.
	 *
	 * @return void
	 */
	public function setCodeText( string $codeText ): void {
		$this->codeText = $codeText;
	}

	/**
	 * Gets stored until.
	 *
	 * @return \DateTimeImmutable|null
	 */
	public function getStoredUntil(): ?\DateTimeImmutable {
		return $this->storedUntil;
	}

	/**
	 * Sets stored until.
	 *
	 * @param string|null $storedUntil Stored until.
	 *
	 * @return void
	 */
	public function setStoredUntil( ?string $storedUntil ): void {
		if ( $storedUntil === null ) {
			$this->storedUntil = null;

			return;
		}

		$formattedStoredUntil = \DateTimeImmutable::createFromFormat( CoreHelper::MYSQL_DATE_FORMAT, $storedUntil );
		$this->storedUntil    = $formattedStoredUntil instanceof \DateTimeImmutable ? $formattedStoredUntil : null;
	}
}
