<?php

declare( strict_types=1 );

namespace Packetery\Module\Dashboard;

class DashboardItem {
	/**
	 * @var string
	 */
	private $caption;

	/**
	 * @var string|null
	 */
	private $url;

	/**
	 * @var string
	 */
	private $description;

	/**
	 * @var int
	 */
	private $order;

	/**
	 * @var bool
	 */
	private $isFinished;

	public function __construct(
		string $caption,
		?string $url,
		string $description,
		int $order,
		bool $isFinished
	) {
		$this->caption     = $caption;
		$this->url         = $url;
		$this->description = $description;
		$this->order       = $order;
		$this->isFinished  = $isFinished;
	}

	public function getCaption(): string {
		return $this->caption;
	}

	public function getUrl(): ?string {
		return $this->url;
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function getOrder(): int {
		return $this->order;
	}

	public function isFinished(): bool {
		return $this->isFinished;
	}
}
