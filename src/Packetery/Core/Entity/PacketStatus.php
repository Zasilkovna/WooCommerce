<?php
/**
 * Class PacketStatus
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Entity;

/**
 * Class PacketStatus
 *
 * @package Packetery
 */
class PacketStatus {

	public const DELIVERED              = 'delivered';
	public const POSTED_BACK            = 'posted back';
	public const ARRIVED                = 'arrived';
	public const READY_FOR_PICKUP       = 'ready for pickup';
	public const DEPARTED               = 'departed';
	public const CANCELLED              = 'cancelled';
	public const RECEIVED_DATA          = 'received data';
	public const COLLECTED              = 'collected';
	public const PREPARED_FOR_DEPARTURE = 'prepared for departure';
	public const HANDED_TO_CARRIER      = 'handed to carrier';
	public const RETURNED               = 'returned';
	public const UNKNOWN                = 'unknown';

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
