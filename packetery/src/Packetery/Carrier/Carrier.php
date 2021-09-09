<?php
/**
 * Class Carrier
 *
 * @package Packetery\Carrier
 */

namespace Packetery\Carrier;

/**
 * Class Carrier
 *
 * @package Packetery\Carrier
 */
class Carrier {
	/**
	 * Carrier id.
	 *
	 * @var int
	 */
	private $id;
	/**
	 * Carrier name.
	 *
	 * @var string
	 */
	private $name;
	/**
	 * Carrier isPickupPoints.
	 *
	 * @var bool
	 */
	private $isPickupPoints;
	/**
	 * Carrier hasCarrierDirectLabel.
	 *
	 * @var bool
	 */
	private $hasCarrierDirectLabel;
	/**
	 * Carrier separateHouseNumber.
	 *
	 * @var bool
	 */
	private $separateHouseNumber;
	/**
	 * Carrier customsDeclarations.
	 *
	 * @var bool
	 */
	private $customsDeclarations;
	/**
	 * Carrier requiresEmail.
	 *
	 * @var bool
	 */
	private $requiresEmail;
	/**
	 * Carrier requiresPhone.
	 *
	 * @var bool
	 */
	private $requiresPhone;
	/**
	 * Carrier requiresSize.
	 *
	 * @var bool
	 */
	private $requiresSize;
	/**
	 * Carrier disallowsCod.
	 *
	 * @var bool
	 */
	private $disallowsCod;
	/**
	 * Carrier country.
	 *
	 * @var string
	 */
	private $country;
	/**
	 * Carrier currency.
	 *
	 * @var string
	 */
	private $currency;
	/**
	 * Carrier maxWeight.
	 *
	 * @var bool
	 */
	private $maxWeight;
	/**
	 * Carrier deleted.
	 *
	 * @var bool
	 */
	private $deleted;

	/**
	 * Carrier constructor.
	 *
	 * @param array $carrierData Data from db.
	 */
	public function __construct( array $carrierData ) {
		$this->id                    = (int) $carrierData['id'];
		$this->name                  = $carrierData['name'];
		$this->isPickupPoints        = (bool) $carrierData['is_pickup_points'];
		$this->hasCarrierDirectLabel = (bool) $carrierData['has_carrier_direct_label'];
		$this->separateHouseNumber   = (bool) $carrierData['separate_house_number'];
		$this->customsDeclarations   = (bool) $carrierData['customs_declarations'];
		$this->requiresEmail         = (bool) $carrierData['requires_email'];
		$this->requiresPhone         = (bool) $carrierData['requires_phone'];
		$this->requiresSize          = (bool) $carrierData['requires_size'];
		$this->disallowsCod          = (bool) $carrierData['disallows_cod'];
		$this->country               = $carrierData['country'];
		$this->currency              = $carrierData['currency'];
		$this->maxWeight             = (bool) $carrierData['max_weight'];
		$this->deleted               = (bool) $carrierData['deleted'];
	}

	/**
	 * Gets carrier id.
	 *
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * Gets carrier name.
	 *
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Gets carrier isPickupPoints.
	 *
	 * @return bool
	 */
	public function getIsPickupPoints(): bool {
		return $this->isPickupPoints;
	}

	/**
	 * Gets carrier hasCarrierDirectLabel.
	 *
	 * @return bool
	 */
	public function getHasCarrierDirectLabel(): bool {
		return $this->hasCarrierDirectLabel;
	}

	/**
	 * Gets carrier separateHouseNumber.
	 *
	 * @return bool
	 */
	public function getSeparateHouseNumber(): bool {
		return $this->separateHouseNumber;
	}

	/**
	 * Gets carrier customsDeclarations.
	 *
	 * @return bool
	 */
	public function getCustomsDeclarations(): bool {
		return $this->customsDeclarations;
	}

	/**
	 * Gets carrier requiresEmail.
	 *
	 * @return bool
	 */
	public function getRequiresEmail(): bool {
		return $this->requiresEmail;
	}

	/**
	 * Gets carrier requiresPhone.
	 *
	 * @return bool
	 */
	public function getRequiresPhone(): bool {
		return $this->requiresPhone;
	}

	/**
	 * Gets carrier requiresSize.
	 *
	 * @return bool
	 */
	public function getRequiresSize(): bool {
		return $this->requiresSize;
	}

	/**
	 * Gets carrier disallowsCod.
	 *
	 * @return bool
	 */
	public function getDisallowsCod(): bool {
		return $this->disallowsCod;
	}

	/**
	 * Gets carrier country.
	 *
	 * @return string
	 */
	public function getCountry(): string {
		return $this->country;
	}

	/**
	 * Gets carrier currency.
	 *
	 * @return string
	 */
	public function getCurrency(): string {
		return $this->currency;
	}

	/**
	 * Gets carrier maxWeight.
	 *
	 * @return bool
	 */
	public function getMaxWeight(): bool {
		return $this->maxWeight;
	}

	/**
	 * Gets carrier deleted.
	 *
	 * @return bool
	 */
	public function getDeleted(): bool {
		return $this->deleted;
	}

}
