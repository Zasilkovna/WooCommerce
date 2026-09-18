<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

class ShippingRateEvaluation {

	/**
	 * @var string
	 */
	private $carrierId;

	/**
	 * @var string
	 */
	private $carrierName;

	/**
	 * @var array{label: string, id: string, cost: float, taxes: array<int, float>|string, calc_tax: string}|null
	 */
	private $rate;

	/**
	 * @var RateUnavailability[]
	 */
	private $unavailabilities;

	/**
	 * @param string                                                                                                $carrierId        Carrier id.
	 * @param string                                                                                                $carrierName      Name shown in checkout.
	 * @param array{label: string, id: string, cost: float, taxes: array<int, float>|string, calc_tax: string}|null $rate             Null when not offered.
	 * @param RateUnavailability[]                                                                                  $unavailabilities Empty when offered.
	 */
	public function __construct( string $carrierId, string $carrierName, ?array $rate, array $unavailabilities ) {
		$this->carrierId        = $carrierId;
		$this->carrierName      = $carrierName;
		$this->rate             = $rate;
		$this->unavailabilities = $unavailabilities;
	}

	public function getCarrierId(): string {
		return $this->carrierId;
	}

	public function getCarrierName(): string {
		return $this->carrierName;
	}

	/**
	 * @return array{label: string, id: string, cost: float, taxes: array<int, float>|string, calc_tax: string}|null
	 */
	public function getRate(): ?array {
		return $this->rate;
	}

	/**
	 * @return array<int, array{reason: string, context: array<string, scalar|null>}>
	 */
	public function getUnavailabilitiesForLog(): array {
		$result = [];
		foreach ( $this->unavailabilities as $unavailability ) {
			$result[] = [
				'reason'  => $unavailability->getReason(),
				'context' => $unavailability->getContext(),
			];
		}

		return $result;
	}
}
