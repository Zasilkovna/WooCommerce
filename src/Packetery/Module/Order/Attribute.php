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

	public const POINT_ID     = 'packetery_point_id';
	public const POINT_NAME   = 'packetery_point_name';
	public const POINT_CITY   = 'packetery_point_city';
	public const POINT_ZIP    = 'packetery_point_zip';
	public const POINT_STREET = 'packetery_point_street';
	public const POINT_PLACE  = 'packetery_point_place'; // Business name of pickup point.
	public const CARRIER_ID   = 'packetery_carrier_id';
	public const POINT_URL    = 'packetery_point_url';

	public const ADDRESS_IS_VALIDATED = 'packetery_address_isValidated';
	public const ADDRESS_HOUSE_NUMBER = 'packetery_address_houseNumber';
	public const ADDRESS_STREET       = 'packetery_address_street';
	public const ADDRESS_CITY         = 'packetery_address_city';
	public const ADDRESS_POST_CODE    = 'packetery_address_postCode';
	public const ADDRESS_COUNTY       = 'packetery_address_county';
	public const ADDRESS_COUNTRY      = 'packetery_address_country';
	public const ADDRESS_LATITUDE     = 'packetery_address_latitude';
	public const ADDRESS_LONGITUDE    = 'packetery_address_longitude';

	public const CAR_DELIVERY_ID        = 'packetery_car_delivery_id';
	public const EXPECTED_DELIVERY_FROM = 'packetery_car_delivery_from';
	public const EXPECTED_DELIVERY_TO   = 'packetery_car_delivery_to';

	/**
	 * Pickup point attributes configuration.
	 *
	 * @var array[]
	 */
	public static $pickupPointAttrs = [
		'id'        => [
			'name'     => self::POINT_ID,
			'required' => true,
		],
		'name'      => [
			'name'     => self::POINT_NAME,
			'required' => false,
		],
		'city'      => [
			'name'     => self::POINT_CITY,
			'required' => false,
		],
		'zip'       => [
			'name'     => self::POINT_ZIP,
			'required' => false,
		],
		'street'    => [
			'name'     => self::POINT_STREET,
			'required' => false,
		],
		'place'     => [
			'name'     => self::POINT_PLACE,
			'required' => false,
		],
		'carrierId' => [
			'name'     => self::CARRIER_ID,
			'required' => false,
		],
		'url'       => [
			'name'     => self::POINT_URL,
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
			'name'                => self::ADDRESS_IS_VALIDATED,
			// Name of checkout hidden form field. Must be unique in entire form.
			'isWidgetResultField' => false,
			// Is attribute included in widget result address? By default, it is.
		],
		'houseNumber' => [
			'name' => self::ADDRESS_HOUSE_NUMBER,
		],
		'street'      => [
			'name' => self::ADDRESS_STREET,
		],
		'city'        => [
			'name' => self::ADDRESS_CITY,
		],
		'postCode'    => [
			'name'              => self::ADDRESS_POST_CODE,
			'widgetResultField' => 'postcode',
			// Widget returns address object containing specified field. By default, it is the array key 'postCode', but in this case it is 'postcode'.
		],
		'county'      => [
			'name' => self::ADDRESS_COUNTY,
		],
		'country'     => [
			'name' => self::ADDRESS_COUNTRY,
		],
		'latitude'    => [
			'name' => self::ADDRESS_LATITUDE,
		],
		'longitude'   => [
			'name' => self::ADDRESS_LONGITUDE,
		],
	];

	/**
	 * Car delivery attributes configuration.
	 *
	 * @var array[]
	 */
	public static $carDeliveryAttrs = [
		'carDeliveryId'           => [
			'name'                => self::CAR_DELIVERY_ID,
			'isWidgetResultField' => false,
		],
		'street'                  => [
			'name' => self::ADDRESS_STREET,
		],
		'houseNumber'             => [
			'name' => self::ADDRESS_HOUSE_NUMBER,
		],
		'city'                    => [
			'name' => self::ADDRESS_CITY,
		],
		'postalCode'              => [
			'name' => self::ADDRESS_POST_CODE,
		],
		'country'                 => [
			'name' => self::ADDRESS_COUNTRY,
		],
		'expectedDeliveryDayFrom' => [
			'name'                => self::EXPECTED_DELIVERY_FROM,
			'isWidgetResultField' => false,
		],
		'expectedDeliveryDayTo'   => [
			'name'                => self::EXPECTED_DELIVERY_TO,
			'isWidgetResultField' => false,
		],
	];
}
