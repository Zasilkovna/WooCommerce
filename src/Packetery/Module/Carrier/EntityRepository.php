<?php
/**
 * Class EntityRepository
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Core\Entity;
use Packetery\Module\EntityFactory;

/**
 * Class EntityRepository
 *
 * @package Packetery
 */
class EntityRepository {

	/**
	 * Carrier repository.
	 *
	 * @var Repository
	 */
	private $repository;

	/**
	 * Carrier Entity Factory.
	 *
	 * @var EntityFactory\Carrier
	 */
	private $carrierEntityFactory;

	/**
	 * Internal pickup points config.
	 *
	 * @var PacketaPickupPointsConfig
	 */
	private $pickupPointsConfig;

	/**
	 * Constructor.
	 *
	 * @param Repository                $repository           Carrier repository.
	 * @param EntityFactory\Carrier     $carrierEntityFactory Carrier Entity Factory.
	 * @param PacketaPickupPointsConfig $pickupPointsConfig   Internal pickup points config.
	 */
	public function __construct(
		Repository $repository,
		EntityFactory\Carrier $carrierEntityFactory,
		PacketaPickupPointsConfig $pickupPointsConfig
	) {
		$this->repository           = $repository;
		$this->carrierEntityFactory = $carrierEntityFactory;
		$this->pickupPointsConfig   = $pickupPointsConfig;
	}

	/**
	 * Gets Carrier value object by id.
	 *
	 * @param int $carrierId Carrier id.
	 *
	 * @return Entity\Carrier|null
	 */
	public function getById( int $carrierId ): ?Entity\Carrier {
		$result = $this->repository->getById( $carrierId );
		if ( null === $result ) {
			return null;
		}

		return $this->carrierEntityFactory->fromDbResult( $result );
	}

	/**
	 * Gets feed carrier or packeta carrier by id.
	 *
	 * @param string $carrierId Extended branch service id.
	 *
	 * @return Entity\Carrier|null
	 */
	public function getAnyById( string $carrierId ): ?Entity\Carrier {
		$nonFeedCarriers = $this->pickupPointsConfig->getCompoundAndVendorCarriers();

		foreach ( $nonFeedCarriers as $nonFeedCarrier ) {
			if ( $nonFeedCarrier->getId() === $carrierId ) {
				return $this->carrierEntityFactory->fromNonFeedCarrierData( $nonFeedCarrier );
			}
		}

		if ( ! is_numeric( $carrierId ) ) {
			return null;
		}

		return $this->getById( (int) $carrierId );
	}

	/**
	 * Gets all active carriers for a country.
	 *
	 * @param string $country ISO code.
	 *
	 * @return Entity\Carrier[]
	 */
	public function getByCountry( string $country ): array {
		$entities        = [];
		$countryCarriers = $this->repository->getByCountry( $country );

		foreach ( $countryCarriers as $carrierData ) {
			$entities[] = $this->carrierEntityFactory->fromDbResult( $carrierData );
		}

		return $entities;
	}


	/**
	 * Gets all active carriers.
	 *
	 * @return Entity\Carrier[]
	 */
	public function getActiveCarriers(): array {
		$entities       = [];
		$activeCarriers = $this->repository->getActiveCarriers();

		foreach ( $activeCarriers as $carrierData ) {
			$entities[ $carrierData['id'] ] = $this->carrierEntityFactory->fromDbResult( $carrierData );
		}

		return $entities;
	}

	/**
	 * Gets all active carriers for a country including internal pickup point carriers.
	 *
	 * @param string $country ISO code.
	 *
	 * @return Entity\Carrier[]
	 */
	public function getByCountryIncludingNonFeed( string $country ): array {
		$nonFeedCarriers       = [];
		$nonFeedCarriersArrays = $this->pickupPointsConfig->getNonFeedCarriersByCountry( $country );
		foreach ( $nonFeedCarriersArrays as $nonFeedCarrierData ) {
			$nonFeedCarriers[] = $this->carrierEntityFactory->fromNonFeedCarrierData( $nonFeedCarrierData );
		}
		$feedCarriers = $this->getByCountry( $country );

		return array_merge( $nonFeedCarriers, $feedCarriers );
	}

	/**
	 * Get all carriers.
	 *
	 * @return Entity\Carrier[]
	 */
	public function getAllCarriersIncludingNonFeed(): array {
		$feedCarriers    = $this->getActiveCarriers();
		$nonFeedCarriers = $this->getNonFeedCarriers();

		return array_merge( $feedCarriers, $nonFeedCarriers );
	}

	/**
	 * Gets zpoint carriers as object.
	 *
	 * @return Entity\Carrier[]
	 */
	public function getNonFeedCarriers(): array {
		$carriers        = [];
		$nonFeedCarriers = $this->pickupPointsConfig->getCompoundAndVendorCarriers();

		foreach ( $nonFeedCarriers as $nonFeedCarrier ) {
			$carriers[ $nonFeedCarrier->getId() ] = $this->carrierEntityFactory->fromNonFeedCarrierData( $nonFeedCarrier );
		}

		return $carriers;
	}

	/**
	 * Gets all active carriers for checkbox list
	 *
	 * @return array
	 */
	public function getAllActiveCarriersList(): array {
		$activeCarriers = [];
		$carriers       = $this->getAllCarriersIncludingNonFeed();
		foreach ( $carriers as $carrier ) {
			$carrierOptions = Options::createByCarrierId( $carrier->getId() );
			if ( $carrierOptions->isActive() ) {
				$activeCarriers[] = [
					'option_id' => $carrierOptions->getOptionId(),
					'label'     => $carrierOptions->getName(),
				];
			}
		}

		return $activeCarriers;
	}

	/**
	 * Validates carrier for country.
	 *
	 * @param string $carrierId       Carrier id.
	 * @param string $customerCountry Customer country.
	 *
	 * @return bool
	 */
	public function isValidForCountry( string $carrierId, string $customerCountry ): bool {
		// There is no separate validation for vendor carriers yet.
		if ( ! is_numeric( $carrierId ) ) {
			$compoundCarriers = $this->pickupPointsConfig->getCompoundCarriers();

			return ( ! empty( $compoundCarriers[ $customerCountry ] ) );
		}

		$carrier = $this->getById( (int) $carrierId );
		if ( null === $carrier || $carrier->isDeleted() || $customerCountry !== $carrier->getCountry() ) {
			return false;
		}

		return Options::createByCarrierId( $carrier->getId() )->isActive();
	}

}
