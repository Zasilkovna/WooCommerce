<?php

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Core\Entity;
use Packetery\Core\Entity\Order;
use Packetery\Module\Carrier\PacketaPickupPointsConfig;
use Packetery\Module\Exception\EmptyVendorsException;
use Packetery\Module\Framework\WpAdapter;

class WidgetOptionsBuilder {

	/**
	 * Internal pickup points config.
	 *
	 * @var PacketaPickupPointsConfig
	 */
	private $pickupPointsConfig;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	public function __construct(
		PacketaPickupPointsConfig $pickupPointsConfig,
		WpAdapter $wpAdapter
	) {
		$this->pickupPointsConfig = $pickupPointsConfig;
		$this->wpAdapter          = $wpAdapter;
	}

	/**
	 * Gets widget vendors param.
	 *
	 * @param string     $carrierId    Carrier id.
	 * @param string     $country      Country.
	 * @param array|null $vendorGroups Vendor groups when checked in compound carrier settings.
	 *
	 * @return array
	 * @throws EmptyVendorsException
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

		$vendorGroups = $this->pickupPointsConfig->getFinalVendorGroups( $vendorGroups, $carrierId );
		if ( $vendorGroups === null ) {
			throw new EmptyVendorsException( 'Empty vendor groups for internal carrier.' );
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
			$carrierConfigForWidget['vendors'] = $this->getWidgetVendorsParam(
				$carrier->getId(),
				$carrier->getCountry(),
				( ( ( $carrierOption !== null && $carrierOption !== false ) && isset( $carrierOption['vendor_groups'] ) ) ? $carrierOption['vendor_groups'] : null )
			);
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

		// In backend, we want all pickup points in that country for Packeta carrier.
		if ( $order->getCarrier()->getId() !== Entity\Carrier::INTERNAL_PICKUP_POINTS_ID ) {
			$widgetOptions['vendors'] = $this->getWidgetVendorsParam(
				$order->getCarrier()->getId(),
				$order->getShippingCountry(),
				null
			);
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
