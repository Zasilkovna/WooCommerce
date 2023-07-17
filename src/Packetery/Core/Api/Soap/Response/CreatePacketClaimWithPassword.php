<?php
/**
 * Class CreatePacket.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Response;

/**
 * Class CreatePacket.
 *
 * @package Packetery
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
	 * Validation errors.
	 *
	 * @var string[]
	 */
	private $validationErrors;

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

	/**
	 * Sets errors.
	 *
	 * @param string[] $errors Errors.
	 */
	public function setValidationErrors( array $errors ): void {
		$this->validationErrors = $errors;
	}

	/**
	 * Gets validation errors.
	 *
	 * @return string[]
	 */
	public function getValidationErrors(): array {
		return $this->validationErrors;
	}
}
