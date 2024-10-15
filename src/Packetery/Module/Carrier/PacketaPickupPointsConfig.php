<?php
/**
 * Packeta pickup points configuration.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Core\Entity;
use Packetery\Core\PickupPointProvider\BaseProvider;
use Packetery\Core\PickupPointProvider\CompoundCarrierCollectionFactory;
use Packetery\Core\PickupPointProvider\CompoundProvider;
use Packetery\Core\PickupPointProvider\VendorCollectionFactory;
use Packetery\Core\PickupPointProvider\VendorProvider;
use Packetery\Module\Exception\InvalidCarrierException;
use Packetery\Module\Options\FeatureFlagManager;

/**
 * Packeta pickup points configuration.
 *
 * @package Packetery
 */
class PacketaPickupPointsConfig {

	public const COMPOUND_CARRIER_PREFIX = 'zpoint';

	/**
	 * CompoundCarrierCollectionFactory.
	 *
	 * @var CompoundCarrierCollectionFactory
	 */
	private $compoundCarrierFactory;

	/**
	 * VendorCollectionFactory.
	 *
	 * @var VendorCollectionFactory
	 */
	private $vendorCollectionFactory;

	/**
	 * Feature flag.
	 *
	 * @var FeatureFlagManager
	 */
	private $featureFlag;

	/**
	 * PacketaPickupPointsConfig.
	 *
	 * @param CompoundCarrierCollectionFactory $compoundCarrierFactory  CompoundCarrierCollectionFactory.
	 * @param VendorCollectionFactory          $vendorCollectionFactory VendorCollectionFactory.
	 * @param FeatureFlagManager               $featureFlag             Feature flag.
	 */
	public function __construct(
		CompoundCarrierCollectionFactory $compoundCarrierFactory,
		VendorCollectionFactory $vendorCollectionFactory,
		FeatureFlagManager $featureFlag
	) {
		$this->vendorCollectionFactory = $vendorCollectionFactory;
		$this->compoundCarrierFactory  = $compoundCarrierFactory;
		$this->featureFlag             = $featureFlag;
	}

	/**
	 * Returns internal pickup points configuration
	 *
	 * @return CompoundProvider[]
	 */
	public function getCompoundCarriers(): array {
		$translatedNames = [
			'zpointcz' => 'CZ ' . __( 'Packeta Pick-up Point (Z-Point, Z-Box)', 'packeta' ),
			'zpointsk' => 'SK ' . __( 'Packeta Pick-up Point (Z-Point, Z-Box)', 'packeta' ),
			'zpointhu' => 'HU ' . __( 'Packeta Pick-up Point (Z-Point, Z-Box)', 'packeta' ),
			'zpointro' => 'RO ' . __( 'Packeta Pick-up Point (Z-Point, Z-Box)', 'packeta' ),
		];

		$indexedCollection         = [];
		$compoundCarrierCollection = $this->compoundCarrierFactory->create();
		foreach ( $compoundCarrierCollection as $compoundProvider ) {
			$carrierId = $compoundProvider->getId();
			assert( isset( $translatedNames[ $carrierId ] ), 'Missing name for carrier id ' . $carrierId );
			$compoundProvider->setTranslatedName( $translatedNames[ $carrierId ] );
			// There is only one provider for each country.
			$indexedCollection[ $compoundProvider->getCountry() ] = $compoundProvider;
		}

		return $indexedCollection;
	}

	/**
	 * Gets vendor carriers settings.
	 *
	 * @param bool $forceReturnVendorCarriers Set true to get vendor carriers if split is disabled.
	 *
	 * @return VendorProvider[]
	 */
	public function getVendorCarriers( bool $forceReturnVendorCarriers = false ): array {
		if ( ! $forceReturnVendorCarriers && ! $this->featureFlag->isSplitActive() ) {
			return [];
		}

		$translatedNames = [
			'czzpoint' => 'CZ ' . __( 'Packeta Pick-up Point', 'packeta' ),
			'czzbox'   => 'CZ ' . __( 'Packeta', 'packeta' ) . ' Z-BOX',
			'skzpoint' => 'SK ' . __( 'Packeta Pick-up Point', 'packeta' ),
			'skzbox'   => 'SK ' . __( 'Packeta', 'packeta' ) . ' Z-BOX',
			'huzpoint' => 'HU ' . __( 'Packeta Pick-up Point', 'packeta' ),
			'huzbox'   => 'HU ' . __( 'Packeta', 'packeta' ) . ' Z-BOX',
			'rozpoint' => 'RO ' . __( 'Packeta Pick-up Point', 'packeta' ),
			'rozbox'   => 'RO ' . __( 'Packeta', 'packeta' ) . ' Z-BOX',
		];

		$indexedCollection = [];
		$vendorCollection  = $this->vendorCollectionFactory->create();
		foreach ( $vendorCollection as $vendorProvider ) {
			$vendorId = $vendorProvider->getId();
			assert( isset( $translatedNames[ $vendorId ] ), 'Missing name for vendor id ' . $vendorId );
			$vendorProvider->setTranslatedName( $translatedNames[ $vendorId ] );
			$indexedCollection[ $vendorId ] = $vendorProvider;
		}

		return $indexedCollection;
	}

	/**
	 * Gets all non-feed carriers settings.
	 *
	 * @return BaseProvider[]
	 */
	public function getCompoundAndVendorCarriers(): array {
		return array_merge( $this->getCompoundCarriers(), $this->getVendorCarriers() );
	}

	/**
	 * Checks if id is compound carrier id.
	 *
	 * @param string $carrierId Carrier id.
	 *
	 * @return bool
	 */
	public function isCompoundCarrierId( string $carrierId ): bool {
		return ( strpos( $carrierId, self::COMPOUND_CARRIER_PREFIX ) === 0 );
	}

	/**
	 * Checks if provided id is a vendor carrier id.
	 *
	 * @param string $carrierId Carrier id.
	 *
	 * @return bool
	 */
	public function isVendorCarrierId( string $carrierId ): bool {
		$vendorCarriers = $this->getVendorCarriers();
		return isset( $vendorCarriers[ $carrierId ] );
	}

	/**
	 * All internal pickup point carrier ids are strings, including old 'packeta' value.
	 *
	 * @param string $carrierId Carrier id.
	 *
	 * @return bool
	 */
	public function isInternalPickupPointCarrier( string $carrierId ): bool {
		return ! is_numeric( $carrierId );
	}

	/**
	 * Gets non-feed carriers settings by country.
	 *
	 * @param string $country Country.
	 *
	 * @return array
	 */
	public function getNonFeedCarriersByCountry( string $country ): array {
		$filteredCarriers = [];
		$nonFeedCarriers  = $this->getCompoundAndVendorCarriers();

		foreach ( $nonFeedCarriers as $nonFeedCarrier ) {
			if ( $nonFeedCarrier->getCountry() === $country ) {
				$filteredCarriers[] = $nonFeedCarrier;
			}
		}

		return $filteredCarriers;
	}

	/**
	 * Gets internal countries.
	 *
	 * @return string[]
	 */
	public function getInternalCountries(): array {
		return array_keys( $this->getCompoundCarriers() );
	}

	/**
	 * Changes previously used identifier 'packeta' to zpoint type id.
	 * Returns one of ids from CompoundCarrierCollectionFactory, e.g. zpointcz.
	 *
	 * @param string $carrierId Carrier id.
	 * @param string $country Lowercase country.
	 *
	 * @return string|null Null in case of split vendor when split is off.
	 * @throws InvalidCarrierException InvalidCarrierException.
	 */
	public function getFixedCarrierId( string $carrierId, string $country ): string {
		if ( Entity\Carrier::INTERNAL_PICKUP_POINTS_ID === $carrierId ) {
			$compoundCarriers = $this->getCompoundCarriers();

			if ( ! isset( $compoundCarriers[ $country ] ) ) {
				throw new InvalidCarrierException(
					sprintf(
					// translators: %s is country code.
						__( 'Selected carrier does not deliver to country "%s".', 'packeta' ),
						$country
					)
				);
			}

			return $compoundCarriers[ $country ]->getId();
		}

		return $carrierId;
	}

}
