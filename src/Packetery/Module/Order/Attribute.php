<?php
/**
 * Class Attribute
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

/**
 * Class Attribute
 *
 * @package Packetery
 */
class Attribute {

	public const ATTR_POINT_ID     = 'packetery_point_id';
	public const ATTR_POINT_NAME   = 'packetery_point_name';
	public const ATTR_POINT_CITY   = 'packetery_point_city';
	public const ATTR_POINT_ZIP    = 'packetery_point_zip';
	public const ATTR_POINT_STREET = 'packetery_point_street';
	public const ATTR_POINT_PLACE  = 'packetery_point_place'; // Business name of pickup point.
	public const ATTR_CARRIER_ID   = 'packetery_carrier_id';
	public const ATTR_POINT_URL    = 'packetery_point_url';

	public const ATTR_ADDRESS_IS_VALIDATED = 'packetery_address_isValidated';
	public const ATTR_ADDRESS_HOUSE_NUMBER = 'packetery_address_houseNumber';
	public const ATTR_ADDRESS_STREET       = 'packetery_address_street';
	public const ATTR_ADDRESS_CITY         = 'packetery_address_city';
	public const ATTR_ADDRESS_POST_CODE    = 'packetery_address_postCode';
	public const ATTR_ADDRESS_COUNTY       = 'packetery_address_county';
	public const ATTR_ADDRESS_COUNTRY      = 'packetery_address_country';
	public const ATTR_ADDRESS_LATITUDE     = 'packetery_address_latitude';
	public const ATTR_ADDRESS_LONGITUDE    = 'packetery_address_longitude';

	/**
	 * Pickup point attributes configuration.
	 *
	 * @var array[]
	 */
	public static $pickupPointAttrs = [
		'id'        => [
			'name'     => self::ATTR_POINT_ID,
			'required' => true,
		],
		'name'      => [
			'name'     => self::ATTR_POINT_NAME,
			'required' => true,
		],
		'city'      => [
			'name'     => self::ATTR_POINT_CITY,
			'required' => true,
		],
		'zip'       => [
			'name'     => self::ATTR_POINT_ZIP,
			'required' => true,
		],
		'street'    => [
			'name'     => self::ATTR_POINT_STREET,
			'required' => true,
		],
		'place'     => [
			'name'     => self::ATTR_POINT_PLACE,
			'required' => false,
		],
		'carrierId' => [
			'name'     => self::ATTR_CARRIER_ID,
			'required' => false,
		],
		'url'       => [
			'name'     => self::ATTR_POINT_URL,
			'required' => false,
		],
	];
	/**
	 * Home delivery attributes configuration.
	 *
	 * @var array[]
	 */
	public static $homeDeliveryAttrs = [
		'isValidated' => [
			'name'                => self::ATTR_ADDRESS_IS_VALIDATED,
			// Name of checkout hidden form field. Must be unique in entire form.
			'isWidgetResultField' => false,
			// Is attribute included in widget result address? By default, it is.
		],
		'houseNumber' => [ // post type address field called 'houseNumber'.
			'name' => self::ATTR_ADDRESS_HOUSE_NUMBER,
		],
		'street'      => [
			'name' => self::ATTR_ADDRESS_STREET,
		],
		'city'        => [
			'name' => self::ATTR_ADDRESS_CITY,
		],
		'postCode'    => [
			'name'              => self::ATTR_ADDRESS_POST_CODE,
			'widgetResultField' => 'postcode',
			// Widget returns address object containing specified field. By default, it is the array key 'postCode', but in this case it is 'postcode'.
		],
		'county'      => [
			'name' => self::ATTR_ADDRESS_COUNTY,
		],
		'country'     => [
			'name' => self::ATTR_ADDRESS_COUNTRY,
		],
		'latitude'    => [
			'name' => self::ATTR_ADDRESS_LATITUDE,
		],
		'longitude'   => [
			'name' => self::ATTR_ADDRESS_LONGITUDE,
		],
	];
}
