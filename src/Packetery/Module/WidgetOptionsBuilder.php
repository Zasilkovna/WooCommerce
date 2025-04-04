<?php
/**
 * Class WidgetOptionsBuilder
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Core\Entity;
use Packetery\Core\Entity\Order;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\FlagManager\FeatureFlagProvider;

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
	 * Feature flag.
	 *
	 * @var FeatureFlagProvider
	 */
	private $featureFlagProvider;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	public function __construct(
		PacketaPickupPointsConfig $pickupPointsConfig,
		FeatureFlagProvider $featureFlagProvider,
		WpAdapter $wpAdapter
	) {
		$this->pickupPointsConfig  = $pickupPointsConfig;
		$this->featureFlagProvider = $featureFlagProvider;
		$this->wpAdapter           = $wpAdapter;
	}

	/**
	 * Gets widget vendors param.
	 *
	 * @param string     $carrierId    Carrier id.
	 * @param string     $country      Country.
	 * @param array|null $vendorGroups Vendor groups when checked in compound carrier settings.
	 *
	 * @return array
	 */
	private function getWidgetVendorsParam( string $carrierId, string $country, ?array $vendorGroups ): array {
		if ( is_numeric( $carrierId ) ) {
			return [
				[
					'carrierId' => $carrierId,
					'selected'  => true,
				],
			];
		}

		if ( ! isset( $vendorGroups ) || count( $vendorGroups ) === 0 ) {
			if ( $this->pickupPointsConfig->isCompoundCarrierId( $carrierId ) ) {
				$vendorGroups = $this->pickupPointsConfig->getCompoundCarrierVendorGroups( $carrierId );
			} else {
				$vendorCarriers = $this->pickupPointsConfig->getVendorCarriers();
				if ( isset( $vendorCarriers[ $carrierId ] ) ) {
					$vendorGroups = [ $vendorCarriers[ $carrierId ]->getGroup() ];
				}
			}
		}

		$vendorsParam = [];
		foreach ( $vendorGroups as $code ) {
			$groupSettings = [
				'selected' => true,
				'country'  => $country,
			];
			if ( $code !== Entity\Carrier::VENDOR_GROUP_ZPOINT ) {
				$groupSettings['group'] = $code;
			}
			$vendorsParam[] = $groupSettings;
		}

		return $vendorsParam;
	}

	/**
	 * Gets widget carriers param.
	 *
	 * @param bool   $isPickupPoints Is context pickup point related.
	 * @param string $carrierId      Carrier id.
	 *
	 * @return string|null
	 */
	private function getCarriersParam( bool $isPickupPoints, string $carrierId ): ?string {
		if ( $isPickupPoints ) {
			return ( is_numeric( $carrierId ) ? $carrierId : Entity\Carrier::INTERNAL_PICKUP_POINTS_ID );
		}

		return null;
	}

	/**
	 * Gets carrier configuration for widgets in frontend.
	 *
	 * @param Entity\Carrier $carrier         Carrier configuration.
	 * @param string         $optionId        Option id.
	 *
	 * @return array
	 */
	public function getCarrierForCheckout( Entity\Carrier $carrier, string $optionId ): array {
		$carrierConfigForWidget = [
			'id'               => $carrier->getId(),
			'is_pickup_points' => (int) $carrier->hasPickupPoints(),
		];

		$carrierOption = $this->wpAdapter->getOption( $optionId );
		if ( $carrier->hasPickupPoints() ) {
			if ( $this->featureFlagProvider->isSplitActive() ) {
				$carrierConfigForWidget['vendors'] = $this->getWidgetVendorsParam(
					$carrier->getId(),
					$carrier->getCountry(),
					( ( ( $carrierOption !== null && $carrierOption !== false ) && isset( $carrierOption['vendor_groups'] ) ) ? $carrierOption['vendor_groups'] : null )
				);
			} else {
				$carrierConfigForWidget['carriers'] = $this->getCarriersParam( true, $carrier->getId() );
			}
		}

		if ( ! $carrier->hasPickupPoints() ) {
			$addressValidation = 'none';
			if ( ( $carrierOption !== null && $carrierOption !== false ) && in_array( $carrier->getCountry(), Entity\Carrier::ADDRESS_VALIDATION_COUNTRIES, true ) ) {
				$addressValidation = ( $carrierOption['address_validation'] ?? $addressValidation );
			}

			$carrierConfigForWidget['address_validation'] = $addressValidation;
		}

		return $carrierConfigForWidget;
	}

	/**
	 * Creates pickup point widget options in backend.
	 *
	 * @param Order $order Order.
	 *
	 * @return array
	 */
	public function createPickupPointForAdmin( Order $order ): array {
		$widgetOptions = [
			'country'     => $order->getShippingCountry(),
			'language'    => substr( $this->wpAdapter->getUserLocale(), 0, 2 ),
			'appIdentity' => Plugin::getAppIdentity(),
			'weight'      => $order->getFinalWeight(),
		];

		if ( $this->featureFlagProvider->isSplitActive() ) {
			// In backend, we want all pickup points in that country for Packeta carrier.
			if ( $order->getCarrier()->getId() !== Entity\Carrier::INTERNAL_PICKUP_POINTS_ID ) {
				$widgetOptions['vendors'] = $this->getWidgetVendorsParam(
					$order->getCarrier()->getId(),
					$order->getShippingCountry(),
					null
				);
			}
		} else {
			$widgetOptions['carriers'] = $this->getCarriersParam( $order->isPickupPointDelivery(), $order->getCarrier()->getId() );
		}

		if ( $order->containsAdultContent() === true ) {
			$widgetOptions += [ 'livePickupPoint' => true ];
		}

		return $widgetOptions;
	}

	/**
	 * Creates address validation widget options in backend.
	 *
	 * @param Order $order Order.
	 *
	 * @return array
	 */
	public function createAddressForAdmin( Order $order ): array {
		/**
		 * Delivery address is always present in this case.
		 *
		 * @var Entity\Address $deliveryAddress
		 */
		$deliveryAddress = $order->getDeliveryAddress();
		$widgetOptions   = [
			'country'     => $order->getShippingCountry(),
			'language'    => substr( $this->wpAdapter->getUserLocale(), 0, 2 ),
			'layout'      => 'hd',
			'appIdentity' => Plugin::getAppIdentity(),
			'street'      => $deliveryAddress->getStreet(),
			'city'        => $deliveryAddress->getCity(),
			'postcode'    => $deliveryAddress->getZip(),
		];

		if ( $deliveryAddress->getHouseNumber() !== null ) {
			$widgetOptions += [ 'houseNumber' => $deliveryAddress->getHouseNumber() ];
		}

		if ( $deliveryAddress->getCounty() !== null ) {
			$widgetOptions += [ 'county' => $deliveryAddress->getCounty() ];
		}

		if ( is_numeric( $order->getCarrier()->getId() ) ) {
			$widgetOptions += [ 'carrierId' => $order->getCarrier()->getId() ];
		}

		return $widgetOptions;
	}
}
