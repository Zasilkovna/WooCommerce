<?php

namespace Packetery\Core\Entity;

class PickupPoint {

	/** @var string|null */
	private $id;

	/** @var string|null */
	private $place;

	/** @var string|null */
	private $name;

	/** @var string|null */
	private $url;

	/** @var string|null */
	private $street;

	/** @var string|null */
	private $zip;

	/** @var string|null */
	private $city;

	public function __construct(
		?string $id = null,
		?string $place = null,
		?string $name = null,
		?string $city = null,
		?string $zip = null,
		?string $street = null,
		?string $url = null
	) {
		$this->id     = $id;
		$this->place  = $place;
		$this->name   = $name;
		$this->city   = $city;
		$this->zip    = $zip;
		$this->street = $street;
		$this->url    = $url;
	}

	public function getId(): ?string {
		return $this->id;
	}

	public function getPlace(): ?string {
		return $this->place;
	}

	public function getName(): ?string {
		return $this->name;
	}

	public function getUrl(): ?string {
		return $this->url;
	}

	public function getStreet(): ?string {
		return $this->street;
	}

	public function getZip(): ?string {
		return $this->zip;
	}

	public function getCity(): ?string {
		return $this->city;
	}

	public function getFullAddress(): string {
		$afterCommaSection = implode(
			' ',
			array_filter(
				[
					$this->city,
					$this->zip,
				]
			)
		);

		return implode(
			', ',
			array_filter(
				[
					$this->street,
					$afterCommaSection,
				]
			)
		);
	}

	public function setId( ?string $id ): void {
		$this->id = $id;
	}

	public function setPlace( ?string $place ): void {
		$this->place = $place;
	}

	public function setName( ?string $name ): void {
		$this->name = $name;
	}

	public function setUrl( ?string $url ): void {
		$this->url = $url;
	}

	public function setStreet( ?string $street ): void {
		$this->street = $street;
	}

	public function setZip( ?string $zip ): void {
		$this->zip = $zip;
	}

	public function setCity( ?string $city ): void {
		$this->city = $city;
	}
}
