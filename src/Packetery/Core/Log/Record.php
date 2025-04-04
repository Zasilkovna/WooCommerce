<?php
/**
 * Class Record
 *
 * @package Packetery\Log
 */

declare( strict_types=1 );

namespace Packetery\Core\Log;

/**
 * Class Record
 *
 * @package Packetery\Log
 */
class Record {

	// Do not forget to add translation.
	public const ACTION_PACKET_SENDING                             = 'packet-sending';
	public const ACTION_PACKET_CLAIM_SENDING                       = 'packet-claim-sending';
	public const ACTION_LABEL_PRINT                                = 'label-print';
	public const ACTION_CARRIER_LIST_UPDATE                        = 'carrier-list-update';
	public const ACTION_CARRIER_LABEL_PRINT                        = 'carrier-label-print';
	public const ACTION_CARRIER_NUMBER_RETRIEVING                  = 'carrier-number-retrieving';
	public const ACTION_CARRIER_TABLE_NOT_CREATED                  = 'carrier-table-not-created';
	public const ACTION_ORDER_TABLE_NOT_CREATED                    = 'order-table-not-created';
	public const ACTION_CUSTOMS_DECLARATION_TABLE_NOT_CREATED      = 'customs-declaration-table-not-created';
	public const ACTION_CUSTOMS_DECLARATION_ITEM_TABLE_NOT_CREATED = 'customs-declaration-item-table-not-created';
	public const ACTION_SENDER_VALIDATION                          = 'sender-validation';
	public const ACTION_PACKET_STATUS_SYNC                         = 'packet-status-sync';
	public const ACTION_PACKET_CANCEL                              = 'packet-cancel';
	public const ACTION_PICKUP_POINT_VALIDATE                      = 'pickup-point-validate';
	public const ACTION_ORDER_STATUS_CHANGE                        = 'order-status-change';
	public const ACTION_STORED_UNTIL_CHANGE                        = 'action-stored-until-change';

	public const STATUS_SUCCESS = 'success';
	public const STATUS_ERROR   = 'error';

	/**
	 * Id.
	 *
	 * @var int|null
	 */
	public $id;

	/**
	 * Order ID.
	 *
	 * @var mixed|null
	 */
	public $orderId;

	/**
	 * Action.
	 *
	 * @var string
	 */
	public $action;

	/**
	 * Title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Params.
	 *
	 * @var array<string, mixed>
	 */
	public $params;

	/**
	 * Data.
	 *
	 * @var \DateTimeImmutable
	 */
	public $date;

	/**
	 * Status.
	 *
	 * @var string
	 */
	public $status;

	/**
	 * Note.
	 *
	 * @var string
	 */
	public $note;
}
