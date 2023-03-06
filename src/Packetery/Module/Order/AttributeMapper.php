<?php
/**
 * Class AttributeMapper.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Entity;
use WC_Data_Exception;
use WC_Order;

/**
 * Class AttributeMapper.
 *
 * @package Packetery
 */
class AttributeMapper {

	// Business name of pickup point.

	/**
	 * Updates order entity from props to save.
	 *
	 * @param Entity\Order $orderEntity Order entity.
	 * @param array        $propsToSave Props to save.
	 *
	 * @return void
	 */
	public function toOrderEntityPickupPoint( Entity\Order $orderEntity, array $propsToSave ): void {
		$orderEntityPickupPoint = $orderEntity->getPickupPoint();
		if ( null === $orderEntityPickupPoint ) {
			$orderEntityPickupPoint = new Entity\PickupPoint();
		}

		foreach ( $propsToSave as $attrName => $attrValue ) {
			switch ( $attrName ) {
				case Attribute::ATTR_CARRIER_ID:
					$orderEntity->setCarrierId( $attrValue );
					break;
				case Attribute::ATTR_POINT_ID:
					$orderEntityPickupPoint->setId( $attrValue );
					break;
				case Attribute::ATTR_POINT_NAME:
					$orderEntityPickupPoint->setName( $attrValue );
					break;
				case Attribute::ATTR_POINT_URL:
					$orderEntityPickupPoint->setUrl( $attrValue );
					break;
				case Attribute::ATTR_POINT_STREET:
					$orderEntityPickupPoint->setStreet( $attrValue );
					break;
				case Attribute::ATTR_POINT_ZIP:
					$orderEntityPickupPoint->setZip( $attrValue );
					break;
				case Attribute::ATTR_POINT_CITY:
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
	public function toWcOrderShippingAddress( WC_Order $wcOrder, string $attributeName, string $value ): void {
		if ( Attribute::ATTR_POINT_STREET === $attributeName ) {
			$wcOrder->set_shipping_address_1( $value );
			$wcOrder->set_shipping_address_2( '' );
		}
		if ( Attribute::ATTR_POINT_PLACE === $attributeName ) {
			$wcOrder->set_shipping_company( $value );
		}
		if ( Attribute::ATTR_POINT_CITY === $attributeName ) {
			$wcOrder->set_shipping_city( $value );
		}
		if ( Attribute::ATTR_POINT_ZIP === $attributeName ) {
			$wcOrder->set_shipping_postcode( $value );
		}
	}

}
