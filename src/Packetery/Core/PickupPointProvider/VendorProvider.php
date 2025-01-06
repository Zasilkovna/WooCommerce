<?php

declare( strict_types=1 );

namespace Packetery\Core\PickupPointProvider;

class VendorProvider extends BaseProvider {

	/**
	 * @var string
	 */
	private $group;

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
