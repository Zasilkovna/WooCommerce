<?php

declare( strict_types=1 );

namespace Packetery\Core\PickupPointProvider;

abstract class BaseProvider {

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $country;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var bool
	 */
	protected $supportsCod;

	/**
	 * @var bool
	 */
	protected $supportsAgeVerification;

	/**
	 * @var string
	 */
	protected $currency;

	/**
	 * @var bool
	 */
	protected $hasPickupPoints;

	public function __construct(
		string $id,
		string $country,
		bool $supportsCod,
		bool $supportsAgeVerification,
		string $currency,
		bool $hasPickupPoints
	) {
		$this->id                      = $id;
		$this->country                 = $country;
		$this->supportsCod             = $supportsCod;
		$this->supportsAgeVerification = $supportsAgeVerification;
		$this->currency                = $currency;
		$this->hasPickupPoints         = $hasPickupPoints;
	}

	/**
	 * Translated name setter.
	 *
	 * @param string $translatedName Translated name.
	 *
	 * @return void
	 */
	public function setTranslatedName( string $translatedName ): void {
		$this->name = $translatedName;
	}

	/**
	 * Id getter.
	 *
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * Country getter.
	 *
	 * @return string
	 */
	public function getCountry(): string {
		return $this->country;
	}

	/**
	 * Name getter.
	 *
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Currency getter.
	 *
	 * @return string
	 */
	public function getCurrency(): string {
		return $this->currency;
	}

	/**
	 * COD support getter.
	 *
	 * @return bool
	 */
	public function supportsCod(): bool {
		return $this->supportsCod;
	}

	/**
	 * Age verification getter.
	 *
	 * @return bool
	 */
	public function supportsAgeVerification(): bool {
		return $this->supportsAgeVerification;
	}

	/**
	 * Has pickup points?
	 *
	 * @return bool
	 */
	public function hasPickupPoints(): bool {
		return $this->hasPickupPoints;
	}
}
