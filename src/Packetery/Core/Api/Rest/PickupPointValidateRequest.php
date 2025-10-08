<?php

declare( strict_types=1 );

namespace Packetery\Core\Api\Rest;

use Packetery\Core\Entity\Carrier;

class PickupPointValidateRequest {

	/** @var array<string, bool|float|list<array<string, mixed>>|string|null> */
	private $options;

	/** @var array<string, string|null> */
	private $point;

	/**
	 * @param string                    $pickupPointId
	 * @param string|null               $carrierId
	 * @param string|null               $carrierPickupPointId
	 * @param string|null               $country
	 * @param bool|null                 $claimAssistant
	 * @param bool|null                 $packetConsignment
	 * @param float|null                $weight
	 * @param bool|null                 $livePickupPoint
	 * @param string|null               $expeditionDay
	 * @param bool|null                 $cashOnDelivery
	 * @param array<string, float>|null $maxProductDimensions
	 * @param string[]|null             $vendorGroups
	 */
	public function __construct(
		string $pickupPointId,
		?string $carrierId,
		?string $carrierPickupPointId,
		?string $country,
		?bool $claimAssistant,
		?bool $packetConsignment,
		?float $weight,
		?bool $livePickupPoint,
		?string $expeditionDay,
		?bool $cashOnDelivery,
		?array $maxProductDimensions,
		?array $vendorGroups
	) {
		$resolvedCarrierId = is_numeric( $carrierId ) ? $carrierId : Carrier::INTERNAL_PICKUP_POINTS_ID;

		$this->options = [
			'country'           => $country,
			'carriers'          => $resolvedCarrierId,
			'claimAssistant'    => $claimAssistant,
			'packetConsignment' => $packetConsignment,
			'weight'            => $weight,
			'livePickupPoint'   => $livePickupPoint,
			'expeditionDay'     => $expeditionDay,
			'cashOnDelivery'    => $cashOnDelivery,
		];
		if ( $resolvedCarrierId === Carrier::INTERNAL_PICKUP_POINTS_ID && $vendorGroups !== null ) {
			$this->options['vendors'] = [];
			foreach ( $vendorGroups as $vendorGroup ) {
				$vendorOptions = [
					'carrierId' => null,
					'country'   => $country,
				];
				if ( $vendorGroup !== Carrier::VENDOR_GROUP_ZPOINT ) {
					$vendorOptions['group'] = $vendorGroup;
				}
				$this->options['vendors'][] = $vendorOptions;
			}
		}
		if ( $maxProductDimensions !== null ) {
			$this->options['length'] = $maxProductDimensions['length'];
			$this->options['width']  = $maxProductDimensions['width'];
			$this->options['depth']  = $maxProductDimensions['depth'];
		}

		$this->point = [
			'id'                   => $carrierPickupPointId === null ? $pickupPointId : null,
			'carrierId'            => is_numeric( $carrierId ) ? $carrierId : null,
			'carrierPickupPointId' => $carrierPickupPointId,
		];
	}

	/**
	 * @return array<string, string|bool|float|null>
	 */
	public function getSubmittableData(): array {
		return array_filter( get_object_vars( $this ) );
	}
}
