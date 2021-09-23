<?php
/**
 * Class Address.
 *
 * @package Packetery\Entity
 */

declare( strict_types=1 );

namespace Packetery\Entity;

/**
 * Class Address.
 *
 * @package Packetery\Entity
 */
class Address {

	/**
	 * Customer street for address delivery.
	 *
	 * @var string
	 */
	private $street;

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
	 * Customer house number.
	 *
	 * @var string
	 */
	private $houseNumber;

	/**
	 * Address constructor.
	 *
	 * @param string|null $street Street.
	 * @param string|null $city City.
	 * @param string|null $zip Zip.
	 */
	public function __construct( ?string $street, ?string $city, ?string $zip ) {
		$this->street = $street;
		$this->city   = $city;
		$this->zip    = $zip;
	}

	/**
	 * Gets street.
	 *
	 * @return string|null
	 */
	public function getStreet(): ?string {
		return $this->street;
	}

	/**
	 * Gets city.
	 *
	 * @return string|null
	 */
	public function getCity(): ?string {
		return $this->city;
	}

	/**
	 * Gets zip.
	 *
	 * @return string|null
	 */
	public function getZip(): ?string {
		return $this->zip;
	}

	/**
	 * Gets house number.
	 *
	 * @return string|null
	 */
	public function getHouseNumber(): ?string {
		return $this->houseNumber;
	}

	/**
	 * Sets house number.
	 *
	 * @param string $houseNumber House number.
	 */
	public function setHouseNumber( string $houseNumber ): void {
		$this->houseNumber = $houseNumber;
	}
}
