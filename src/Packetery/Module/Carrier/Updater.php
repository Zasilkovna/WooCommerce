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
	 * @var string[]
	 */
	private $logMessages = [];

	/**
	 * Carrier repository.
	 *
	 * @var Repository
	 */
	private $carrierRepository;

	/**
	 * Logger.
	 *
	 * @var ILogger
	 */
	private $logger;

	/**
	 * CarrierUpdater constructor.
	 *
	 * @param Repository $carrierRepository Carrier repository.
	 * @param ILogger    $logger             Logger.
	 */
	public function __construct( Repository $carrierRepository, ILogger $logger ) {
		$this->carrierRepository = $carrierRepository;
		$this->logger            = $logger;
	}

	/**
	 * Validates data from API.
	 *
	 * @param non-empty-array<int, array<string, string>> $carriers
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
	 * @param array<int, array<string, string>> $carriers $carriers Validated data retrieved from API.
	 *
	 * @return array<int, array<string, string|float|bool>> data to store in db
	 */
	private function carriers_mapper( array $carriers ): array {
		$mappedData = array();

		$carrierBooleanParams = array(
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
			$carrierId   = (int) $carrier['id'];
			$carrierData = array(
				'name'       => $carrier['name'],
				'country'    => $carrier['country'],
				'currency'   => $carrier['currency'],
				'max_weight' => (float) $carrier['maxWeight'],
				'deleted'    => false,
			);
			foreach ( $carrierBooleanParams as $columnName => $paramName ) {
				$carrierData[ $columnName ] = ( $carrier[ $paramName ] === 'true' );
			}
			$mappedData[ $carrierId ] = $carrierData;
		}

		return $mappedData;
	}

	/**
	 * Saves carriers.
	 *
	 * @param array<int, array<string, string>> $carriers Validated data retrieved from API.
	 */
	public function save( array $carriers ): void {
		$mappedData   = $this->carriers_mapper( $carriers );
		$carriersInDb = $this->carrierRepository->getAllRawIndexed();
		foreach ( $mappedData as $carrierId => $carrier ) {
			if ( isset( $carriersInDb[ $carrierId ] ) ) {
				$this->carrierRepository->update( $carrier, $carrierId );
				$differences = $this->getArrayDifferences( $carriersInDb[ $carrierId ], $carrier );
				if ( count( $differences ) > 0 ) {
					$this->addLogEntry(
						// translators: %s is carrier name.
						sprintf( __( 'Carrier parameters changed for carrier "%s".', 'packeta' ), $carrier['name'] ) . ' ' .

						__( 'New parameters', 'packeta' ) . ': ' . implode( ', ', $differences )
					);
				}
				unset( $carriersInDb[ $carrierId ] );
			} else {
				$carrier['id'] = $carrierId;
				$this->carrierRepository->insert( $carrier );
				$this->addLogEntry(
					// translators: %s is carrier name.
					sprintf( __( 'A new carrier "%s" has been added.', 'packeta' ), $carrier['name'] )
				);
			}
		}

		if ( count( $carriersInDb ) > 0 ) {
			$this->carrierRepository->set_as_deleted( array_keys( $carriersInDb ) );
			foreach ( $carriersInDb as $deletedCarrier ) {
				if ( (bool) $deletedCarrier['deleted'] === true ) {
					continue;
				}
				$this->addLogEntry(
					// translators: %s is carrier name.
					sprintf( __( 'Carrier "%s" has been removed.', 'packeta' ), $deletedCarrier['name'] )
				);
			}
		}

		if ( count( $this->logMessages ) > 0 ) {
			set_transient( CountryListingPage::TRANSIENT_CARRIER_CHANGES, true );
		} else {
			delete_transient( CountryListingPage::TRANSIENT_CARRIER_CHANGES );
		}
	}

	/**
	 * Gets array changes as array of strings.
	 *
	 * @param array<string, bool|float|string> $oldData Previous version.
	 * @param array<string, bool|float|string> $newData New version.
	 *
	 * @return array<string, string>
	 */
	private function getArrayDifferences( array $oldData, array $newData ): array {
		$differences    = [];
		$columnSettings = $this->getColumnSettings();

		foreach ( $oldData as $key => $oldValue ) {
			if ( $key === 'id' ) {
				continue;
			}

			if ( $key === 'deleted' ) {
				if ( (string) $oldValue === '1' && isset( $newData['name'] ) ) {
					$differences[ $key ] = __( 'carrier was re-enabled', 'packeta' );
				}

				continue;
			}

			$newValue = (string) $newData[ $key ];
			if ( $columnSettings[ $key ]['isBoolean'] === true ) {
				$newValue = ( $newValue === '1' ? $newValue : '0' );
				$oldValue = ( (string) $oldValue === '1' ? (string) $oldValue : '0' );
			}
			if ( (string) $oldValue === $newValue ) {
				continue;
			}
			if ( $columnSettings[ $key ]['isBoolean'] === true ) {
				$newValue = ( $newValue === '1' ? __( 'yes', 'packeta' ) : __( 'no', 'packeta' ) );
				$oldValue = ( $oldValue === '1' ? __( 'yes', 'packeta' ) : __( 'no', 'packeta' ) );
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
	 * @return array<string, array<string, string|bool>>
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
