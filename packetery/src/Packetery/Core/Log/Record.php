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

	public const ACTION_PACKET_SENDING            = 'packet-sending';
	public const ACTION_LABEL_PRINT               = 'label-print';
	public const ACTION_CARRIER_LIST_UPDATE       = 'carrier-list-update';
	public const ACTION_CARRIER_LABEL_PRINT       = 'carrier-label-print';
	public const ACTION_CARRIER_NUMBER_RETRIEVING = 'carrier-number-retrieving';

	public const STATUS_SUCCESS = 'success';
	public const STATUS_ERROR   = 'error';

	/**
	 * Custom unique record id that is used to delete old duplicates.
	 *
	 * @var string
	 */
	public $customId;

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
	 * @var array
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
	 * @return string
	 */
	public function getNote(): string {
		return implode(
			' ',
			array_filter(
				[
					$this->title,
					( $this->params ? 'Data: ' . wp_json_encode( $this->params, ILogger::JSON_FLAGS ) : '' ),
				]
			)
		);
	}

	/**
	 * Sets custom record ID.
	 *
	 * @param string[] $values Values.
	 */
	public function setCustomId( array $values ): void {
		$this->customId = implode( '_', $values );
	}
}
