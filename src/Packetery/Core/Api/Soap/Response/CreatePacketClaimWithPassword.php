<?php
/**
 * Class CreatePacket.
 *
 * @package Packetery\Api\Soap\Response
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Response;

/**
 * Class CreatePacket.
 *
 * @package Packetery\Api\Soap\Response
 */
class CreatePacketClaimWithPassword extends BaseResponse {

	/**
	 * Packet ID.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Password.
	 *
	 * @var string
	 */
	private $password;

	/**
	 * Gets ID.
	 *
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * Sets ID.
	 *
	 * @param string $id ID.
	 *
	 * @return void
	 */
	public function setId( string $id ): void {
		$this->id = $id;
	}

	/**
	 * Gets password.
	 *
	 * @return string
	 */
	public function getPassword(): string {
		return $this->password;
	}

	/**
	 * Sets password.
	 *
	 * @param string $password Password.
	 *
	 * @return void
	 */
	public function setPassword( string $password ): void {
		$this->password = $password;
	}
}
