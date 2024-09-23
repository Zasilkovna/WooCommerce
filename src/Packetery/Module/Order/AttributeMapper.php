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
	 * @return Entity\PickupPoint
	 */
	public function toOrderEntityPickupPoint( Entity\Order $orderEntity, array $propsToSave ): Entity\PickupPoint {
		$pickupPoint = $orderEntity->getPickupPoint();
		if ( null === $pickupPoint ) {
			$pickupPoint = new Entity\PickupPoint();
		}

		foreach ( $propsToSave as $attrName => $attrValue ) {
			switch ( $attrName ) {
				case Attribute::POINT_ID:
					$pickupPoint->setId( $attrValue );
					break;
				case Attribute::POINT_NAME:
					$pickupPoint->setName( $attrValue );
					break;
				case Attribute::POINT_URL:
					$pickupPoint->setUrl( $attrValue );
					break;
				case Attribute::POINT_STREET:
					$pickupPoint->setStreet( $attrValue );
					break;
				case Attribute::POINT_ZIP:
					$pickupPoint->setZip( $attrValue );
					break;
				case Attribute::POINT_CITY:
					$pickupPoint->setCity( $attrValue );
					break;
			}
		}

		return $pickupPoint;
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
		if ( Attribute::POINT_STREET === $attributeName ) {
			$wcOrder->set_shipping_address_1( $value );
			$wcOrder->set_shipping_address_2( '' );
		}
		if ( Attribute::POINT_PLACE === $attributeName ) {
			$wcOrder->set_shipping_company( $value );
		}
		if ( Attribute::POINT_CITY === $attributeName ) {
			$wcOrder->set_shipping_city( $value );
		}
		if ( Attribute::POINT_ZIP === $attributeName ) {
			$wcOrder->set_shipping_postcode( $value );
		}
	}

	/**
	 * Maps validated address from checkout data to WC order shipping address.
	 *
	 * @param WC_Order $wcOrder      WC order.
	 * @param array    $checkoutData Checkout data.
	 *
	 * @return void
	 */
	public function validatedAddressToWcOrderShippingAddress( WC_Order $wcOrder, array $checkoutData ): void {
		// Change all address fields except customer name and country.
		$houseNumberSuffix = $checkoutData[ Attribute::ADDRESS_HOUSE_NUMBER ] ? ' ' . $checkoutData[ Attribute::ADDRESS_HOUSE_NUMBER ] : '';
		$wcOrder->set_shipping_company( '' );
		$wcOrder->set_shipping_address_1( $checkoutData[ Attribute::ADDRESS_STREET ] . $houseNumberSuffix );
		$wcOrder->set_shipping_address_2( '' );
		$wcOrder->set_shipping_city( $checkoutData[ Attribute::ADDRESS_CITY ] );
		$wcOrder->set_shipping_state( '' );
		$wcOrder->set_shipping_postcode( $checkoutData[ Attribute::ADDRESS_POST_CODE ] );
	}

	/**
	 * From prepared properties to order Size.
	 *
	 * @param Entity\Order $order       Order.
	 * @param array        $propsToSave Prepared properties.
	 *
	 * @return Entity\Size
	 */
	public function toOrderSize( Entity\Order $order, array $propsToSave ): Entity\Size {
		$orderSize = $order->getSize();
		if ( null === $orderSize ) {
			$orderSize = new Entity\Size();
		}

		foreach ( $propsToSave as $attrName => $attrValue ) {
			switch ( $attrName ) {
				// TODO: Setting these values shouldn't be done here?
				case Form::FIELD_WEIGHT:
					$order->setWeight( $attrValue );
					break;
				case Form::FIELD_WIDTH:
					$orderSize->setWidth( $attrValue );
					break;
				case Form::FIELD_LENGTH:
					$orderSize->setLength( $attrValue );
					break;
				case Form::FIELD_HEIGHT:
					$orderSize->setHeight( $attrValue );
					break;
			}
		}

		return $orderSize;
	}

	/**
	 * From post data to validated address both in frontend and backend.
	 *
	 * @param array $values Data from form.
	 *
	 * @return Entity\Address
	 */
	public function toValidatedAddress( array $values ): Entity\Address {
		$address = $this->createAddress( $values );
		$address->setHouseNumber( $values[ Attribute::ADDRESS_HOUSE_NUMBER ] );
		$address->setCounty( $values[ Attribute::ADDRESS_COUNTY ] );
		$address->setLatitude( $values[ Attribute::ADDRESS_LATITUDE ] );
		$address->setLongitude( $values[ Attribute::ADDRESS_LONGITUDE ] );

		return $address;
	}

	/**
	 * From post data to validated address both in frontend and backend.
	 *
	 * @param array $values Data from form.
	 *
	 * @return Entity\Address
	 */
	public function toCarDeliveryAddress( array $values ): Entity\Address {
		$address = $this->createAddress( $values );
		$address->setHouseNumber( $values[ Attribute::ADDRESS_HOUSE_NUMBER ] );

		return $address;
	}

	/**
	 * Creates new Address
	 *
	 * @param array $values Data from form.
	 *
	 * @return Entity\Address
	 */
	private function createAddress( array $values ): Entity\Address {
		return new Entity\Address(
			$values[ Attribute::ADDRESS_STREET ],
			$values[ Attribute::ADDRESS_CITY ],
			$values[ Attribute::ADDRESS_POST_CODE ]
		);
	}

}
