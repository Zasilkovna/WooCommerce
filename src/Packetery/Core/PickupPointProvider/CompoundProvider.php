<?php
/**
 * Class CompoundProvider
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\PickupPointProvider;

/**
 * Class CompoundProvider
 *
 * @package Packetery
 */
class CompoundProvider extends BaseProvider {

	/**
	 * TODO: consider vendorIds.
	 *
	 * @var string[]
	 */
	private $vendorCodes;

	/**
	 * CompoundProvider constructor.
	 *
	 * @param string   $id                      Id.
	 * @param string   $country                 Country.
	 * @param bool     $supportsCod             Supports COD.
	 * @param bool     $supportsAgeVerification Supports age verification.
	 * @param string   $currency                Currency.
	 * @param bool     $hasPickupPoints         Is pickup points?.
	 * @param string[] $vendorCodes             Vendor codes.
	 */
	public function __construct(
		string $id,
		string $country,
		bool $supportsCod,
		bool $supportsAgeVerification,
		string $currency,
		bool $hasPickupPoints,
		array $vendorCodes
	) {
		parent::__construct(
			$id,
			$country,
			$supportsCod,
			$supportsAgeVerification,
			$currency,
			$hasPickupPoints
		);
		$this->vendorCodes = $vendorCodes;
	}

	/**
	 * Vendor codes getter.
	 *
	 * @return string[]
	 */
	public function getVendorCodes(): array {
		return $this->vendorCodes;
	}

}
