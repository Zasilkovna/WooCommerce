<?php
/**
 * Class Order
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Core\Entity;

/**
 * Class Order
 *
 * @package Packetery\Order
 */
class PacketStatus {

	/**
	 * Status name/code.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Translated name.
	 *
	 * @var string
	 */
	public $translatedName;

	/**
	 * Whether to synchronize packets with this state at default.
	 *
	 * @var bool
	 */
	public $defaultSynchronization;

	/**
	 * PacketStatus constructor.
	 *
	 * @param string $name Status name/code.
	 * @param string $translatedName Translated name.
	 * @param bool   $defaultSynchronization Whether to synchronize packets with this state at default.
	 */
	public function __construct( string $name, string $translatedName, bool $defaultSynchronization ) {
		$this->name                   = $name;
		$this->translatedName         = $translatedName;
		$this->defaultSynchronization = $defaultSynchronization;
	}

}
