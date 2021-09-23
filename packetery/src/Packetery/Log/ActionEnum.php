<?php
/**
 * Class ActionEnum
 *
 * @package Packetery\Log
 */

declare( strict_types=1 );


namespace Packetery\Log;

/**
 * Class ActionEnum
 *
 * @package Packetery\Log
 */
class ActionEnum {
	public const PACKET_SENDING      = 'packet-sending';
	public const LABEL_PRINT         = 'label-print';
	public const CARRIER_LABEL_PRINT = 'carrier-label-print';
	public const CARRIER_LIST_UPDATE = 'carrier-list-update';
}
