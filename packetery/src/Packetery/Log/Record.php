<?php

declare( strict_types=1 );


namespace Packetery\Log;

class Record {

	public const ACTION_PACKET_SENDING = 'packet-sending';
	public const ACTION_LABEL_PRINT = 'label-print';
	public const ACTION_CARRIER_LIST_UPDATE = 'carrier-list-update';
	public const ACTION_CARRIER_LABEL_PRINT = 'carrier-label-print';

	public const STATUS_SUCCESS = 'success';
	public const STATUS_ERROR = 'error';

	/**
	 * @var string
	 */
	public $action;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var array
	 */
	public $params;

	/**
	 * @var \DateTimeImmutable
	 */
	public $date;

	/**
	 * @var string
	 */
	public $status;

	/**
	 * @return string
	 */
	public function getNote(): string {
		return $this->title . ': ' . print_r( $this->params, true );
	}
}
