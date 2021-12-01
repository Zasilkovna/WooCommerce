<?php
/**
 * Class BarcodePng
 *
 * @package Packetery\Core\Api\Soap\Response
 */

declare( strict_types=1 );


namespace Packetery\Core\Api\Soap\Response;

/**
 * Class BarcodePng
 *
 * @package Packetery\Core\Api\Soap\Response
 */
class BarcodePng extends BaseResponse {

	/**
	 * Image.
	 *
	 * @var string|null
	 */
	private $image;

	/**
	 * Image.
	 *
	 * @return string|null
	 */
	public function getImage(): ?string {
		return $this->image;
	}

	/**
	 * Sets image.
	 *
	 * @param string|null $image
	 */
	public function setImage( ?string $image ): void {
		$this->image = $image;
	}
}
