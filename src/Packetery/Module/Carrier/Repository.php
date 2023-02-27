<?php
/**
 * Packeta carrier repository
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Core\Entity;
use Packetery\Module\EntityFactory;
use Packetery\Module\WpdbAdapter;

/**
 * Class CarrierRepository
 * TODO: cache - some queries may run more times during request.
 *
 * @package Packetery
 */
class Repository {

	public const INTERNAL_PICKUP_POINTS_ID = 'packeta';
	public const ZPOINT_CARRIER_PREFIX     = 'zpoint';

	private const COLUMN_NAMES = [
		'id',
		'name',
		'is_pickup_points',
		'has_carrier_direct_label',
		'separate_house_number',
		'customs_declarations',
		'requires_email',
		'requires_phone',
		'requires_size',
		'disallows_cod',
		'country',
		'currency',
		'max_weight',
		'deleted',
	];

	/**
	 * WpdbAdapter object from global
	 *
	 * @var WpdbAdapter
	 */
	private $wpdbAdapter;

	/**
	 * Carrier Entity Factory.
	 *
	 * @var EntityFactory\Carrier
	 */
	private $carrierEntityFactory;

	/**
	 * Repository constructor.
	 *
	 * @param WpdbAdapter           $wpdbAdapter          WpdbAdapter.
	 * @param EntityFactory\Carrier $carrierEntityFactory Carrier Entity Factory.
	 */
	public function __construct( WpdbAdapter $wpdbAdapter, EntityFactory\Carrier $carrierEntityFactory ) {
		$this->wpdbAdapter          = $wpdbAdapter;
		$this->carrierEntityFactory = $carrierEntityFactory;
	}

	/**
	 * Create table to store carriers.
	 *
	 * @return bool
	 */
	public function createTable(): bool {
		return $this->wpdbAdapter->query(
			'CREATE TABLE IF NOT EXISTS `' . $this->wpdbAdapter->packetery_carrier . '` (
				`id` int NOT NULL,
				`name` varchar(255) NOT NULL,
				`is_pickup_points` boolean NOT NULL,
				`has_carrier_direct_label` boolean NOT NULL,
				`separate_house_number` boolean NOT NULL,
				`customs_declarations` boolean NOT NULL,
				`requires_email` boolean NOT NULL,
				`requires_phone` boolean NOT NULL,
				`requires_size` boolean NOT NULL,
				`disallows_cod` boolean NOT NULL,
				`country` varchar(255) NOT NULL,
				`currency` varchar(255) NOT NULL,
				`max_weight` float NOT NULL,
				`deleted` boolean NOT NULL,
				PRIMARY KEY (`id`)
			) ' . $this->wpdbAdapter->get_charset_collate()
		);
	}

	/**
	 * Drop table used to store carriers.
	 */
	public function drop(): void {
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packetery_carrier . '`' );
	}

	/**
	 * Gets known carrier ids.
	 *
	 * @return array|null
	 */
	public function get_carrier_ids(): ?array {
		return $this->wpdbAdapter->get_results( 'SELECT `id` FROM `' . $this->wpdbAdapter->packetery_carrier . '`', ARRAY_A );
	}

	/**
	 * Gets all active carriers including internal pickup point carriers.
	 *
	 * @return array|null
	 */
	public function getAllIncludingZpoints(): ?array {
		$carriers       = $this->wpdbAdapter->get_results( 'SELECT `id`, `name`, `is_pickup_points`, `country`, `currency`  FROM `' . $this->wpdbAdapter->packetery_carrier . '`', ARRAY_A );
		$zpointCarriers = $this->getNonFeedCarriers();
		foreach ( $zpointCarriers as $zpointCarrier ) {
			array_unshift( $carriers, $zpointCarrier );
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
		$carriers       = $this->getAllCarriersIncludingZpoints();
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
	 * Gets is_pickup_point attribute of a carrier.
	 *
	 * @param int $carrierId Carrier id.
	 *
	 * @return bool
	 */
	public function hasPickupPoints( int $carrierId ): bool {
		return (bool) $this->wpdbAdapter->get_var(
			$this->wpdbAdapter->prepare( 'SELECT `is_pickup_points` FROM `' . $this->wpdbAdapter->packetery_carrier . '` WHERE `id` = %d', $carrierId )
		);
	}

	/**
	 * Gets Carrier value object by id.
	 *
	 * @param int $carrierId Carrier id.
	 *
	 * @return Entity\Carrier|null
	 */
	public function getById( int $carrierId ): ?Entity\Carrier {
		$result = $this->wpdbAdapter->get_row(
			$this->wpdbAdapter->prepare(
				'SELECT `' . implode( '`, `', self::COLUMN_NAMES ) . '`
				FROM `' . $this->wpdbAdapter->packetery_carrier . '` WHERE `id` = %s',
				$carrierId
			),
			ARRAY_A
		);
		if ( null === $result ) {
			return null;
		}

		return $this->carrierEntityFactory->fromDbResult( $result );
	}

	/**
	 * Gets feed carrier or packeta carrier by id.
	 *
	 * @param string $extendedBranchServiceId Extended branch service id.
	 *
	 * @return Entity\Carrier|null
	 */
	public function getAnyById( string $extendedBranchServiceId ): ?Entity\Carrier {
		$zpointCarriers = $this->getNonFeedCarriers();

		foreach ( $zpointCarriers as $zpointCarrier ) {
			if ( $zpointCarrier['id'] === $extendedBranchServiceId ) {
				return $this->carrierEntityFactory->fromZpointCarrierData( $zpointCarrier );
			}
		}

		if ( ! is_numeric( $extendedBranchServiceId ) ) {
			return null;
		}

		return $this->getById( (int) $extendedBranchServiceId );
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
		$countryCarriers = $this->wpdbAdapter->get_results(
			$this->wpdbAdapter->prepare(
				'SELECT `' . implode( '`, `', self::COLUMN_NAMES ) . '`
				FROM `' . $this->wpdbAdapter->packetery_carrier . '` WHERE `country` = %s AND `deleted` = false',
				$country
			),
			ARRAY_A
		);

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
		$activeCarriers = $this->wpdbAdapter->get_results(
			'SELECT `' . implode( '`, `', self::COLUMN_NAMES ) . '`
			FROM `' . $this->wpdbAdapter->packetery_carrier . '` WHERE `deleted` = false',
			ARRAY_A
		);

		foreach ( $activeCarriers as $carrierData ) {
			$entities[ $carrierData['id'] ] = $this->carrierEntityFactory->fromDbResult( $carrierData );
		}

		return $entities;
	}

	/**
	 * Gets all carriers.
	 *
	 * @return array[]
	 */
	public function getAllRawIndexed(): array {
		$unIndexedResult = $this->wpdbAdapter->get_results(
			'SELECT `' . implode( '`, `', self::COLUMN_NAMES ) . '`
			FROM `' . $this->wpdbAdapter->packetery_carrier . '`',
			ARRAY_A
		);

		return array_combine( array_column( $unIndexedResult, 'id' ), $unIndexedResult );
	}

	/**
	 * Gets all active carriers for a country including internal pickup point carriers.
	 *
	 * @param string $country ISO code.
	 *
	 * @return Entity\Carrier[]
	 */
	public function getByCountryIncludingZpoints( string $country ): array {
		$countryCarriers = [];
		$zpointCarriers  = $this->getNonFeedCarriersByCountry( $country );
		if ( $zpointCarriers ) {
			foreach ( $zpointCarriers as $zpointCarrierData ) {
				$countryCarriers[] = $this->carrierEntityFactory->fromZpointCarrierData( $zpointCarrierData );
			}
		}

		$feedCarriers = $this->getByCountry( $country );

		return array_merge( $countryCarriers, $feedCarriers );
	}

	/**
	 * Get all carriers.
	 *
	 * @return Entity\Carrier[]
	 */
	public function getAllCarriersIncludingZpoints(): array {
		$feedCarriers   = $this->getActiveCarriers();
		$zpointCarriers = $this->getZpointCarrierCarriers();

		return array_merge( $feedCarriers, $zpointCarriers );
	}

	/**
	 * Tells if there is any active feed carrier.
	 *
	 * @return bool
	 */
	public function hasAnyActiveFeedCarrier(): bool {
		return (bool) $this->wpdbAdapter->get_var( 'SELECT 1 FROM `' . $this->wpdbAdapter->packetery_carrier . '` WHERE `deleted` = false LIMIT 1' );
	}

	/**
	 * Gets all active countries.
	 *
	 * @return array
	 */
	public function getCountries(): array {
		return $this->wpdbAdapter->get_col( 'SELECT `country` FROM `' . $this->wpdbAdapter->packetery_carrier . '` WHERE `deleted` = false GROUP BY `country` ORDER BY `country`' );
	}

	/**
	 * Set carriers specified by ids as deleted.
	 *
	 * @param array $carrierIdsNotInFeed Carriers not in feed.
	 */
	public function set_as_deleted( array $carrierIdsNotInFeed ): void {
		$this->wpdbAdapter->query(
			'UPDATE `' . $this->wpdbAdapter->packetery_carrier . '`
			SET `deleted` = 1 WHERE `id` IN (' . implode( ',', $carrierIdsNotInFeed ) . ')'
		);
	}

	/**
	 * Inserts carrier data to db.
	 *
	 * @param array $data Carrier data.
	 */
	public function insert( array $data ): void {
		$this->wpdbAdapter->insert( $this->wpdbAdapter->packetery_carrier, $data );
	}

	/**
	 * Updates carrier data in db.
	 *
	 * @param array $data Carrier data.
	 * @param int   $carrier_id Carrier id.
	 */
	public function update( array $data, int $carrier_id ): void {
		$this->wpdbAdapter->update( $this->wpdbAdapter->packetery_carrier, $data, [ 'id' => $carrier_id ] );
	}

	/**
	 * Gets internal countries.
	 *
	 * @return string[]
	 */
	public function getInternalCountries(): array {
		return array_keys( $this->getZpointCarriers() );
	}

	/**
	 * Returns internal pickup points configuration
	 *
	 * @return array[]
	 */
	public function getZpointCarriers(): array {
		return [
			'cz' => [
				'id'                        => 'zpointcz',
				'name'                      => __( 'CZ Packeta pickup points', 'packeta' ),
				'is_pickup_points'          => 1,
				'currency'                  => 'CZK',
				'supports_age_verification' => true,
				'country'                   => 'cz',
				'vendor_codes'              => [
					'czzpoint',
					'czzbox',
					'czalzabox',
				],
			],
			'sk' => [
				'id'                        => 'zpointsk',
				'name'                      => __( 'SK Packeta pickup points', 'packeta' ),
				'is_pickup_points'          => 1,
				'currency'                  => 'EUR',
				'supports_age_verification' => true,
				'country'                   => 'sk',
				'vendor_codes'              => [
					'skzpoint',
					'skzbox',
				],
			],
			'hu' => [
				'id'                        => 'zpointhu',
				'name'                      => __( 'HU Packeta pickup points', 'packeta' ),
				'is_pickup_points'          => 1,
				'currency'                  => 'HUF',
				'supports_age_verification' => true,
				'country'                   => 'hu',
				'vendor_codes'              => [
					'huzpoint',
					'huzbox',
				],
			],
			'ro' => [
				'id'                        => 'zpointro',
				'name'                      => __( 'RO Packeta pickup points', 'packeta' ),
				'is_pickup_points'          => 1,
				'currency'                  => 'RON',
				'supports_age_verification' => true,
				'country'                   => 'ro',
				'vendor_codes'              => [
					'rozpoint',
					'rozbox',
				],
			],
		];
	}

	/**
	 * Gets vendor carriers settings.
	 *
	 * @return array[]
	 */
	public function getVendorCarriers(): array {
		return [
			'czzpoint'  => [
				'country'      => 'cz',
				'group'        => 'zpoint',
				'name'         => __( 'CZ Packeta internal pickup points', 'packeta' ),
				'supports_cod' => true,
			],
			'czzbox'    => [
				'country'      => 'cz',
				'group'        => 'zbox',
				'name'         => __( 'CZ Packeta Z-BOX', 'packeta' ),
				'supports_cod' => true,
			],
			'czalzabox' => [
				'country'      => 'cz',
				'group'        => 'alzabox',
				'name'         => __( 'CZ AlzaBox', 'packeta' ),
				'supports_cod' => true,
			],
			'skzpoint'  => [
				'country'      => 'sk',
				'group'        => 'zpoint',
				'name'         => __( 'SK Packeta internal pickup points', 'packeta' ),
				'supports_cod' => true,
			],
			'skzbox'    => [
				'country'      => 'sk',
				'group'        => 'zbox',
				'name'         => __( 'SK Packeta Z-BOX', 'packeta' ),
				'supports_cod' => true,
			],
			'huzpoint'  => [
				'country'      => 'hu',
				'group'        => 'zpoint',
				'name'         => __( 'HU Packeta internal pickup points', 'packeta' ),
				'supports_cod' => true,
			],
			'huzbox'    => [
				'country'      => 'hu',
				'group'        => 'zbox',
				'name'         => __( 'HU Packeta Z-BOX', 'packeta' ),
				'supports_cod' => true,
			],
			'rozpoint'  => [
				'country'      => 'ro',
				'group'        => 'zpoint',
				'name'         => __( 'RO Packeta internal pickup points', 'packeta' ),
				'supports_cod' => true,
			],
			'rozbox'    => [
				'country'      => 'ro',
				'group'        => 'zbox',
				'name'         => __( 'RO Packeta Z-BOX', 'packeta' ),
				'supports_cod' => true,
			],
		];
	}

	/**
	 * Gets all non-feed carriers settings.
	 *
	 * @return array
	 */
	public function getNonFeedCarriers(): array {
		$nonFeedCarriers = [];

		$zPointCarriers = $this->getZpointCarriers();
		foreach ( $zPointCarriers as $country => $zpointCarrier ) {
			$nonFeedCarriers[ $zpointCarrier['id'] ] = ( $zpointCarrier + [ 'country' => $country ] );
		}

		foreach ( $this->getVendorCarriers() as $carrierId => $vendorCarrier ) {
			$nonFeedCarriers[ $carrierId ] = [
				'id'                        => $carrierId,
				'name'                      => $vendorCarrier['name'],

				// Vendor loads some settings from country.
				'is_pickup_points'          => $zPointCarriers[ $vendorCarrier['country'] ]['is_pickup_points'],
				'currency'                  => $zPointCarriers[ $vendorCarrier['country'] ]['currency'],
				'supports_age_verification' => $zPointCarriers[ $vendorCarrier['country'] ]['supports_age_verification'],

				'vendor_codes'              => [ $carrierId ],
				'country'                   => $vendorCarrier['country'],
				'supports_cod'              => $vendorCarrier['supports_cod'],
			];
		}

		return $nonFeedCarriers;
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
		$nonFeedCarriers  = $this->getNonFeedCarriers();

		foreach ( $nonFeedCarriers as $nonFeedCarrier ) {
			if ( $nonFeedCarrier['country'] === $country ) {
				$filteredCarriers[] = $nonFeedCarrier;
			}
		}

		return $filteredCarriers;
	}

	/**
	 * Gets zpoint carriers as object.
	 *
	 * @return Entity\Carrier[]
	 */
	public function getZpointCarrierCarriers(): array {
		$carriers       = [];
		$zpointCarriers = $this->getNonFeedCarriers();

		foreach ( $zpointCarriers as $zpointCarrier ) {
			$carriers[ $zpointCarrier['id'] ] = $this->carrierEntityFactory->fromZpointCarrierData( $zpointCarrier );
		}

		return $carriers;
	}

	/**
	 * Checks if chosen carrier has pickup points and sets carrier id in provided array.
	 *
	 * @param string $carrierId Carrier id.
	 *
	 * @return bool
	 */
	public function isPickupPointCarrier( string $carrierId ): bool {
		if ( self::INTERNAL_PICKUP_POINTS_ID === $carrierId ) {
			return true;
		}
		$vendorCarriers = $this->getVendorCarriers();
		if ( isset( $vendorCarriers[ $carrierId ] ) ) {
			return true;
		}

		return $this->hasPickupPoints( (int) $carrierId );
	}

	/**
	 * Checks if carrier is home delivery carrier.
	 *
	 * @param string $carrierId Carrier ID.
	 *
	 * @return bool
	 */
	public function isHomeDeliveryCarrier( string $carrierId ): bool {
		if ( self::INTERNAL_PICKUP_POINTS_ID === $carrierId ) {
			return false;
		}

		return false === $this->hasPickupPoints( (int) $carrierId );
	}

	/**
	 * Tells zpoint carrier Id for given country.
	 *
	 * @param string $country Country ISO code.
	 *
	 * @return string|null
	 */
	public function getZpointCarrierIdByCountry( string $country ): ?string {
		$zpointCarriers = $this->getZpointCarriers();

		if ( ! isset( $zpointCarriers[ $country ] ) ) {
			return null;
		}

		return $zpointCarriers[ $country ]['id'];
	}

	/**
	 * Validates carrier for country.
	 *
	 * @param string|null $carrierId       Null for internal pickup points.
	 * @param string      $customerCountry Customer country.
	 *
	 * @return bool
	 */
	public function isValidForCountry( ?string $carrierId, string $customerCountry ): bool {
		if ( null === $carrierId ) {
			$zpointCarriers = $this->getZpointCarriers();

			return ( ! empty( $zpointCarriers[ $customerCountry ] ) );
		}

		$carrier = $this->getById( (int) $carrierId );
		if ( null === $carrier || $carrier->isDeleted() || $customerCountry !== $carrier->getCountry() ) {
			return false;
		}

		return Options::createByCarrierId( $carrier->getId() )->isActive();
	}

}
