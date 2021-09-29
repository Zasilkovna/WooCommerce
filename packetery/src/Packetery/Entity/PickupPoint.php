<?php
/**
 * Class PickupPoint
 *
 * @package Packetery\Entity
 */

namespace Packetery\Entity;

/**
 * Class PickupPoint
 *
 * @package Packetery\Entity
 */
class PickupPoint {

	/**
	 * Selected pickup point ID
	 *
	 * @var int|null
	 */
	private $id;

	/**
	 * Point name.
	 *
	 * @var string|null
	 */
	private $name;

	/**
	 * Link to official Packeta detail page.
	 *
	 * @var string|null
	 */
	private $url;

	/**
	 * Pickup point street.
	 *
	 * @var string|null
	 */
	private $street;

	/**
	 * Pickup point zip.
	 *
	 * @var string|null
	 */
	private $zip;

	/**
	 * Pickup point city.
	 *
	 * @var string|null
	 */
	private $city;

	/**
	 * Carrier pickup point id.
	 *
	 * @var string|null
	 */
	private $carrierPointId;

	/**
	 * Pickup point type.
	 *
	 * @var string|null
	 */
	private $type;

	/**
	 * PickupPoint constructor.
	 *
	 * @param int|null    $id Point id.
	 * @param string|null $type Point type.
	 * @param string|null $name Point name.
	 * @param string|null $city Point city.
	 * @param string|null $zip Point zip.
	 * @param string|null $street Point street.
	 * @param string|null $url Point url.
	 * @param string|null $carrierPointId Carrier point id.
	 */
	public function __construct(
		?int $id,
		?string $type,
		?string $name,
		?string $city,
		?string $zip,
		?string $street,
		?string $url,
		?string $carrierPointId
	) {
		$this->id             = $id;
		$this->type           = $type;
		$this->name           = $name;
		$this->city           = $city;
		$this->zip            = $zip;
		$this->street         = $street;
		$this->url            = $url;
		$this->carrierPointId = $carrierPointId;
	}

	/**
	 * Selected pickup point ID
	 *
	 * @return int|null
	 */
	public function getId(): ?int {
		return $this->id;
	}

	/**
	 * Point name.
	 *
	 * @return string|null
	 */
	public function getName(): ?string {
		return $this->name;
	}

	/**
	 * Link to official Packeta detail page.
	 *
	 * @return string|null
	 */
	public function getUrl(): ?string {
		return $this->url;
	}

	/**
	 * Gets pickup point street.
	 *
	 * @return string|null
	 */
	public function getStreet(): ?string {
		return $this->street;
	}

	/**
	 * Gets pickup point ZIP.
	 *
	 * @return string|null
	 */
	public function getZip(): ?string {
		return $this->zip;
	}

	/**
	 * Gets pickup point city.
	 *
	 * @return string|null
	 */
	public function getCity(): ?string {
		return $this->city;
	}

	/**
	 * Gets carrier pickup point id.
	 *
	 * @return string|null
	 */
	public function getCarrierPointId(): ?string {
		return $this->carrierPointId;
	}

	/**
	 * Gets pickup point type.
	 *
	 * @return string|null
	 */
	public function getType(): ?string {
		return $this->type;
	}


}
