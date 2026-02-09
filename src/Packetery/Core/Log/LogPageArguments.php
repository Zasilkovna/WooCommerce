<?php

declare( strict_types=1 );

namespace Packetery\Core\Log;

class LogPageArguments {

	private ?int $orderId;
	private ?string $action;
	private ?string $status;
	private ?string $search;

	/**
	 * @var array<array<string, string>>|null
	 */
	private ?array $dateQuery;

	/**
	 * @var array<string, string>|null
	 */
	private ?array $orderBy;
	private ?int $limit;
	private ?int $offset;
	private bool $useExactTimes = false;

	/**
	 * @param int|null                          $orderId
	 * @param string|null                       $action
	 * @param string|null                       $status
	 * @param string|null                       $search
	 * @param array<array<string, string>>|null $dateQuery
	 */
	public function __construct(
		?int $orderId = null,
		?string $action = null,
		?string $status = null,
		?string $search = null,
		?array $dateQuery = null
	) {
		$this->orderId   = $orderId;
		$this->action    = $action;
		$this->status    = $status;
		$this->search    = $search;
		$this->dateQuery = $dateQuery;
	}

	public function getOrderId(): ?int {
		return $this->orderId;
	}

	public function getAction(): ?string {
		return $this->action;
	}

	public function getStatus(): ?string {
		return $this->status;
	}

	public function getSearch(): ?string {
		return $this->search;
	}

	/**
	 * @return array<array<string, string>>|null
	 */
	public function getDateQuery(): ?array {
		return $this->dateQuery;
	}

	/**
	 * @param array<array<string, string>>|null $dateQuery
	 */
	public function setDateQuery( ?array $dateQuery ): void {
		$this->dateQuery = $dateQuery;
	}

	/**
	 * @return array<string, string>|null
	 */
	public function getOrderBy(): ?array {
		return $this->orderBy;
	}

	/**
	 * @param array<string, string>|null $orderBy
	 */
	public function setOrderBy( ?array $orderBy ): void {
		$this->orderBy = $orderBy;
	}

	public function getLimit(): ?int {
		return $this->limit;
	}

	public function setLimit( ?int $limit ): void {
		$this->limit = $limit;
	}

	public function getOffset(): ?int {
		return $this->offset;
	}

	public function setOffset( ?int $offset ): void {
		$this->offset = $offset;
	}

	public function getUseExactTimes(): bool {
		return $this->useExactTimes;
	}

	public function setUseExactTimes( bool $useExactTimes ): void {
		$this->useExactTimes = $useExactTimes;
	}
}
