<?php
/**
 * Class CreatePacket.
 *
 * @package Packetery\Api\Soap\Request
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Request;

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
	 * Order money values currency.
	 *
	 * @var string
	 */
	private $currency;

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
	 * @param array $createPacketData CreatePacket Data.
	 */
	public function __construct( array $createPacketData ) {
		// Required attributes.
		$this->number    = $createPacketData['number'];
		$this->name      = $createPacketData['name'];
		$this->surname   = $createPacketData['surname'];
		$this->value     = $createPacketData['value'];
		$this->weight    = $createPacketData['weight'];
		$this->addressId = $createPacketData['addressId'];
		$this->eshop     = $createPacketData['eshop'];
		// Optional attributes.
		$this->adultContent = $createPacketData['adultContent'];
		$this->cod          = $createPacketData['cod'];
		$this->currency     = $createPacketData['currency'];
		$this->email        = $createPacketData['email'];
		$this->note         = $createPacketData['note'];
		$this->phone        = $createPacketData['phone'];

		if ( ! empty( $createPacketData['carrierPickupPoint'] ) ) {
			$this->carrierPickupPoint = $createPacketData['carrierPickupPoint'];
		}
		if ( ! empty( $createPacketData['street'] ) ) {
			$this->street = $createPacketData['street'];
		}
		if ( ! empty( $createPacketData['city'] ) ) {
			$this->city = $createPacketData['city'];
		}
		if ( ! empty( $createPacketData['zip'] ) ) {
			$this->zip = $createPacketData['zip'];
		}
		if ( ! empty( $createPacketData['houseNumber'] ) ) {
			$this->houseNumber = $createPacketData['houseNumber'];
		}
		if ( ! empty( $createPacketData['size'] ) ) {
			$this->size = $createPacketData['size'];
		}
	}

	/**
	 * Gets submittable data.
	 *
	 * @return array
	 */
	public function getSubmittableData(): array {
		return array_filter( get_object_vars( $this ) );
	}

}
