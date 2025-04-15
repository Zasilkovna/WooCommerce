<?php

declare( strict_types=1 );

namespace Packetery\Module\Order;

class GridLinksConfig {
	/**
	 * @var bool
	 */
	private $filterOrdersToSubmitEnabled;

	/**
	 * @var string
	 */
	private $filterOrdersToSubmitTitle;

	/**
	 * @var bool
	 */
	private $filterOrdersToPrintEnabled;

	/**
	 * @var string
	 */
	private $filterOrdersToPrintTitle;

	/**
	 * @var bool
	 */
	private $orderGridRunWizardEnabled;

	/**
	 * @var string
	 */
	private $orderGridRunWizardTitle;

	public function __construct(
		string $filterOrdersToSubmitTitle,
		string $filterOrdersToPrintTitle,
		string $orderGridRunWizardTitle
	) {
		$this->filterOrdersToSubmitEnabled = true;
		$this->filterOrdersToSubmitTitle   = $filterOrdersToSubmitTitle;
		$this->filterOrdersToPrintEnabled  = true;
		$this->filterOrdersToPrintTitle    = $filterOrdersToPrintTitle;
		$this->orderGridRunWizardEnabled   = true;
		$this->orderGridRunWizardTitle     = $orderGridRunWizardTitle;
	}

	public function isFilterOrdersToSubmitEnabled(): bool {
		return $this->filterOrdersToSubmitEnabled;
	}

	public function setFilterOrdersToSubmitEnabled( bool $filterOrdersToSubmitEnabled ): void {
		$this->filterOrdersToSubmitEnabled = $filterOrdersToSubmitEnabled;
	}

	public function getFilterOrdersToSubmitTitle(): string {
		return $this->filterOrdersToSubmitTitle;
	}

	public function setFilterOrdersToSubmitTitle( string $filterOrdersToSubmitTitle ): void {
		$this->filterOrdersToSubmitTitle = $filterOrdersToSubmitTitle;
	}

	public function isFilterOrdersToPrintEnabled(): bool {
		return $this->filterOrdersToPrintEnabled;
	}

	public function setFilterOrdersToPrintEnabled( bool $filterOrdersToPrintEnabled ): void {
		$this->filterOrdersToPrintEnabled = $filterOrdersToPrintEnabled;
	}

	public function getFilterOrdersToPrintTitle(): string {
		return $this->filterOrdersToPrintTitle;
	}

	public function setFilterOrdersToPrintTitle( string $filterOrdersToPrintTitle ): void {
		$this->filterOrdersToPrintTitle = $filterOrdersToPrintTitle;
	}

	public function isOrderGridRunWizardEnabled(): bool {
		return $this->orderGridRunWizardEnabled;
	}

	public function setOrderGridRunWizardEnabled( bool $orderGridRunWizardEnabled ): void {
		$this->orderGridRunWizardEnabled = $orderGridRunWizardEnabled;
	}

	public function getOrderGridRunWizardTitle(): string {
		return $this->orderGridRunWizardTitle;
	}

	public function setOrderGridRunWizardTitle( string $orderGridRunWizardTitle ): void {
		$this->orderGridRunWizardTitle = $orderGridRunWizardTitle;
	}
}
