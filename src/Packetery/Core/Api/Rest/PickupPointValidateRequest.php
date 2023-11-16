<?php
/**
 * Class PickupPointValidateRequest
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Rest;

/**
 * Class PickupPointValidateRequest
 *
 * @package Packetery
 */
class PickupPointValidateRequest {

	/**
	 * Pickup point id.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Carrier id.
	 *
	 * @var string|null
	 */
	private $carrierId;

	/**
	 * Carrier pickup point id.
	 *
	 * @var string|null
	 */
	private $carrierPickupPointId;

	/**
	 * Destination country.
	 *
	 * @var string|null
	 */
	private $country;

	/**
	 * Allowed carriers.
	 *
	 * @var string|null
	 */
	private $carriers;

	/**
	 * Set true if you need Claim Assistant service.
	 *
	 * @var bool|null
	 */
	private $claimAssistant;

	/**
	 * Set true if you need new parcel consignment service.
	 *
	 * @var bool|null
	 */
	private $packetConsignment;

	/**
	 * Package weight.
	 *
	 * @var float|null
	 */
	private $weight;

	/**
	 * Set true if you need age verification.
	 *
	 * @var bool|null
	 */
	private $livePickupPoint;

	/**
	 * Expected expedition day.
	 *
	 * @var string|null
	 */
	private $expeditionDay;

	/**
	 * PickupPointValidateResponse constructor.
	 *
	 * @param string      $pickupPointId Pickup point id.
	 * @param string|null $carrierId Carrier id.
	 * @param string|null $carrierPickupPointId Carrier pickup point id.
	 * @param string|null $country Destination country.
	 * @param string|null $carriers Allowed carriers.
	 * @param bool|null   $claimAssistant Set true if you need Claim Assistant service.
	 * @param bool|null   $packetConsignment Set true if you need new parcel consignment service.
	 * @param float|null  $weight Package weight.
	 * @param bool|null   $livePickupPoint Set true if you need age verification.
	 * @param string|null $expeditionDay Expected expedition day.
	 */
	public function __construct(
		string $pickupPointId,
		?string $carrierId,
		?string $carrierPickupPointId,
		?string $country,
		?string $carriers,
		?bool $claimAssistant,
		?bool $packetConsignment,
		?float $weight,
		?bool $livePickupPoint,
		?string $expeditionDay
	) {
		$this->id                   = $pickupPointId;
		$this->carrierId            = $carrierId;
		$this->carrierPickupPointId = $carrierPickupPointId;
		$this->country              = $country;
		$this->carriers             = $carriers;
		$this->claimAssistant       = $claimAssistant;
		$this->packetConsignment    = $packetConsignment;
		$this->weight               = $weight;
		$this->livePickupPoint      = $livePickupPoint;
		$this->expeditionDay        = $expeditionDay;
	}

	/**
	 * Gets submittable data.
	 *
	 * @return array<string, string|bool|float|null>
	 */
	public function getSubmittableData(): array {
		return array_filter( get_object_vars( $this ) );
	}

}
