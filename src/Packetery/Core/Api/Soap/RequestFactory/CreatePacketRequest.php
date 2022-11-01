<?php
/**
 * Class CreatePacketRequest.
 *
 * @package Packetery\EntityFactory
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\RequestFactory;

use Packetery\Core\Entity;

/**
 * Class CreatePacketRequest.
 *
 * @package Packetery\EntityFactory
 */
class CreatePacketRequest {

	/**
	 * CreatePacketRequest constructor.
	 */
	public function __construct() {

	}

	/**
	 * Maps order data to CreatePacket structure.
	 *
	 * @param Entity\Order $order Order entity.
	 *
	 * @return array
	 */
	public function preparePacketRequestdata( Entity\Order $order ): array {
		$createPacketData = [
			// Required attributes.
			'number'       => ( $order->getCustomNumber() ?? $order->getNumber() ),
			'name'         => $order->getName(),
			'surname'      => $order->getSurname(),
			'value'        => $order->getValue(),
			'weight'       => $order->getFinalWeight(),
			'addressId'    => $order->getPickupPointOrCarrierId(),
			'eshop'        => $order->getEshop(),
			// Optional attributes.
			'adultContent' => (int) $order->containsAdultContent(),
			'cod'          => $order->getCod(),
			'currency'     => $order->getCurrency(),
			'email'        => $order->getEmail(),
			'note'         => $order->getNote(),
			'phone'        => $order->getPhone(),
		];

		$pickupPoint = $order->getPickupPoint();
		if ( null !== $pickupPoint && $order->isExternalCarrier() ) {
			$createPacketData['carrierPickupPoint'] = $pickupPoint->getId();
		}

		if ( $order->isHomeDelivery() ) {
			$address = $order->getDeliveryAddress();
			if ( null !== $address ) {
				$createPacketData['street'] = $address->getStreet();
				$createPacketData['city']   = $address->getCity();
				$createPacketData['zip']    = $address->getZip();
				if ( $address->getHouseNumber() ) {
					$createPacketData['houseNumber'] = $address->getHouseNumber();
				}
			}
		}

		$carrier = $order->getCarrier();
		if ( null !== $carrier && $carrier->requiresSize() ) {
			$size = $order->getSize();
			if ( null !== $size ) {
				$createPacketData['size'] = [
					'length' => $size->getLength(),
					'width'  => $size->getWidth(),
					'height' => $size->getHeight(),
				];
			}
		}

		return $createPacketData;
	}

}
