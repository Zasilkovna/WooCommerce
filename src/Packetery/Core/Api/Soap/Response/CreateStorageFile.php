<?php
/**
 * Class CreateStorageFile.
 *
 * @package Packetery
 */

declare(strict_types=1);

namespace Packetery\Core\Api\Soap\Response;

/**
 * Class CreateStorageFile.
 */
class CreateStorageFile extends BaseResponse {

	/**
	 * ID.
	 *
	 * @var string|null
	 */
	private $id = null;

	/**
	 * Sets ID.
	 *
	 * @param string $id ID.
	 * @return void
	 */
	public function setId( string $id ): void {
		$this->id = $id;
	}

	/**
	 * Gets ID.
	 *
	 * @return string|null
	 */
	public function getId(): ?string {
		return $this->id;
	}
}
