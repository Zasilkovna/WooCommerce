<?php
/**
 * Class Entity
 *
 * @package Packetery\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use WC_Order;

/**
 * Class Entity
 *
 * @package Packetery\Order
 */
class Entity {
	public const META_CARRIER_ID       = 'packetery_carrier_id';
	public const META_IS_EXPORTED      = 'packetery_is_exported';
	public const META_PACKET_ID        = 'packetery_packet_id';
	public const META_IS_LABEL_PRINTED = 'packetery_is_label_printed';
	public const META_POINT_ID         = 'packetery_point_id';
	public const META_POINT_NAME       = 'packetery_point_name';
	public const META_POINT_URL        = 'packetery_point_url';
	public const META_POINT_STREET     = 'packetery_point_street';
	public const META_POINT_ZIP        = 'packetery_point_zip';
	public const META_POINT_CITY       = 'packetery_point_city';
	public const META_WEIGHT           = 'packetery_weight';
	public const META_LENGTH           = 'packetery_length';
	public const META_WIDTH            = 'packetery_width';
	public const META_HEIGHT           = 'packetery_height';
	public const META_CARRIER_NUMBER   = 'packetery_carrier_number';
	public const META_PACKET_STATUS    = 'packetery_packet_status';
}
