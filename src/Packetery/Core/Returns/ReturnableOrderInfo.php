<?php
/**
 * Class ReturnableOrderInfo
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Returns;

/**
 * Immutable set of order facts evaluated by {@see ReturnEligibility}.
 *
 * Deliberately decoupled from the WooCommerce order and from {@see \Packetery\Core\Entity\Order} so the
 * eligibility engine stays framework-agnostic and unit-testable, and works for both Packeta and
 * non-Packeta orders. The module layer collects these facts from the WC order and carrier config.
 *
 * @package Packetery
 */
class ReturnableOrderInfo {

	private string $orderStatus;
	private ?\DateTimeImmutable $completedAt;
	private ?string $deliveryCountry;
	private bool $isPacketaOrder;
	private bool $isPacketDelivered;
	private bool $carrierAllowsReturns;
	private ?float $totalValue;
	private ?float $totalWeightKg;

	/** @var int[] */
	private array $categoryIds;
	private bool $hasVirtualItem;

	/**
	 * @param string                  $orderStatus          WC order status slug, without the "wc-" prefix.
	 * @param \DateTimeImmutable|null $completedAt          Moment the order became delivered/completed.
	 * @param string|null             $deliveryCountry     Delivery country as lowercase ISO 3166-1 alpha-2.
	 * @param bool                    $isPacketaOrder      Whether the order was delivered by a Packeta carrier.
	 * @param bool                    $isPacketDelivered   Whether the Packeta packet has been delivered (only meaningful for Packeta orders).
	 * @param bool                    $carrierAllowsReturns Whether the order's Packeta carrier allows returns (only meaningful for Packeta orders).
	 * @param float|null              $totalValue          Total order value incl. tax.
	 * @param float|null              $totalWeightKg       Total order weight in kg.
	 * @param int[]                   $categoryIds         Ids of all product categories in the order.
	 * @param bool                    $hasVirtualItem      Whether the order contains a virtual/downloadable product.
	 */
	public function __construct(
		string $orderStatus,
		?\DateTimeImmutable $completedAt,
		?string $deliveryCountry,
		bool $isPacketaOrder,
		bool $isPacketDelivered,
		bool $carrierAllowsReturns,
		?float $totalValue,
		?float $totalWeightKg,
		array $categoryIds,
		bool $hasVirtualItem
	) {
		$this->orderStatus          = $orderStatus;
		$this->completedAt          = $completedAt;
		$this->deliveryCountry      = $deliveryCountry;
		$this->isPacketaOrder       = $isPacketaOrder;
		$this->isPacketDelivered    = $isPacketDelivered;
		$this->carrierAllowsReturns = $carrierAllowsReturns;
		$this->totalValue           = $totalValue;
		$this->totalWeightKg        = $totalWeightKg;
		$this->categoryIds          = $categoryIds;
		$this->hasVirtualItem       = $hasVirtualItem;
	}

	public function getOrderStatus(): string {
		return $this->orderStatus;
	}

	public function getCompletedAt(): ?\DateTimeImmutable {
		return $this->completedAt;
	}

	public function getDeliveryCountry(): ?string {
		return $this->deliveryCountry;
	}

	public function isPacketaOrder(): bool {
		return $this->isPacketaOrder;
	}

	public function isPacketDelivered(): bool {
		return $this->isPacketDelivered;
	}

	public function carrierAllowsReturns(): bool {
		return $this->carrierAllowsReturns;
	}

	public function getTotalValue(): ?float {
		return $this->totalValue;
	}

	public function getTotalWeightKg(): ?float {
		return $this->totalWeightKg;
	}

	/**
	 * @return int[]
	 */
	public function getCategoryIds(): array {
		return $this->categoryIds;
	}

	public function hasVirtualItem(): bool {
		return $this->hasVirtualItem;
	}
}
