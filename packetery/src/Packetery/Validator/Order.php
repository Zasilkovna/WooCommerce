<?php
/**
 * Class Order
 *
 * @package Packetery\Validator
 */

declare( strict_types=1 );

namespace Packetery\Validator;

use Packetery\Entity;

/**
 * Class Order
 *
 * @package Packetery\Validator
 */
class Order {

	/**
	 * Validates data needed to submit packet.
	 *
	 * @param Entity\Order $order Order entity.
	 *
	 * @return bool
	 */
	public function validate( Entity\Order $order ): bool {
		// TODO: validatePickupPoint?
		return (
			$order->getNumber() &&
			$order->getName() &&
			$order->getSurname() &&
			$order->getValue() &&
			$order->getWeight() &&
			$order->getAddressId() &&
			$order->getEshop() &&
			$this->validateAddress( $order ) &&
			$this->validateSize( $order )
		);
	}

	/**
	 * Validates delivery address if needed.
	 *
	 * @param Entity\Order $order Order entity.
	 *
	 * @return bool
	 */
	private function validateAddress( Entity\Order $order ): bool {
		if ( $order->isHomeDelivery() ) {
			$address = $order->getDeliveryAddress();
			if ( null === $address ) {
				return false;
			}

			return ( $address->getStreet() && $address->getCity() && $address->getZip() );
		}

		return true;
	}

	/**
	 * Validates size if needed.
	 *
	 * @param Entity\Order $order Order entity.
	 *
	 * @return bool
	 */
	private function validateSize( Entity\Order $order ): bool {
		if ( $order->carrierRequiresSize() ) {
			$size = $order->getSize();
			if ( null === $size ) {
				return false;
			}

			return ( $size->getLength() && $size->getWidth() && $size->getHeight() );
		}

		return true;
	}
}
