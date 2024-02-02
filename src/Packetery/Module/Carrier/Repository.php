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
	public function createOrAlterTable(): bool {
		$createTableQuery = 'CREATE TABLE ' . $this->wpdbAdapter->packetery_carrier . ' (
			`id` int(11) NOT NULL,
			`name` varchar(255) NOT NULL,
			`is_pickup_points` tinyint(1) NOT NULL,
			`has_carrier_direct_label` tinyint(1) NOT NULL,
			`separate_house_number` tinyint(1) NOT NULL,
			`customs_declarations` tinyint(1) NOT NULL,
			`requires_email` tinyint(1) NOT NULL,
			`requires_phone` tinyint(1) NOT NULL,
			`requires_size` tinyint(1) NOT NULL,
			`disallows_cod` tinyint(1) NOT NULL,
			`country` varchar(255) NOT NULL,
			`currency` varchar(255) NOT NULL,
			`max_weight` float NOT NULL,
			`deleted` tinyint(1) NOT NULL,
			PRIMARY KEY  (`id`)
		) ' . $this->wpdbAdapter->get_charset_collate();

		return $this->wpdbAdapter->dbDelta( $createTableQuery, $this->wpdbAdapter->packetery_carrier );
	}

	/**
	 * Drop table used to store carriers.
	 */
	public function drop(): void {
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packetery_carrier . '`' );
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
	 * @return array|null
	 */
	public function getById( int $carrierId ): ?array {
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
	 * @return array|null
	 */
	public function getByCountry( string $country ): ?array {
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
	 * @return array|null
	 */
	public function getActiveCarriers(): ?array {
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
}
