<?php
/**
 * Packeta pickup points configuration.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Core\Entity;

/**
 * Packeta pickup points configuration.
 *
 * @package Packetery
 */
class PacketaPickupPointsConfig {

	public const COMPOUND_CARRIER_PREFIX = 'zpoint';

	/**
	 * Returns internal pickup points configuration
	 *
	 * @return array[]
	 */
	public function getCompoundCarriers(): array {
		// TODO: take into account that not all types of pickup points support age verification.
		// Can lead to situation with no pickup points selected when age verification is required.
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
				'country'                   => 'cz',
				'group'                     => Entity\Carrier::VENDOR_GROUP_ZPOINT,
				'name'                      => 'CZ ' . __( 'Packeta internal pickup points', 'packeta' ),
				'supports_cod'              => true,
				'supports_age_verification' => true,
			],
			'czzbox'    => [
				'country'                   => 'cz',
				'group'                     => 'zbox',
				'name'                      => 'CZ ' . __( 'Packeta', 'packeta' ) . ' Z-BOX',
				'supports_cod'              => true,
				'supports_age_verification' => false,
			],
			'czalzabox' => [
				'country'                   => 'cz',
				'group'                     => 'alzabox',
				'name'                      => 'CZ AlzaBox',
				'supports_cod'              => true,
				'supports_age_verification' => false,
			],
			'skzpoint'  => [
				'country'                   => 'sk',
				'group'                     => Entity\Carrier::VENDOR_GROUP_ZPOINT,
				'name'                      => 'SK ' . __( 'Packeta internal pickup points', 'packeta' ),
				'supports_cod'              => true,
				'supports_age_verification' => true,
			],
			'skzbox'    => [
				'country'                   => 'sk',
				'group'                     => 'zbox',
				'name'                      => 'SK ' . __( 'Packeta', 'packeta' ) . ' Z-BOX',
				'supports_cod'              => true,
				'supports_age_verification' => false,
			],
			'huzpoint'  => [
				'country'                   => 'hu',
				'group'                     => Entity\Carrier::VENDOR_GROUP_ZPOINT,
				'name'                      => 'HU ' . __( 'Packeta internal pickup points', 'packeta' ),
				'supports_cod'              => true,
				'supports_age_verification' => true,
			],
			'huzbox'    => [
				'country'                   => 'hu',
				'group'                     => 'zbox',
				'name'                      => 'HU ' . __( 'Packeta', 'packeta' ) . ' Z-BOX',
				'supports_cod'              => true,
				'supports_age_verification' => false,
			],
			'rozpoint'  => [
				'country'                   => 'ro',
				'group'                     => Entity\Carrier::VENDOR_GROUP_ZPOINT,
				'name'                      => 'RO ' . __( 'Packeta internal pickup points', 'packeta' ),
				'supports_cod'              => true,
				'supports_age_verification' => true,
			],
			'rozbox'    => [
				'country'                   => 'ro',
				'group'                     => 'zbox',
				'name'                      => 'RO ' . __( 'Packeta', 'packeta' ) . ' Z-BOX',
				'supports_cod'              => true,
				'supports_age_verification' => false,
			],
		];
	}

	/**
	 * Gets all non-feed carriers settings.
	 *
	 * @return array
	 */
	public function getCompoundAndVendorCarriers(): array {
		$nonFeedCarriers = [];

		$compoundCarriers = $this->getCompoundCarriers();
		foreach ( $compoundCarriers as $compoundCarrier ) {
			$nonFeedCarriers[ $compoundCarrier['id'] ] = $compoundCarrier;
		}

		foreach ( $this->getVendorCarriers() as $carrierId => $vendorCarrier ) {
			$nonFeedCarriers[ $carrierId ] = [
				'id'                        => $carrierId,
				'name'                      => $vendorCarrier['name'],
				'country'                   => $vendorCarrier['country'],
				'supports_cod'              => $vendorCarrier['supports_cod'],
				'supports_age_verification' => $vendorCarrier['supports_age_verification'],
				'vendor_codes'              => [ $carrierId ],
				// Vendor loads some settings from country.
				'currency'                  => $compoundCarriers[ $vendorCarrier['country'] ]['currency'],
				'is_pickup_points'          => $compoundCarriers[ $vendorCarrier['country'] ]['is_pickup_points'],
			];
		}

		return $nonFeedCarriers;
	}

	/**
	 * Tells zpoint carrier Id for given country.
	 *
	 * @param string $country Country ISO code.
	 *
	 * @return string|null
	 */
	public function getCompoundCarrierIdByCountry( string $country ): ?string {
		$compoundCarriers = $this->getCompoundCarriers();

		if ( ! isset( $compoundCarriers[ $country ] ) ) {
			return null;
		}

		return $compoundCarriers[ $country ]['id'];
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
			if ( $nonFeedCarrier['country'] === $country ) {
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

}
