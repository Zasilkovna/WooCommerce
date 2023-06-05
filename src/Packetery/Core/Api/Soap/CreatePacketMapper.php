<?php
/**
 * Class CreatePacketMapper.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap;

use Packetery\Core\Entity;
use Packetery\Core\Helper;

/**
 * Class CreatePacketMapper.
 *
 * @package Packetery
 */
class CreatePacketMapper {

	/**
	 * Helper.
	 *
	 * @var \Packetery\Core\Helper
	 */
	private $helper;

	/**
	 * CreatePacketMapper constructor.
	 *
	 * @param Helper $helper Helper.
	 */
	public function __construct( Helper $helper ) {
		$this->helper = $helper;
	}

	/**
	 * Maps order data to CreatePacket structure.
	 *
	 * @param Entity\Order $order Order entity.
	 *
	 * @return array
	 */
	public function fromOrderToArray( Entity\Order $order ): array {
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
			'deliverOn'    => $this->helper->getStringFromDateTime( $order->getDeliverOn(), Helper::DATEPICKER_FORMAT ),
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
		if ( $carrier->requiresSize() ) {
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
