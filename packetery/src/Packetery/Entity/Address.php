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
	 * @param string $street Street.
	 * @param string $city City.
	 * @param string $zip Zip.
	 */
	public function __construct( string $street, string $city, string $zip ) {
		$this->street = $street;
		$this->city   = $city;
		$this->zip    = $zip;
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
	 * Sets house number.
	 *
	 * @param string $houseNumber House number.
	 */
	public function setHouseNumber( string $houseNumber ): void {
		$this->houseNumber = $houseNumber;
	}
}
