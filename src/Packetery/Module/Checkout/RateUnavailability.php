<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

class RateUnavailability {

	/**
	 * @var string One of RateUnavailabilityReason constants.
	 */
	private $reason;

	/**
	 * @var array<string, scalar|null>
	 */
	private $context;

	/**
	 * @param string                     $reason  One of RateUnavailabilityReason constants.
	 * @param array<string, scalar|null> $context Measured values, configured limits and identifiers of
	 *                                      what caused it, so the client can act without asking.
	 */
	public function __construct( string $reason, array $context = [] ) {
		$this->reason  = $reason;
		$this->context = $context;
	}

	public function getReason(): string {
		return $this->reason;
	}

	/**
	 * @return array<string, scalar|null>
	 */
	public function getContext(): array {
		return $this->context;
	}
}
