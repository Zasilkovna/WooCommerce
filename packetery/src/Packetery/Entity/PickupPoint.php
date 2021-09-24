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
	 * @var string|null
	 */
	private $pointId;

	/**
	 * Point name.
	 *
	 * @var string|null
	 */
	private $pointName;

	/**
	 * Link to official Packeta detail page.
	 *
	 * @var string|null
	 */
	private $pointUrl;

	/**
	 * Pickup point street.
	 *
	 * @var string|null
	 */
	private $pointStreet;

	/**
	 * Pickup point zip.
	 *
	 * @var string|null
	 */
	private $pointZip;

	/**
	 * Pickup point city.
	 *
	 * @var string|null
	 */
	private $pointCity;

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
	private $pointType;

	/**
	 * PickupPoint constructor.
	 *
	 * @param string|null $pointId Point id.
	 * @param string|null $pointType Point type.
	 * @param string|null $pointName Point name.
	 * @param string|null $pointCity Point city.
	 * @param string|null $pointZip Point zip.
	 * @param string|null $pointStreet Point street.
	 * @param string|null $pointUrl Point url.
	 * @param string|null $carrierPointId Carrier point id.
	 */
	public function __construct(
		?string $pointId,
		?string $pointType,
		?string $pointName,
		?string $pointCity,
		?string $pointZip,
		?string $pointStreet,
		?string $pointUrl,
		?string $carrierPointId
	) {
		$this->pointId        = $pointId;
		$this->pointType      = $pointType;
		$this->pointName      = $pointName;
		$this->pointCity      = $pointCity;
		$this->pointZip       = $pointZip;
		$this->pointStreet    = $pointStreet;
		$this->pointUrl       = $pointUrl;
		$this->carrierPointId = $carrierPointId;
	}

	/**
	 * Dynamically crafted point address.
	 *
	 * @return string
	 */
	public function getPointAddress(): string {
		return implode(
			', ',
			array_filter(
				[
					$this->getPointStreet(),
					implode(
						' ',
						array_filter(
							[
								$this->getPointZip(),
								$this->getPointCity(),
							]
						)
					),
				]
			)
		);
	}

	/**
	 * Selected pickup point ID
	 *
	 * @return string|null
	 */
	public function getPointId(): ?string {
		return $this->pointId;
	}

	/**
	 * Point name.
	 *
	 * @return string|null
	 */
	public function getPointName(): ?string {
		return $this->pointName;
	}

	/**
	 * Link to official Packeta detail page.
	 *
	 * @return string|null
	 */
	public function getPointUrl(): ?string {
		return $this->pointUrl;
	}

	/**
	 * Gets pickup point street.
	 *
	 * @return string|null
	 */
	private function getPointStreet(): ?string {
		return $this->pointStreet;
	}

	/**
	 * Gets pickup point ZIP.
	 *
	 * @return string|null
	 */
	private function getPointZip(): ?string {
		return $this->pointZip;
	}

	/**
	 * Gets pickup point city.
	 *
	 * @return string|null
	 */
	private function getPointCity(): ?string {
		return $this->pointCity;
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
	public function getPointType(): ?string {
		return $this->pointType;
	}


}
