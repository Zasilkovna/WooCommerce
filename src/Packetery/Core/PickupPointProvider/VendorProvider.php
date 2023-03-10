<?php
/**
 * Class VendorProvider
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\PickupPointProvider;

/**
 * Class VendorProvider
 *
 * @package Packetery
 */
class VendorProvider extends BaseProvider {

	/**
	 * Group.
	 *
	 * @var string
	 */
	private $group;

	/**
	 * VendorProvider constructor.
	 *
	 * BaseProvider constructor.
	 *
	 * @param string $id                      Id.
	 * @param string $country                 Country.
	 * @param bool   $supportsCod             Supports COD.
	 * @param bool   $supportsAgeVerification Supports age verification.
	 * @param string $currency                Currency.
	 * @param bool   $hasPickupPoints         Is pickup points?.
	 * @param string $group                   Group.
	 */
	public function __construct(
		string $id,
		string $country,
		bool $supportsCod,
		bool $supportsAgeVerification,
		string $currency,
		bool $hasPickupPoints,
		string $group
	) {
		parent::__construct(
			$id,
			$country,
			$supportsCod,
			$supportsAgeVerification,
			$currency,
			$hasPickupPoints
		);
		$this->group = $group;
	}

	/**
	 * Group getter.
	 *
	 * @return string
	 */
	public function getGroup(): string {
		return $this->group;
	}

}
