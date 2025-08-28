<?php
/**
 * Class CreatePacket.
 *
 * @package Packetery\Api\Soap\Request
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Request;

use Packetery\Core\Entity;

/**
 * Class CreatePacket.
 * We deliberately don't use this class to send data to the API, but we keep the class for possible later use.
 *
 * @package Packetery\Api\Soap\Request
 */
class CreatePacket {
	/**
	 * Order
	 *
	 * @var Entity\Order
	 */
	private $order;

	/**
	 * Order id.
	 *
	 * @var ?string
	 */
	private $number;

	/**
	 * Customer name.
	 *
	 * @var ?string
	 */
	private $name;

	/**
	 * Customer surname.
	 *
	 * @var ?string
	 */
	private $surname;

	/**
	 * Customer e-mail.
	 *
	 * @var ?string
	 */
	private $email;

	/**
	 * Customer phone.
	 *
	 * @var ?string
	 */
	private $phone;

	/**
	 * Pickup point or carrier id.
	 *
	 * @var ?int
	 */
	private $addressId;

	/**
	 * Order value.
	 *
	 * @var ?float
	 */
	private $value;

	/**
	 * Sender label.
	 *
	 * @var ?string
	 */
	private $eshop;

	/**
	 * Package weight.
	 *
	 * @var float|null
	 */
	private $weight;

	/**
	 * Customer street for address delivery.
	 *
	 * @var ?string
	 */
	private $street;

	/**
	 * Customer houseNumber for address delivery.
	 *
	 * @var string
	 */
	private $houseNumber;

	/**
	 * Customer city for address delivery.
	 *
	 * @var ?string
	 */
	private $city;

	/**
	 * Customer zip for address delivery.
	 *
	 * @var ?string
	 */
	private $zip;

	/**
	 * Cash on delivery value.
	 *
	 * @var ?float
	 */
	private $cod;

	/**
	 * Order money values currency.
	 *
	 * @var string
	 */
	private $currency;

	/**
	 * Carrier pickup point.
	 *
	 * @var ?string
	 */
	private $carrierPickupPoint;

	/**
	 * Package size.
	 *
	 * @var array<string, float|null>
	 */
	private $size;

	/**
	 * Packet note.
	 *
	 * @var ?string
	 */
	private $note;

	/**
	 * Adult content presence flag.
	 *
	 * @var int
	 */
	private $adultContent;

	/**
	 * CreatePacket constructor.
	 *
	 * @param Entity\Order $order Order entity.
	 */
	public function __construct( Entity\Order $order ) {
		$this->order = $order;

		// Required attributes.
		$this->number    = $order->getCustomNumberOrNumber();
		$this->name      = $order->getName();
		$this->surname   = $order->getSurname();
		$this->value     = $order->getFinalValue();
		$this->weight    = $order->getFinalWeight();
		$this->addressId = $order->getPickupPointOrCarrierId();
		$this->eshop     = $order->getEshop();
		// Optional attributes.
		$this->adultContent = (int) $order->containsAdultContent();
		$this->cod          = $order->getFinalCod();
		$this->currency     = $order->getCurrency();
		$this->email        = $order->getEmail();
		$this->note         = $order->getNote();
		$this->phone        = $order->getPhone();

		$pickupPoint = $order->getPickupPoint();
		if ( $pickupPoint !== null && $order->isExternalCarrier() ) {
			$this->carrierPickupPoint = $pickupPoint->getId();
		}

		if ( $order->isHomeDelivery() || $order->isCarDelivery() ) {
			$address = $order->getDeliveryAddress();
			if ( $address !== null ) {
				$this->street = $address->getStreet();
				$this->city   = $address->getCity();
				$this->zip    = $address->getZip();
				if ( $address->getHouseNumber() !== null ) {
					$this->houseNumber = $address->getHouseNumber();
				}
			}
		}

		$carrier = $order->getCarrier();
		if ( $carrier->requiresSize() ) {
			$size = $order->getSize();
			if ( $size !== null ) {
				$this->size = [
					'length' => $size->getLength(),
					'width'  => $size->getWidth(),
					'height' => $size->getHeight(),
				];
			}
		}
	}

	/**
	 * Gets submittable data.
	 *
	 * @return array<string, string|int|float|null>
	 */
	public function getSubmittableData(): array {
		$data =  array_filter( get_object_vars( $this ) );

		return apply_filters('packeta_create_packet_api_data', $data, $this->order, $this);
	}
}
