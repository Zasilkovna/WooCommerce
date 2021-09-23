<?php
/**
 * Class CreatePacket.
 *
 * @package Packetery\Api\Soap\Request
 */

declare( strict_types=1 );

namespace Packetery\Api\Soap\Request;

use Packetery\Entity\Address;
use Packetery\Entity\Size;

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
	 * @param string|null $number Order id.
	 * @param string|null $name Customer name.
	 * @param string|null $surname Customer surname.
	 * @param float|null  $value Order value.
	 * @param float|null  $weight Packet weight.
	 * @param int|null    $addressId Carrier or pickup point id.
	 * @param string|null $eshop Sender label.
	 */
	public function __construct(
		?string $number,
		?string $name,
		?string $surname,
		?float $value,
		?float $weight,
		?int $addressId,
		?string $eshop
	) {
		$this->number    = $number;
		$this->name      = $name;
		$this->surname   = $surname;
		$this->value     = $value;
		$this->weight    = $weight;
		$this->addressId = $addressId;
		$this->eshop     = $eshop;
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
		return array_filter( $this->__toArray() );
	}

	/**
	 * Sets number.
	 *
	 * @param string $number Number.
	 */
	public function setNumber( string $number ): void {
		$this->number = $number;
	}

	/**
	 * Sets phone.
	 *
	 * @param string $name Name.
	 */
	public function setName( string $name ): void {
		$this->name = $name;
	}

	/**
	 * Sets surname.
	 *
	 * @param string $surname Surname.
	 */
	public function setSurname( string $surname ): void {
		$this->surname = $surname;
	}

	/**
	 * Sets e-mail.
	 *
	 * @param string $email E-mail.
	 */
	public function setEmail( string $email ): void {
		$this->email = $email;
	}

	/**
	 * Sets phone.
	 *
	 * @param string $phone Phone.
	 */
	public function setPhone( string $phone ): void {
		$this->phone = $phone;
	}

	/**
	 * Sets address id.
	 *
	 * @param int $addressId Address id.
	 */
	public function setAddressId( int $addressId ): void {
		$this->addressId = $addressId;
	}

	/**
	 * Sets value.
	 *
	 * @param float $value Value.
	 */
	public function setValue( float $value ): void {
		$this->value = $value;
	}

	/**
	 * Sets sender label.
	 *
	 * @param string $eshop Sender label.
	 */
	public function setEshop( string $eshop ): void {
		$this->eshop = $eshop;
	}

	/**
	 * Sets weight.
	 *
	 * @param ?float $weight Weight.
	 */
	public function setWeight( ?float $weight ): void {
		$this->weight = $weight;
	}

	/**
	 * Sets street.
	 *
	 * @param string $street Street.
	 */
	public function setStreet( string $street ): void {
		$this->street = $street;
	}

	/**
	 * Sets city.
	 *
	 * @param string $city City.
	 */
	public function setCity( string $city ): void {
		$this->city = $city;
	}

	/**
	 * Sets zip.
	 *
	 * @param string $zip Zip.
	 */
	public function setZip( string $zip ): void {
		$this->zip = $zip;
	}

	/**
	 * Sets COD.
	 *
	 * @param float $cod COD.
	 */
	public function setCod( float $cod ): void {
		$this->cod = $cod;
	}

	/**
	 * Sets carrier pickup point.
	 *
	 * @param string $carrierPickupPoint Carrier pickup point.
	 */
	public function setCarrierPickupPoint( string $carrierPickupPoint ): void {
		$this->carrierPickupPoint = $carrierPickupPoint;
	}

	/**
	 * Sets address.
	 *
	 * @param Address $address Address.
	 */
	public function setAddress( Address $address ): void {
		$this->street = $address->getStreet();
		$this->city   = $address->getCity();
		$this->zip    = $address->getZip();
		if ( $address->getHouseNumber() ) {
			$this->houseNumber = $address->getHouseNumber();
		}
	}

	/**
	 * Sets size.
	 *
	 * @param Size $size Size.
	 */
	public function setSize( Size $size ): void {
		$this->size = [
			'lenght' => $size->getLength(),
			'width'  => $size->getWidth(),
			'height' => $size->getHeight(),
		];
	}

	/**
	 * Sets packet note.
	 *
	 * @param string $note Packet note.
	 */
	public function setNote( string $note ): void {
		$this->note = $note;
	}

	/**
	 * Sets adult content presence flag.
	 *
	 * @param int $adultContent Adult content presence flag.
	 */
	public function setAdultContent( int $adultContent ): void {
		$this->adultContent = $adultContent;
	}

	/**
	 * Gets order id.
	 *
	 * @return string|null
	 */
	public function getNumber(): ?string {
		return $this->number;
	}

	/**
	 * Gets customer name.
	 *
	 * @return string|null
	 */
	public function getName(): ?string {
		return $this->name;
	}

	/**
	 * Gets customer surname.
	 *
	 * @return string|null
	 */
	public function getSurname(): ?string {
		return $this->surname;
	}

	/**
	 * Gets order value.
	 *
	 * @return float|null
	 */
	public function getValue(): ?float {
		return $this->value;
	}

	/**
	 * Gets order weight.
	 *
	 * @return float|null
	 */
	public function getWeight(): ?float {
		return $this->weight;
	}

	/**
	 * Gets carrier or internal pickup point id.
	 *
	 * @return int|null
	 */
	public function getAddressId(): ?int {
		return $this->addressId;
	}

	/**
	 * Gets sender label.
	 *
	 * @return string|null
	 */
	public function getEshop(): ?string {
		return $this->eshop;
	}
}
