<?php
/**
 * Class ReturnSettings
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Returns;

/**
 * Immutable value object holding the order-level return eligibility restrictions.
 *
 * Only the restriction settings consumed by {@see ReturnEligibility} live here. Workflow flags
 * (service enabled, allow guest, approve before send) are handled at the module/gate level, not by
 * the pure eligibility engine. Whether a Packeta carrier allows returns is configured per carrier,
 * not here — {@see ReturnableOrderInfo::carrierAllowsReturns()}.
 *
 * @package Packetery
 */
class ReturnSettings {

	/**
	 * Countries with Packeta internal pickup points (lowercase ISO 3166-1 alpha-2). A return is
	 * physically handed over at a pickup point, so only these countries are serviceable. Used to gate
	 * non-Packeta orders; Packeta orders are gated by the per-carrier flag.
	 */
	public const SERVICED_COUNTRIES = [ 'cz', 'sk', 'hu', 'ro' ];

	public const RETURN_WINDOW_DAYS_DEFAULT = 14;

	public const EXCLUDE_VIRTUAL_DEFAULT = true;

	private int $returnWindowDays;
	private bool $excludeVirtual;
	private ?float $maxOrderValue;
	private ?float $maxWeightKg;

	/** @var string[] */
	private array $allowedCountries;

	/** @var int[] */
	private array $excludedCategories;

	/**
	 * @param int        $returnWindowDays   Days after delivery a return can be created.
	 * @param bool       $excludeVirtual     Whether an order with a virtual/downloadable product is excluded.
	 * @param float|null $maxOrderValue      Maximum total order value incl. tax; null means no limit.
	 * @param float|null $maxWeightKg        Maximum total order weight in kg; null means no limit.
	 * @param string[]   $allowedCountries   Whitelist of delivery countries (ISO2) for non-Packeta orders; empty means all serviced.
	 * @param int[]      $excludedCategories Blacklist of product category ids.
	 */
	public function __construct(
		int $returnWindowDays,
		bool $excludeVirtual,
		?float $maxOrderValue,
		?float $maxWeightKg,
		array $allowedCountries,
		array $excludedCategories
	) {
		$this->returnWindowDays   = $returnWindowDays;
		$this->excludeVirtual     = $excludeVirtual;
		$this->maxOrderValue      = $maxOrderValue;
		$this->maxWeightKg        = $maxWeightKg;
		$this->allowedCountries   = $allowedCountries;
		$this->excludedCategories = $excludedCategories;
	}

	public function getReturnWindowDays(): int {
		return $this->returnWindowDays;
	}

	public function isExcludeVirtual(): bool {
		return $this->excludeVirtual;
	}

	public function getMaxOrderValue(): ?float {
		return $this->maxOrderValue;
	}

	public function getMaxWeightKg(): ?float {
		return $this->maxWeightKg;
	}

	/**
	 * @return string[]
	 */
	public function getAllowedCountries(): array {
		return $this->allowedCountries;
	}

	/**
	 * @return int[]
	 */
	public function getExcludedCategories(): array {
		return $this->excludedCategories;
	}
}
