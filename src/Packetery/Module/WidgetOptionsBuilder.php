<?php
/**
 * Class WidgetOptionsBuilder
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Core\Entity;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;

/**
 * Class WidgetOptionsBuilder
 *
 * @package Packetery
 */
class WidgetOptionsBuilder {

	/**
	 * Internal pickup points config.
	 *
	 * @var PacketaPickupPointsConfig
	 */
	private $pickupPointsConfig;

	/**
	 * WidgetOptionsBuilder constructor.
	 *
	 * @param PacketaPickupPointsConfig $pickupPointsConfig     Internal pickup points config.
	 */
	public function __construct(
		PacketaPickupPointsConfig $pickupPointsConfig
	) {
		$this->pickupPointsConfig = $pickupPointsConfig;
	}

	/**
	 * Gets widget vendors param.
	 *
	 * @param string     $carrierId    Carrier id.
	 * @param string     $country      Country.
	 * @param array|null $vendorGroups Vendor groups.
	 *
	 * @return array|null
	 */
	public function getWidgetVendorsParam( string $carrierId, string $country, ?array $vendorGroups ): ?array {
		if ( is_numeric( $carrierId ) ) {
			return [
				[
					'carrierId' => $carrierId,
					'selected'  => true,
				],
			];
		}

		$vendorCarriers = $this->pickupPointsConfig->getVendorCarriers();
		if ( ! empty( $vendorCarriers[ $carrierId ] ) ) {
			$vendorGroups = [ $vendorCarriers[ $carrierId ]['group'] ];
		}

		if ( empty( $vendorGroups ) ) {
			return null;
		}

		$vendorsParam = [];
		foreach ( $vendorGroups as $code ) {
			$groupSettings = [
				'selected' => true,
				'country'  => $country,
			];
			if ( Entity\Carrier::VENDOR_GROUP_ZPOINT !== $code ) {
				$groupSettings['group'] = $code;
			}
			$vendorsParam[] = $groupSettings;
		}

		return $vendorsParam;
	}

}
