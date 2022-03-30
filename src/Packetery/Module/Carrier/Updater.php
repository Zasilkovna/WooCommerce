<?php
/**
 * Packeta carrier updater
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

/**
 * Class CarrierUpdater.
 *
 * @package Packetery
 */
class Updater {

	/**
	 * Carrier repository.
	 *
	 * @var Repository
	 */
	private $carrier_repository;

	/**
	 * CarrierUpdater constructor.
	 *
	 * @param Repository $carrier_repository Carrier repository.
	 */
	public function __construct( Repository $carrier_repository ) {
		$this->carrier_repository = $carrier_repository;
	}

	/**
	 * Validates data from API.
	 *
	 * @param array $carriers Data retrieved from API.
	 *
	 * @return bool
	 */
	public function validate_carrier_data( array $carriers ): bool {
		foreach ( $carriers as $carrier ) {
			if ( ! isset(
				$carrier['id'],
				$carrier['name'],
				$carrier['country'],
				$carrier['currency'],
				$carrier['pickupPoints'],
				$carrier['apiAllowed'],
				$carrier['separateHouseNumber'],
				$carrier['customsDeclarations'],
				$carrier['requiresEmail'],
				$carrier['requiresPhone'],
				$carrier['requiresSize'],
				$carrier['disallowsCod'],
				$carrier['maxWeight']
			) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Maps input data to storage structure.
	 *
	 * @param array $carriers Validated data retrieved from API.
	 *
	 * @return array data to store in db
	 */
	private function carriers_mapper( array $carriers ): array {
		$mapped_data = array();

		$carrier_boolean_params = array(
			'is_pickup_points'         => 'pickupPoints',
			'has_carrier_direct_label' => 'apiAllowed',
			'separate_house_number'    => 'separateHouseNumber',
			'customs_declarations'     => 'customsDeclarations',
			'requires_email'           => 'requiresEmail',
			'requires_phone'           => 'requiresPhone',
			'requires_size'            => 'requiresSize',
			'disallows_cod'            => 'disallowsCod',
		);

		foreach ( $carriers as $carrier ) {
			$carrier_id   = (int) $carrier['id'];
			$carrier_data = array(
				'name'       => $carrier['name'],
				'country'    => $carrier['country'],
				'currency'   => $carrier['currency'],
				'max_weight' => (float) $carrier['maxWeight'],
				'deleted'    => false,
			);
			foreach ( $carrier_boolean_params as $column_name => $param_name ) {
				$carrier_data[ $column_name ] = ( 'true' === $carrier[ $param_name ] );
			}
			$mapped_data[ $carrier_id ] = $carrier_data;
		}

		return $mapped_data;
	}

	/**
	 * Saves carriers.
	 *
	 * @param array $carriers Validated data retrieved from API.
	 */
	public function save( array $carriers ): void {
		$mapped_data      = $this->carriers_mapper( $carriers );
		$carriers_in_feed = array();

		$carrier_check  = $this->carrier_repository->get_carrier_ids();
		$carriers_in_db = array_column( $carrier_check, 'id' );
		foreach ( $mapped_data as $carrier_id => $carrier ) {
			$carriers_in_feed[] = $carrier_id;
			if ( in_array( (string) $carrier_id, $carriers_in_db, true ) ) {
				$this->carrier_repository->update( $carrier, (int) $carrier_id );
			} else {
				$carrier['id'] = $carrier_id;
				$this->carrier_repository->insert( $carrier );
			}
		}

		$this->carrier_repository->set_others_as_deleted( $carriers_in_feed );
	}
}
