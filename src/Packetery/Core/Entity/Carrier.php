<?php
/**
 * Class Carrier
 *
 * @package Packetery\Entities
 */

declare( strict_types=1 );

namespace Packetery\Core\Entity;

/**
 * Class Carrier
 *
 * @package Packetery\Entities
 */
class Carrier {

	public const INTERNAL_PICKUP_POINTS_ID    = 'packeta';
	public const VENDOR_GROUP_ZPOINT          = 'zpoint';
	public const VENDOR_GROUP_ZBOX            = 'zbox';
	public const ADDRESS_VALIDATION_COUNTRIES = [ 'cz', 'sk' ];
	public const CAR_DELIVERY_CARRIERS        = [ '25061' ];

	/**
	 * Carrier id.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Carrier name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Carrier hasPickupPoints.
	 *
	 * @var bool
	 */
	private $hasPickupPoints;

	/**
	 * Carrier hasDirectLabel.
	 *
	 * @var bool
	 */
	private $hasDirectLabel;

	/**
	 * Carrier requiresSeparateHouseNumber.
	 *
	 * @var bool
	 */
	private $requiresSeparateHouseNumber;

	/**
	 * Carrier requiresCustomsDeclarations.
	 *
	 * @var bool
	 */
	private $requiresCustomsDeclarations;

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
	 * Carrier supportsCod.
	 *
	 * @var bool
	 */
	private $supportsCod;

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
	 * @var float
	 */
	private $maxWeight;

	/**
	 * Tells if carrier is available.
	 *
	 * @var bool
	 */
	private $isAvailable;

	/**
	 * Carrier isDeleted.
	 *
	 * @var bool
	 */
	private $isDeleted;

	/**
	 * Carrier allows age verification.
	 *
	 * @var bool
	 */
	private $ageVerification;

	public function __construct(
		string $id,
		string $name,
		bool $hasPickupPoints,
		bool $hasDirectLabel,
		bool $requiresSeparateHouseNumber,
		bool $requiresCustomsDeclarations,
		bool $requiresEmail,
		bool $requiresPhone,
		bool $requiresSize,
		bool $supportsCod,
		string $country,
		string $currency,
		float $maxWeight,
		bool $isAvailable,
		bool $isDeleted,
		bool $ageVerification
	) {
		$this->id                          = $id;
		$this->name                        = $name;
		$this->hasPickupPoints             = $hasPickupPoints;
		$this->hasDirectLabel              = $hasDirectLabel;
		$this->requiresSeparateHouseNumber = $requiresSeparateHouseNumber;
		$this->requiresCustomsDeclarations = $requiresCustomsDeclarations;
		$this->requiresEmail               = $requiresEmail;
		$this->requiresPhone               = $requiresPhone;
		$this->requiresSize                = $requiresSize;
		$this->supportsCod                 = $supportsCod;
		$this->country                     = $country;
		$this->currency                    = $currency;
		$this->maxWeight                   = $maxWeight;
		$this->isAvailable                 = $isAvailable;
		$this->isDeleted                   = $isDeleted;
		$this->ageVerification             = $ageVerification;
	}

	/**
	 * Returns all properties as array.
	 *
	 * @return array<string, string|bool|float>
	 */
	public function __toArray(): array {
		return get_object_vars( $this );
	}

	/**
	 * Gets carrier id.
	 *
	 * @return string
	 */
	public function getId(): string {
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
	 * Gets carrier hasPickupPoints.
	 *
	 * @return bool
	 */
	public function hasPickupPoints(): bool {
		return $this->hasPickupPoints;
	}

	/**
	 * Gets carrier hasCarrierDirectLabel.
	 *
	 * @return bool
	 */
	public function hasDirectLabel(): bool {
		return $this->hasDirectLabel;
	}

	/**
	 * Gets carrier separateHouseNumber.
	 *
	 * @return bool
	 */
	public function requiresSeparateHouseNumber(): bool {
		return $this->requiresSeparateHouseNumber;
	}

	/**
	 * Gets carrier customsDeclarations.
	 *
	 * @return bool
	 */
	public function requiresCustomsDeclarations(): bool {
		return $this->requiresCustomsDeclarations;
	}

	/**
	 * Gets carrier requiresEmail.
	 *
	 * @return bool
	 */
	public function requiresEmail(): bool {
		return $this->requiresEmail;
	}

	/**
	 * Gets carrier requiresPhone.
	 *
	 * @return bool
	 */
	public function requiresPhone(): bool {
		return $this->requiresPhone;
	}

	/**
	 * Gets carrier requiresSize.
	 *
	 * @return bool
	 */
	public function requiresSize(): bool {
		return $this->requiresSize;
	}

	/**
	 * Gets carrier supportsCod.
	 *
	 * @return bool
	 */
	public function supportsCod(): bool {
		return $this->supportsCod;
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
	 * @return float
	 */
	public function getMaxWeight(): float {
		return $this->maxWeight;
	}

	public function isAvailable(): bool {
		return $this->isAvailable;
	}

	/**
	 * Gets carrier isDeleted.
	 *
	 * @return bool
	 */
	public function isDeleted(): bool {
		return $this->isDeleted;
	}

	/**
	 * Tells if allows age verification.
	 *
	 * @return bool
	 */
	public function supportsAgeVerification(): bool {
		return $this->ageVerification;
	}

	/**
	 * Tells if is car delivery carrier.
	 *
	 * @return bool
	 */
	public function isCarDelivery(): bool {
		return in_array( $this->id, self::CAR_DELIVERY_CARRIERS, true );
	}
}
