<?php
/**
 * Class Facade.
 *
 * @package Packetery
 */

namespace Packetery\Module\Order;

use Packetery\Core\Entity;
use WC_Data_Exception;
use WC_Order;

/**
 * Class Facade.
 *
 * @package Packetery
 */
class Facade {

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
	public static $pickupPointAttrs = array(
		'id'        => array(
			'name'     => self::ATTR_POINT_ID,
			'required' => true,
		),
		'name'      => array(
			'name'     => self::ATTR_POINT_NAME,
			'required' => true,
		),
		'city'      => array(
			'name'     => self::ATTR_POINT_CITY,
			'required' => true,
		),
		'zip'       => array(
			'name'     => self::ATTR_POINT_ZIP,
			'required' => true,
		),
		'street'    => array(
			'name'     => self::ATTR_POINT_STREET,
			'required' => true,
		),
		'place'     => array(
			'name'     => self::ATTR_POINT_PLACE,
			'required' => false,
		),
		'carrierId' => array(
			'name'     => self::ATTR_CARRIER_ID,
			'required' => false,
		),
		'url'       => array(
			'name'     => self::ATTR_POINT_URL,
			'required' => false,
		),
	);

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

	/**
	 * Updates order entity from props to save.
	 *
	 * @param Entity\Order $orderEntity Order entity.
	 * @param array        $propsToSave Props to save.
	 *
	 * @return void
	 */
	public function updateOrderEntityFromPropsToSave( Entity\Order $orderEntity, array $propsToSave ): void {
		$orderEntityPickupPoint = $orderEntity->getPickupPoint();
		if ( null === $orderEntityPickupPoint ) {
			$orderEntityPickupPoint = new Entity\PickupPoint();
		}

		foreach ( $propsToSave as $attrName => $attrValue ) {
			switch ( $attrName ) {
				case self::ATTR_CARRIER_ID:
					$orderEntity->setCarrierId( $attrValue );
					break;
				case self::ATTR_POINT_ID:
					$orderEntityPickupPoint->setId( $attrValue );
					break;
				case self::ATTR_POINT_NAME:
					$orderEntityPickupPoint->setName( $attrValue );
					break;
				case self::ATTR_POINT_URL:
					$orderEntityPickupPoint->setUrl( $attrValue );
					break;
				case self::ATTR_POINT_STREET:
					$orderEntityPickupPoint->setStreet( $attrValue );
					break;
				case self::ATTR_POINT_ZIP:
					$orderEntityPickupPoint->setZip( $attrValue );
					break;
				case self::ATTR_POINT_CITY:
					$orderEntityPickupPoint->setCity( $attrValue );
					break;
			}
		}

		$orderEntity->setPickupPoint( $orderEntityPickupPoint );
	}

	/**
	 * Update order shipping.
	 *
	 * @param WC_Order $wcOrder       WC Order.
	 * @param string   $attributeName Attribute name.
	 * @param string   $value         Value.
	 *
	 * @return void
	 * @throws WC_Data_Exception When shipping input is invalid.
	 */
	public function updateShippingAddressProperty( WC_Order $wcOrder, string $attributeName, string $value ): void {
		if ( self::ATTR_POINT_STREET === $attributeName ) {
			$wcOrder->set_shipping_address_1( $value );
			$wcOrder->set_shipping_address_2( '' );
		}
		if ( self::ATTR_POINT_PLACE === $attributeName ) {
			$wcOrder->set_shipping_company( $value );
		}
		if ( self::ATTR_POINT_CITY === $attributeName ) {
			$wcOrder->set_shipping_city( $value );
		}
		if ( self::ATTR_POINT_ZIP === $attributeName ) {
			$wcOrder->set_shipping_postcode( $value );
		}
	}

}
