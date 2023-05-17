<?php
/**
 * Class CreateStorageFile.
 *
 * @package Packetery
 */

declare(strict_types=1);

namespace Packetery\Core\Api\Soap\Request;

/**
 * Class CreateStorageFile.
 */
class CreateStorageFile {

	/**
	 * Content.
	 *
	 * @var callable
	 */
	private $content;

	/**
	 * Name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * @param callable $content
	 * @param string   $name
	 */
	public function __construct( callable $content, string $name ) {
		$this->content = $content;
		$this->name    = $name;
	}

	/**
	 * Gets content.
	 *
	 * @return string
	 */
	public function getContent(): string {
		return call_user_func( $this->content );
	}

	/**
	 * Gets name.
	 *
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}
}
