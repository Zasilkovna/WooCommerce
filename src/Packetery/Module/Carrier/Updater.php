<?php
/**
 * Packeta carrier updater
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Core\Log\ILogger;
use Packetery\Core\Log\Record;

/**
 * Class CarrierUpdater.
 *
 * @package Packetery
 */
class Updater {

	/**
	 * Log messages.
	 *
	 * @var array
	 */
	private $logMessages = [];

	/**
	 * Carrier repository.
	 *
	 * @var Repository
	 */
	private $carrier_repository;

	/**
	 * Logger.
	 *
	 * @var ILogger
	 */
	private $logger;

	/**
	 * CarrierUpdater constructor.
	 *
	 * @param Repository $carrier_repository Carrier repository.
	 * @param ILogger    $logger             Logger.
	 */
	public function __construct( Repository $carrier_repository, ILogger $logger ) {
		$this->carrier_repository = $carrier_repository;
		$this->logger             = $logger;
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
		$mapped_data  = $this->carriers_mapper( $carriers );
		$carriersInDb = $this->carrier_repository->getAllRawIndexed();
		foreach ( $mapped_data as $carrier_id => $carrier ) {
			if ( ! empty( $carriersInDb[ $carrier_id ] ) ) {
				$this->carrier_repository->update( $carrier, (int) $carrier_id );
				$differences = $this->getArrayDifferences( $carriersInDb[ $carrier_id ], $carrier );
				if ( ! empty( $differences ) ) {
					$this->addLogEntry(
						// translators: %s is carrier name.
						sprintf( __( 'Carrier parameters changed for carrier "%s".', 'packeta' ), $carrier['name'] ) . ' ' .

						__( 'New parameters', 'packeta' ) . ': ' . implode( ', ', $differences )
					);
				}
				unset( $carriersInDb[ $carrier_id ] );
			} else {
				$carrier['id'] = $carrier_id;
				$this->carrier_repository->insert( $carrier );
				$this->addLogEntry(
					// translators: %s is carrier name.
					sprintf( __( 'A new carrier "%s" has been added.', 'packeta' ), $carrier['name'] )
				);
			}
		}

		if ( ! empty( $carriersInDb ) ) {
			$this->carrier_repository->set_as_deleted( array_keys( $carriersInDb ) );
			foreach ( $carriersInDb as $deletedCarrier ) {
				if ( true === (bool) $deletedCarrier['deleted'] ) {
					continue;
				}
				$this->addLogEntry(
					// translators: %s is carrier name.
					sprintf( __( 'Carrier "%s" has been removed.', 'packeta' ), $deletedCarrier['name'] )
				);
			}
		}

		if ( ! empty( $this->logMessages ) ) {
			set_transient( CountryListingPage::TRANSIENT_CARRIER_CHANGES, true );
		} else {
			delete_transient( CountryListingPage::TRANSIENT_CARRIER_CHANGES );
		}
	}

	/**
	 * Gets array changes as array of strings.
	 *
	 * @param array $old Previous version.
	 * @param array $new New version.
	 *
	 * @return string[]
	 */
	private function getArrayDifferences( array $old, array $new ): array {
		$differences    = [];
		$columnSettings = $this->getColumnSettings();

		foreach ( $old as $key => $oldValue ) {
			if ( 'id' === $key ) {
				continue;
			}

			if ( 'deleted' === $key ) {
				if ( '1' === (string) $oldValue && isset( $new['name'] ) ) {
					$differences[ $key ] = __( 'carrier was re-enabled', 'packeta' );
				}
				continue;
			}

			$newValue = (string) $new[ $key ];
			if ( $columnSettings[ $key ]['isBoolean'] ) {
				$newValue = ( '1' === $newValue ? $newValue : '0' );
				$oldValue = ( '1' === (string) $oldValue ? (string) $oldValue : '0' );
			}
			if ( (string) $oldValue === $newValue ) {
				continue;
			}
			if ( $columnSettings[ $key ]['isBoolean'] ) {
				$newValue = ( '1' === $newValue ? __( 'yes', 'packeta' ) : __( 'no', 'packeta' ) );
				$oldValue = ( '1' === $oldValue ? __( 'yes', 'packeta' ) : __( 'no', 'packeta' ) );
			}
			$differences[ $key ] = $columnSettings[ $key ]['label'] . ': ' . $oldValue . ' => ' . $newValue;
		}

		return $differences;
	}

	/**
	 * Adds log entry.
	 *
	 * @param string $message Message.
	 *
	 * @return void
	 */
	private function addLogEntry( string $message ): void {
		$this->logMessages[] = $message;

		$record         = new Record();
		$record->action = Record::ACTION_CARRIER_LIST_UPDATE;
		$record->status = Record::STATUS_SUCCESS;
		$record->title  = $message;
		$record->params = [];
		$this->logger->add( $record );
	}

	/**
	 * Gets translations and isBoolean properties for column names.
	 *
	 * @return array[]
	 */
	private function getColumnSettings(): array {
		return [
			'name'                     => [
				'label'     => __( 'name', 'packeta' ),
				'isBoolean' => false,
			],
			'is_pickup_points'         => [
				'label'     => __( 'offers own pickup points', 'packeta' ),
				'isBoolean' => true,
			],
			'has_carrier_direct_label' => [
				'label'     => __( 'supports direct labels', 'packeta' ),
				'isBoolean' => true,
			],
			'separate_house_number'    => [
				'label'     => __( 'requires separate house number', 'packeta' ),
				'isBoolean' => true,
			],
			'customs_declarations'     => [
				'label'     => __( 'requires completion of customs declarations', 'packeta' ),
				'isBoolean' => true,
			],
			'requires_email'           => [
				'label'     => __( 'requires email', 'packeta' ),
				'isBoolean' => true,
			],
			'requires_phone'           => [
				'label'     => __( 'requires phone number', 'packeta' ),
				'isBoolean' => true,
			],
			'requires_size'            => [
				'label'     => __( 'requires package size', 'packeta' ),
				'isBoolean' => true,
			],
			'disallows_cod'            => [
				'label'     => __( 'disallows COD', 'packeta' ),
				'isBoolean' => true,
			],
			'country'                  => [
				'label'     => __( 'country', 'packeta' ),
				'isBoolean' => false,
			],
			'currency'                 => [
				'label'     => __( 'currency', 'packeta' ),
				'isBoolean' => false,
			],
			'max_weight'               => [
				'label'     => __( 'maximum weight (kg)', 'packeta' ),
				'isBoolean' => false,
			],
		];
	}

}
