<?php
/**
 * Class CreatePacket.
 *
 * @package Packetery\Api\Soap\Request
 */

declare( strict_types=1 );

namespace Packetery\Api\Soap\Request;

use Packetery\Entity;

/**
 * Class CreatePacket.
 *
 * @package Packetery\Api\Soap\Request
 */
class CreatePacket {

	/**
	 * Order id.
	 *
	 * @var string
	 */
	private $number;

	/**
	 * Customer name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Customer surname.
	 *
	 * @var string
	 */
	private $surname;

	/**
	 * Customer e-mail.
	 *
	 * @var string
	 */
	private $email;

	/**
	 * Customer phone.
	 *
	 * @var string
	 */
	private $phone;

	/**
	 * Pickup point or carrier id.
	 *
	 * @var int
	 */
	private $addressId;

	/**
	 * Order value.
	 *
	 * @var float
	 */
	private $value;

	/**
	 * Sender label.
	 *
	 * @var string
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
	 * @var string
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
	 * @var string
	 */
	private $city;

	/**
	 * Customer zip for address delivery.
	 *
	 * @var string
	 */
	private $zip;

	/**
	 * Cash on delivery value.
	 *
	 * @var float
	 */
	private $cod;

	/**
	 * Carrier pickup point.
	 *
	 * @var string
	 */
	private $carrierPickupPoint;

	/**
	 * Package size.
	 *
	 * @var array
	 */
	private $size;

	/**
	 * Packet note.
	 *
	 * @var string
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
		// Required attributes.
		$this->number    = $order->getNumber();
		$this->name      = $order->getName();
		$this->surname   = $order->getSurname();
		$this->value     = $order->getValue();
		$this->weight    = $order->getWeight();
		$this->addressId = $order->getAddressId();
		$this->eshop     = $order->getEshop();
		// Optional attributes.
		$this->adultContent = (int) $order->containsAdultContent();
		$this->cod          = $order->getCod();
		$this->email        = $order->getEmail();
		$this->note         = $order->getNote();
		$this->phone        = $order->getPhone();

		$pickupPoint = $order->getPickupPoint();
		if ( null !== $pickupPoint ) {
			$this->carrierPickupPoint = $pickupPoint->getCarrierPointId();
		}

		$address = $order->getDeliveryAddress();
		if ( null !== $address ) {
			$this->street = $address->getStreet();
			$this->city   = $address->getCity();
			$this->zip    = $address->getZip();
			if ( $address->getHouseNumber() ) {
				$this->houseNumber = $address->getHouseNumber();
			}
		}

		$size = $order->getSize();
		if ( null !== $size ) {
			$this->size = [
				'length' => $size->getLength(),
				'width'  => $size->getWidth(),
				'height' => $size->getHeight(),
			];
		}
	}

	/**
	 * Gets all properties as array.
	 *
	 * @return array
	 */
	public function __toArray(): array {
		return get_object_vars( $this );
	}

	/**
	 * Gets submittable data.
	 *
	 * @return array
	 */
	public function getSubmittableData(): array {
		$rawData = array_filter( $this->__toArray() );
		if ( isset( $rawData['adultContent'] ) ) {
			$rawData['adultContent'] = (int) $rawData['adultContent'];
		}

		return $rawData;
	}

}
