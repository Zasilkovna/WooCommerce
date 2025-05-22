<?php
// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

namespace Packetery\Module\Command;

class PacketaOrderData extends \stdClass {
	/** @var string */
	public $id;

	/** @var string */
	public $carrier_id;

	/** @var int */
	public $is_exported = 0;

	/** @var null|string */
	public $packet_id = null;

	/** @var null|string */
	public $packet_claim_id = null;

	/** @var null|string */
	public $packet_claim_password = null;

	/** @var null|string */
	public $packet_status = null;

	/** @var null|string */
	public $stored_until = null;

	/** @var int */
	public $is_label_printed = 0;

	/** @var null|string */
	public $carrier_number = null;

	/** @var int */
	public $weight = 5;

	/** @var null|string */
	public $car_delivery_id = null;

	/** @var null|string */
	public $point_id = null;

	/** @var null|string */
	public $point_name = null;

	/** @var null|string */
	public $point_url = null;

	/** @var null|string */
	public $point_street = null;

	/** @var null|string */
	public $point_zip = null;

	/** @var null|string */
	public $point_city = null;

	/** @var int */
	public $address_validated = 0;

	/** @var null|string */
	public $delivery_address = null;

	/** @var int */
	public $length = 100;

	/** @var int */
	public $width = 100;

	/** @var int */
	public $height = 100;

	/** @var null|bool */
	public $adult_content = null;

	/** @var null|int */
	public $cod = null;

	/** @var null|int */
	public $value = null;

	/** @var null|string */
	public $api_error_message = null;

	/** @var null|string */
	public $api_error_date = null;

	/** @var null|string */
	public $deliver_on = null;
}
