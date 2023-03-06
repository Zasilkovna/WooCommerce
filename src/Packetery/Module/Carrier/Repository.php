<?php
/**
 * Packeta carrier repository
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Module\WpdbAdapter;

/**
 * Class CarrierRepository
 * TODO: cache - some queries may run more times during request.
 *
 * @package Packetery
 */
class Repository {

	public const INTERNAL_PICKUP_POINTS_ID = 'packeta';

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
	 * Internal pickup points config.
	 *
	 * @var PacketaPickupPointsConfig
	 */
	private $pickupPointsConfig;

	/**
	 * Repository constructor.
	 *
	 * @param WpdbAdapter               $wpdbAdapter        WpdbAdapter.
	 * @param PacketaPickupPointsConfig $pickupPointsConfig Internal pickup points config.
	 */
	public function __construct(
		WpdbAdapter $wpdbAdapter,
		PacketaPickupPointsConfig $pickupPointsConfig
	) {
		$this->wpdbAdapter        = $wpdbAdapter;
		$this->pickupPointsConfig = $pickupPointsConfig;
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
	 * Gets all active carriers including internal pickup point carriers.
	 *
	 * @return array|null
	 */
	public function getAllIncludingZpoints(): ?array {
		$carriers        = $this->wpdbAdapter->get_results( 'SELECT `id`, `name`, `is_pickup_points`, `country`, `currency`  FROM `' . $this->wpdbAdapter->packetery_carrier . '`', ARRAY_A );
		$nonFeedCarriers = $this->pickupPointsConfig->getCompoundAndVendorCarriers();
		foreach ( $nonFeedCarriers as $nonFeedCarrier ) {
			array_unshift( $carriers, $nonFeedCarrier );
		}

		return $carriers;
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
	 * @return array|object|null
	 */
	public function getById( int $carrierId ) {
		return $this->wpdbAdapter->get_row(
			$this->wpdbAdapter->prepare(
				'SELECT `' . implode( '`, `', self::COLUMN_NAMES ) . '`
				FROM `' . $this->wpdbAdapter->packetery_carrier . '` WHERE `id` = %s',
				$carrierId
			),
			ARRAY_A
		);
	}

	/**
	 * Gets all active carriers for a country.
	 *
	 * @param string $country ISO code.
	 *
	 * @return array|object|null
	 */
	public function getByCountry( string $country ) {
		return $this->wpdbAdapter->get_results(
			$this->wpdbAdapter->prepare(
				'SELECT `' . implode( '`, `', self::COLUMN_NAMES ) . '`
				FROM `' . $this->wpdbAdapter->packetery_carrier . '` WHERE `country` = %s AND `deleted` = false',
				$country
			),
			ARRAY_A
		);
	}

	/**
	 * Gets all active carriers.
	 *
	 * @return array|object|null
	 */
	public function getActiveCarriers() {
		return $this->wpdbAdapter->get_results(
			'SELECT `' . implode( '`, `', self::COLUMN_NAMES ) . '`
			FROM `' . $this->wpdbAdapter->packetery_carrier . '` WHERE `deleted` = false',
			ARRAY_A
		);
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
		if ( $this->pickupPointsConfig->isVendorCarrierId( $carrierId ) ) {
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

}
