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

/**
 * Class CarrierRepository
 * TODO: cache - some queries may run more times during request.
 *
 * @package Packetery
 */
class Repository {

	public const INTERNAL_PICKUP_POINTS_ID = 'packeta';

	/**
	 * WordPress wpdb object from global
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Carrier Entity Factory.
	 *
	 * @var EntityFactory\Carrier
	 */
	private $carrierEntityFactory;

	/**
	 * Repository constructor.
	 *
	 * @param \wpdb                 $wpdb wpdb.
	 * @param EntityFactory\Carrier $carrierEntityFactory Carrier Entity Factory.
	 */
	public function __construct( \wpdb $wpdb, EntityFactory\Carrier $carrierEntityFactory ) {
		$this->wpdb                 = $wpdb;
		$this->carrierEntityFactory = $carrierEntityFactory;
	}

	/**
	 * Gets wpdb object from global variable with custom tables names set.
	 *
	 * @return \wpdb
	 */
	private function get_wpdb(): \wpdb {
		return $this->wpdb;
	}

	/**
	 * Create table to store carriers.
	 *
	 * @return bool
	 */
	public function createTable(): bool {
		$wpdb = $this->get_wpdb();
		return $wpdb->query(
			'CREATE TABLE IF NOT EXISTS `' . $wpdb->packetery_carrier . '` (
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
			) ' . $wpdb->get_charset_collate()
		);
	}

	/**
	 * Drop table used to store carriers.
	 */
	public function drop(): void {
		$wpdb = $this->get_wpdb();
		$wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->packetery_carrier . '`' );
	}

	/**
	 * Gets known carrier ids.
	 *
	 * @return array|null
	 */
	public function get_carrier_ids(): ?array {
		$wpdb = $this->get_wpdb();

		return $wpdb->get_results( 'SELECT `id` FROM `' . $wpdb->packetery_carrier . '`', ARRAY_A );
	}

	/**
	 * Gets all active carriers including internal pickup point carriers.
	 *
	 * @return array|null
	 */
	public function getAllIncludingZpoints(): ?array {
		$wpdb = $this->get_wpdb();

		$carriers       = $wpdb->get_results( 'SELECT `id`, `name`, `is_pickup_points`  FROM `' . $wpdb->packetery_carrier . '`', ARRAY_A );
		$zpointCarriers = $this->getZpointCarriers();
		foreach ( $zpointCarriers as $zpointCarrier ) {
			array_unshift( $carriers, $zpointCarrier );
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
		$wpdb = $this->get_wpdb();

		return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT `is_pickup_points` FROM `' . $wpdb->packetery_carrier . '` WHERE `id` = %d', $carrierId ) );
	}

	/**
	 * Gets Carrier value object by id.
	 *
	 * @param int $carrierId Carrier id.
	 *
	 * @return Entity\Carrier|null
	 */
	public function getById( int $carrierId ): ?Entity\Carrier {
		$wpdb   = $this->get_wpdb();
		$result = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT
					`id`,
					`name`,
					`is_pickup_points`,
					`has_carrier_direct_label`,
					`separate_house_number`,
					`customs_declarations`,
					`requires_email`,
					`requires_phone`,
					`requires_size`,
					`disallows_cod`,
					`country`,
					`currency`,
					`max_weight`,
					`deleted`
				FROM `' . $wpdb->packetery_carrier . '` WHERE `id` = %s',
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
	 * Gets all active carriers for a country.
	 *
	 * @param string $country ISO code.
	 *
	 * @return Entity\Carrier[]
	 */
	public function getByCountry( string $country ): array {
		$wpdb = $this->get_wpdb();

		$entities        = [];
		$countryCarriers = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT
					`id`,
					`name`,
					`is_pickup_points`,
					`has_carrier_direct_label`,
					`separate_house_number`,
					`customs_declarations`,
					`requires_email`,
					`requires_phone`,
					`requires_size`,
					`disallows_cod`,
					`country`,
					`currency`,
					`max_weight`,
					`deleted`
				FROM `' . $wpdb->packetery_carrier . '` WHERE `country` = %s AND `deleted` = false',
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
	 * Gets all active carriers for a country including internal pickup point carriers.
	 *
	 * @param string $country ISO code.
	 *
	 * @return Entity\Carrier[]
	 */
	public function getByCountryIncludingZpoints( string $country ): array {
		$countryCarriers = $this->getByCountry( $country );
		$zpointCarriers  = $this->getZpointCarriers();
		if ( ! empty( $zpointCarriers[ $country ] ) ) {
			$zpointCarrierData            = $zpointCarriers[ $country ];
			$zpointCarrierData['country'] = $country;
			$zpointCarrier                = $this->carrierEntityFactory->fromZpointCarrierData( $zpointCarrierData );
			array_unshift( $countryCarriers, $zpointCarrier );
		}

		return $countryCarriers;
	}

	/**
	 * Gets all active countries.
	 *
	 * @return array
	 */
	public function getCountries(): array {
		$wpdb      = $this->get_wpdb();
		$countries = $wpdb->get_results( 'SELECT `country` FROM `' . $wpdb->packetery_carrier . '` WHERE `deleted` = false GROUP BY `country` ORDER BY `country`', ARRAY_A );

		return array_column( ( $countries ? $countries : [] ), 'country' );
	}

	/**
	 * Set those not in feed as deleted.
	 *
	 * @param array $carriers_in_feed Carriers in feed.
	 */
	public function set_others_as_deleted( array $carriers_in_feed ): void {
		$wpdb = $this->get_wpdb();
		$wpdb->query(
			'UPDATE `' .
			$wpdb->packetery_carrier .
			'` SET `deleted` = 1 WHERE `id` NOT IN (' .
	            // @codingStandardsIgnoreStart
				implode( ',', $carriers_in_feed )
	            // @codingStandardsIgnoreEnd
			. ')'
		);
	}

	/**
	 * Inserts carrier data to db.
	 *
	 * @param array $data Carrier data.
	 */
	public function insert( array $data ): void {
		$wpdb = $this->get_wpdb();
		$wpdb->insert( $wpdb->packetery_carrier, $data );
	}

	/**
	 * Updates carrier data in db.
	 *
	 * @param array $data Carrier data.
	 * @param int   $carrier_id Carrier id.
	 */
	public function update( array $data, int $carrier_id ): void {
		$wpdb = $this->get_wpdb();
		$wpdb->update( $wpdb->packetery_carrier, $data, array( 'id' => $carrier_id ) );
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
				'name'                      => __( 'CZ Packeta pickup points', 'packetery' ),
				'is_pickup_points'          => 1,
				'currency'                  => 'CZK',
				'supports_age_verification' => true,
			],
			'sk' => [
				'id'                        => 'zpointsk',
				'name'                      => __( 'SK Packeta pickup points', 'packetery' ),
				'is_pickup_points'          => 1,
				'currency'                  => 'EUR',
				'supports_age_verification' => true,
			],
			'hu' => [
				'id'                        => 'zpointhu',
				'name'                      => __( 'HU Packeta pickup points', 'packetery' ),
				'is_pickup_points'          => 1,
				'currency'                  => 'HUF',
				'supports_age_verification' => true,
			],
			'ro' => [
				'id'                        => 'zpointro',
				'name'                      => __( 'RO Packeta pickup points', 'packetery' ),
				'is_pickup_points'          => 1,
				'currency'                  => 'RON',
				'supports_age_verification' => true,
			],
		];
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
