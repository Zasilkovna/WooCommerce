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
	 * @var string
	 */
	private $content;

	/**
	 * Name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Constructor.
	 *
	 * @param string $content Content.
	 * @param string $name Name.
	 */
	public function __construct( string $content, string $name ) {
		$this->content = $content;
		$this->name    = $name;
	}

	/**
	 * Gets content.
	 *
	 * @return string
	 */
	public function getContent(): string {
		return $this->content;
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
